/* Urbs — service worker. Páginas: rede primeiro; arquivos estáticos: cache primeiro. */
'use strict';

const VERSION = 'urbs-v1';
const STATIC_CACHE = VERSION + '-static';
const PAGES_CACHE = VERSION + '-pages';
const IMG_CACHE = VERSION + '-img';
const SCOPE = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const OFFLINE_URL = new URL(location.href).searchParams.get('offline') || SCOPE + '/offline';

const PRECACHE = [
  OFFLINE_URL,
  SCOPE + '/assets/css/app.css',
  SCOPE + '/assets/js/app.js',
  SCOPE + '/assets/vendor/leaflet/leaflet.css',
  SCOPE + '/assets/vendor/leaflet/leaflet.js',
  SCOPE + '/assets/icons/icon.svg',
  SCOPE + '/assets/icons/icon-192.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((c) => Promise.all(PRECACHE.map((u) => c.add(new Request(u, { cache: 'reload' })).catch(() => null))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

async function trim(cacheName, max) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return; // tiles e geocodificação: sempre da rede
  const path = url.pathname;

  // API e áreas restritas: nunca do cache.
  if (path.includes('/api/') || path.includes('/moderacao') || path.includes('/conta')) return;

  // Navegação: rede primeiro, cópia em cache para leitura offline, página offline como último recurso.
  if (req.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        const res = await fetch(req);
        if (res.ok && res.type === 'basic') {
          const copy = res.clone();
          caches.open(PAGES_CACHE).then((c) => c.put(req, copy)).then(() => trim(PAGES_CACHE, 40));
        }
        return res;
      } catch (e) {
        return (await caches.match(req)) || (await caches.match(OFFLINE_URL)) || Response.error();
      }
    })());
    return;
  }

  // Arquivos versionados (?v=) e ícones: cache primeiro.
  if (path.startsWith(SCOPE + '/assets/')) {
    event.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) { const copy = res.clone(); caches.open(STATIC_CACHE).then((c) => c.put(req, copy)); }
      return res;
    })));
    return;
  }

  // Fotos enviadas (nunca mudam de conteúdo): cache primeiro, com limite.
  if (path.startsWith(SCOPE + '/uploads/') && /\.(jpe?g|png|webp)$/i.test(path)) {
    event.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) {
        const copy = res.clone();
        caches.open(IMG_CACHE).then((c) => c.put(req, copy)).then(() => trim(IMG_CACHE, 150));
      }
      return res;
    })));
  }
});
