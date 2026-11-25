const VERSION = 'recreosuite-pwa-v1';
const STATIC_CACHE = `${VERSION}-static`;
const PUBLIC_CACHE = `${VERSION}-public`;
const SAFE_SHELL = ['/offline', '/manifest.webmanifest', '/pwa/icon/192.png', '/pwa/icon/512.png'];

self.addEventListener('install', event => event.waitUntil(caches.open(STATIC_CACHE).then(cache => cache.addAll(SAFE_SHELL))));
self.addEventListener('activate', event => event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => !key.startsWith(VERSION)).map(key => caches.delete(key)))).then(() => self.clients.claim())));

self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
    if (event.data?.type === 'CLEAR_PRIVATE_DATA') event.waitUntil(caches.keys().then(keys => Promise.all(keys.map(key => caches.delete(key)))));
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== location.origin) return;
    const isAsset = url.pathname.startsWith('/build/') || url.pathname.startsWith('/pwa/icon/') || url.pathname.startsWith('/storage/branding/');
    const isPublicPage = url.pathname === '/menu' || url.pathname === '/sitio' || url.pathname.startsWith('/sitio/') || url.pathname.startsWith('/carta/');
    if (isAsset) {
        event.respondWith(caches.match(request).then(cached => cached || fetch(request).then(response => {
            if (response.ok && !response.headers.get('Cache-Control')?.includes('no-store')) caches.open(STATIC_CACHE).then(cache => cache.put(request, response.clone()));
            return response;
        })));
    } else if (isPublicPage) {
        event.respondWith(fetch(request).then(response => {
            if (response.ok && !response.headers.get('Cache-Control')?.includes('no-store')) caches.open(PUBLIC_CACHE).then(cache => cache.put(request, response.clone()));
            return response;
        }).catch(() => caches.match(request).then(cached => cached || caches.match('/offline'))));
    } else if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline')));
    }
});
