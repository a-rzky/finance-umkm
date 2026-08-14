/**
 * Service worker Catatan Usaha.
 *
 * Aturan utama: hanya aset statis yang boleh masuk cache. Halaman hasil login
 * TIDAK PERNAH di-cache, karena satu HP sering dipakai bergantian dan halaman
 * tersimpan bisa tersaji ke akun lain setelah keluar.
 */

// Naikkan setiap kali isi PRECACHE_URLS berubah, agar pengguna lama
// tidak terus disuguhi versi lama dari cache.
const CACHE_VERSION = 'v2';
const CACHE_NAME = `catatan-usaha-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
            )
            .then(() => self.clients.claim())
    );
});

/** Aset build Vite memakai nama ber-hash, jadi aman disimpan selamanya. */
const isImmutableAsset = (url) =>
    url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/');

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Hanya GET yang boleh disentuh cache; POST/PUT/DELETE selalu ke jaringan.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Permintaan ke domain lain dibiarkan apa adanya.
    if (url.origin !== self.location.origin) {
        return;
    }

    if (isImmutableAsset(url)) {
        event.respondWith(cacheFirst(request));

        return;
    }

    // Navigasi halaman: selalu ambil dari jaringan, jangan disimpan.
    // Kalau offline, tampilkan halaman pemberitahuan.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
    }

    // Sisanya (termasuk permintaan Inertia) dibiarkan langsung ke jaringan.
});

async function cacheFirst(request) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response.ok) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, response.clone());
    }

    return response;
}
