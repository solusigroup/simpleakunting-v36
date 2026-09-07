const CACHE_NAME = 'simpleakunting-v36-2';
const OFFLINE_URL = 'offline.html';

const ASSETS_TO_PRECACHE = [
  OFFLINE_URL,
  'css/app.css?v=3.6',
  'img/icon-pwa-512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css',
  'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'
];

// Install service worker & precache static shell assets
self.addEventListener('install', evt => {
  evt.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Precaching shell assets for SimpleAkunting');
      return cache.addAll(ASSETS_TO_PRECACHE).catch(err => {
        console.warn('Precaching non-critical asset failed, continuing anyway...', err);
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
          .map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

// Fetch event handler
self.addEventListener('fetch', evt => {
  // Skip non-GET requests (POST, PUT, DELETE always bypass SW)
  if (evt.request.method !== 'GET') return;

  const url = new URL(evt.request.url);

  // 1. Bypass Service Worker completely for auth, session, and backup routes
  if (
    url.pathname.includes('logout') ||
    url.pathname.includes('process') ||
    url.pathname.includes('backup')
  ) {
    return;
  }

  // 2. Navigation Requests (HTML pages): Network Only with Offline Fallback
  // SANGAT KRUSIAL: Data keuangan, saldo kas, dan mutasi jurnal TIDAK BOLEH
  // disimpan di cache browser agar user tidak melihat data basi (stale data).
  if (evt.request.mode === 'navigate') {
    evt.respondWith(
      fetch(evt.request).catch(() => {
        return caches.match(OFFLINE_URL);
      })
    );
    return;
  }

  // 3. Static Assets: Cache First (CSS, JS, Fonts, Images, Icons)
  const isStaticAsset =
    ['style', 'script', 'image', 'font'].includes(evt.request.destination) ||
    /\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$/i.test(url.pathname) ||
    url.hostname.includes('cdn.jsdelivr.net') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com');

  if (isStaticAsset) {
    evt.respondWith(
      caches.match(evt.request).then(cacheRes => {
        return (
          cacheRes ||
          fetch(evt.request).then(fetchRes => {
            if (fetchRes.status === 200) {
              const resClone = fetchRes.clone();
              caches.open(CACHE_NAME).then(cache => cache.put(evt.request, resClone));
            }
            return fetchRes;
          }).catch(err => {
            console.error('Fetch failed for static asset:', evt.request.url, err);
          })
        );
      })
    );
    return;
  }

  // 4. Default for AJAX / dynamic JSON endpoints (e.g. getFaktur, getPelanggan):
  // Langsung ke network, jangan pernah dicache sebagai asset statis
  evt.respondWith(fetch(evt.request));
});
