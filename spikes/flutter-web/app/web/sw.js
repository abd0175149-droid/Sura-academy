// WP-0.5 spike service worker (ADR-010 §6). Flutter no longer generates one, so this is ours.
// Cache-first for the build's files, network-first for navigations (fresh index.html and its
// headers), nothing for other origins or API calls. The page fills the cache after start-up with
// exactly the files this browser used; misses are added here as they happen.
const BUILD_ID = '__BUILD_ID__';
const CACHE = `sura-spike-${BUILD_ID}-${new URL(self.registration.scope).pathname}`;
const PREFIX = `sura-spike-`;
const SCOPE_SUFFIX = `-${new URL(self.registration.scope).pathname}`;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    // Other scopes (other variants and runs) keep their caches; older builds of this scope go.
    for (const key of await caches.keys()) {
      if (key.startsWith(PREFIX) && key.endsWith(SCOPE_SUFFIX) && key !== CACHE) await caches.delete(key);
    }
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET' || !req.url.startsWith(self.registration.scope)) return;
  if (req.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        return await fetch(req);
      } catch (_) {
        return (await caches.match('./', { cacheName: CACHE })) ?? Response.error();
      }
    })());
    return;
  }
  event.respondWith((async () => {
    const cache = await caches.open(CACHE);
    const hit = await cache.match(req, { ignoreSearch: true });
    if (hit) return hit;
    const res = await fetch(req);
    if (res.ok) await cache.put(req, res.clone());
    return res;
  })());
});
