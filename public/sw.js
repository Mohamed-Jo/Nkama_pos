const NKAMA_CACHE = 'nkama-pos-static-v1';
const CORE_ASSETS = ['/offline.html', '/manifest.webmanifest', '/pwa-icon.svg'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(NKAMA_CACHE).then((cache) => cache.addAll(CORE_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== NKAMA_CACHE).map((key) => caches.delete(key)))));
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  if (request.mode !== 'navigate') return;

  event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
});