const CACHE_NAME = 'simpleakunting-v3.6-pwa-v3';
const OFFLINE_URL = 'offline.html';

const ASSETS_TO_PRECACHE = [
  OFFLINE_URL,
  'css/app.css?v=3.6',
  'js/pwa.js?v=3.6',
  'img/icons/icon-192x192.png',
  'img/icons/icon-512x512.png',
  'img/icons/apple-touch-icon.png',
  'img/icon-192.png',
  'img/icon-512.png',
  'img/logo_jatim.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css',
  'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'
];

// Install service worker & precache static shell assets
self.addEventListener('install', evt => {
  evt.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[PWA] Precaching shell assets for SimpleAkunting');
      return cache.addAll(ASSETS_TO_PRECACHE).catch(err => {
        console.warn('[PWA] Precache warning (some assets will load on demand):', err);
      });
    })
  );
  self.skipWaiting();
});

// Activate event & purge outdated cache versions
self.addEventListener('activate', evt => {
  evt.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys
          .filter(key => key !== CACHE_NAME)
          .map(key => {
            console.log('[PWA] Deleting old cache:', key);
            return caches.delete(key);
          })
      );
    })
  );
  self.clients.claim();
});

// Listen for skip waiting messages from client
self.addEventListener('message', evt => {
  if (evt.data && evt.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
});

// Fetch event handler
self.addEventListener('fetch', evt => {
  // Only process GET requests
  if (evt.request.method !== 'GET') return;

  const url = new URL(evt.request.url);

  // 1. Bypass Service Worker completely for auth, session, and backup routes
  if (
    url.pathname.includes('logout') ||
    url.pathname.includes('process') ||
    url.pathname.includes('backup') ||
    url.pathname.includes('api_') ||
    url.pathname.includes('/api/')
  ) {
    return;
  }

  // 2. Navigation Requests (HTML pages): Network First with Offline Fallback
  // SANGAT KRUSIAL: Data keuangan, saldo kas, piutang, dan jurnal TIDAK BOLEH stale!
  if (evt.request.mode === 'navigate') {
    evt.respondWith(
      fetch(evt.request).catch(() => {
        return caches.match(OFFLINE_URL);
      })
    );
    return;
  }

  // 3. Static Assets: Stale-While-Revalidate (CSS, JS, Fonts, Images, Icons)
  const isStaticAsset =
    ['style', 'script', 'image', 'font'].includes(evt.request.destination) ||
    /\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$/i.test(url.pathname) ||
    url.hostname.includes('cdn.jsdelivr.net') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com');

  if (isStaticAsset) {
    evt.respondWith(
      caches.match(evt.request).then(cachedResponse => {
        const fetchPromise = fetch(evt.request).then(networkResponse => {
          if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(evt.request, responseToCache);
            });
          }
          return networkResponse;
        }).catch(err => {
          // Silent catch for network failure during static fetch
        });

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // 4. Default for other endpoints (AJAX/dynamic): Pass directly to network
  evt.respondWith(fetch(evt.request));
});
