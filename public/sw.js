const CACHE_NAME = 'simpleakunting-v3-6-v1';
const OFFLINE_URL = 'offline.html';

const ASSETS_TO_PRECACHE = [
  './',
  'login',
  OFFLINE_URL,
  'img/icon-pwa-512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'
];

// Install service worker
self.addEventListener('install', evt => {
  evt.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Precaching assets');
      return cache.addAll(ASSETS_TO_PRECACHE).catch(err => {
        console.warn('Precaching failed for some assets, continuing anyway...', err);
      });
    })
  );
  self.skipWaiting();
});

// Activate event
self.addEventListener('activate', evt => {
  evt.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(keys
        .filter(key => key !== CACHE_NAME)
        .map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', evt => {
  // Skip non-GET requests
  if (evt.request.method !== 'GET') return;

  const url = new URL(evt.request.url);

  // 1. Bypass Service Worker for logout and auth processing
  if (url.pathname.includes('logout') || url.pathname.includes('process')) {
    return;
  }

  // 2. Strategy: Network First for Navigation (HTML pages)
  // Ini memastikan user selalu mendapatkan data terbaru saat online,
  // dan hanya melihat cache/halaman offline saat koneksi terputus.
  if (evt.request.mode === 'navigate') {
    evt.respondWith(
      fetch(evt.request)
        .then(fetchRes => {
          // Update cache with fresh version
          const resClone = fetchRes.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(evt.request, resClone));
          return fetchRes;
        })
        .catch(() => {
          // If network fails, try cache then offline.html
          return caches.match(evt.request).then(cacheRes => {
            return cacheRes || caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // 3. Strategy: Cache First for Static Assets (CSS, JS, Images, Fonts)
  evt.respondWith(
    caches.match(evt.request).then(cacheRes => {
      return cacheRes || fetch(evt.request).then(fetchRes => {
        // Cache new successful requests for static assets from our origin or CDNs
        if (fetchRes.status === 200 && (url.origin === self.location.origin || url.hostname.includes('cdn.jsdelivr.net'))) {
          const resClone = fetchRes.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(evt.request, resClone));
        }
        return fetchRes;
      }).catch(err => {
        // Fallback for failed asset fetch (e.g. return a transparent pixel or nothing)
        console.error('Fetch failed for asset:', evt.request.url, err);
      });
    })
  );
});


