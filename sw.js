const CACHE_NAME   = 'candi-v1';
const STATIC_CACHE = 'candi-static-v1';

// Assets à pré-cacher à l'installation
const PRECACHE_URLS = [
  'offline.html',
  'assets/css/app.css',
  'favicon.png',
  'icon-192.png',
  'icon-512.png',
  'apple-touch-icon.png',
  'images/candi-logo-trim.png',
];

// ── Installation : pré-cache les assets statiques ─────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) =>
      cache.addAll(PRECACHE_URLS.map((u) => new Request(u, { cache: 'reload' })))
    ).then(() => self.skipWaiting())
  );
});

// ── Activation : nettoie les anciens caches ───────────────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => k !== CACHE_NAME && k !== STATIC_CACHE)
          .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch : stratégies par type de ressource ─────────────────────────────
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET et les API externes
  if (request.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;

  const isStaticAsset = /\.(css|js|png|jpg|jpeg|gif|svg|webp|woff2?|ico)(\?.*)?$/.test(url.pathname);
  const isApiRequest  = url.pathname.includes('/ai/demander') || url.pathname.includes('/webhook');

  // API calls : réseau uniquement (pas de cache)
  if (isApiRequest) return;

  if (isStaticAsset) {
    // Cache-first pour les assets statiques
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(STATIC_CACHE).then((c) => c.put(request, clone));
          }
          return response;
        });
      })
    );
  } else {
    // Network-first pour les pages HTML : essaie le réseau, repli sur cache, puis offline
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((c) => c.put(request, clone));
          }
          return response;
        })
        .catch(() =>
          caches.match(request).then((cached) => {
            if (cached) return cached;
            return caches.match('offline.html');
          })
        )
    );
  }
});
