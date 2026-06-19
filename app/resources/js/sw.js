/**
 * Service worker de ClientFlow.
 *
 * Estrategias de cache:
 * - `/build/assets/*` y `/fonts/*` → cache-first, inmutables.
 * - `/`, `/login`, `/register`, `/password/reset*`, `/invitation/*` →
 *   stale-while-revalidate.
 * - `/livewire/*` y todo bajo `/admin/*`, `/portal/*`, `/api/*` →
 *   network-only (CSRF + sesion, no se pueden servir stale).
 *
 * Notificaciones:
 * - En MVP las notificaciones son client-side: el codigo de la
 *   pagina (`pwa.js`) hace polling del contador de no leidos y
 *   envia un `postMessage` al SW. El SW muestra la notificacion
 *   del sistema via la API `Notification`.
 * - El handler `push` esta presente como stub para una fase
 *   futura en la que se introduzcan VAPID y `web-push`.
 *
 * Versionado: incrementar `CACHE_VERSION` fuerza al SW a
 * descartar caches antiguos en su proxima activacion.
 */

const CACHE_VERSION = 'cf-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGES_CACHE = `${CACHE_VERSION}-pages`;

// Assets criticos a pre-cachear en el `install`. Vite genera
// URLs con hash, asi que los URLs reales se resuelven en
// runtime via la estrategia cache-first.
const PRECACHE_URLS = [
    '/',
    '/login',
    '/register',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PAGES_CACHE).then((cache) => {
            // Pre-cacheamos las paginas publicas mas importantes.
            // Si alguna falla no abortamos la instalacion: el
            // SW sigue siendo util para cachear assets.
            return Promise.allSettled(
                PRECACHE_URLS.map((url) => cache.add(url).catch(() => null))
            );
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name.startsWith('cf-') && !name.startsWith(CACHE_VERSION))
                    .map((name) => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Solo manejamos GET. POST/PUT/PATCH/DELETE requieren ir a
    // la red siempre (formularios, mutaciones Livewire, etc).
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (!sameOrigin) {
        return;
    }

    // Network-only: rutas autenticadas y endpoints dinamicos.
    if (
        url.pathname.startsWith('/admin')
        || url.pathname.startsWith('/portal')
        || url.pathname.startsWith('/api')
        || url.pathname.startsWith('/livewire')
        || url.pathname.startsWith('/_debugbar')
    ) {
        return;
    }

    // Cache-first: assets estaticos. Vite los emite con hash,
    // asi que son seguros de cachear indefinidamente.
    if (
        url.pathname.startsWith('/build/assets/')
        || url.pathname.startsWith('/fonts/')
    ) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // Stale-while-revalidate: paginas publicas. El SW devuelve
    // la version cacheada inmediatamente y en paralelo busca
    // la version actualizada para la proxima vez.
    if (isPublicPage(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request, PAGES_CACHE));
        return;
    }

    // Para todo lo demas (mismas rutas publicas no contempladas
    // explicitamente), dejamos pasar a la red sin intervenir.
});

self.addEventListener('message', (event) => {
    const data = event.data || {};

    if (data.type === 'new-message' || data.type === 'new-task') {
        showClientNotification(data);
    }

    if (data.type === 'skip-waiting') {
        self.skipWaiting();
    }
});

/**
 * Stub de push handler. En MVP el servidor no envia push real;
 * se documenta aqui la forma que tendria el handler cuando se
 * introduzcan VAPID + `minishlink/web-push` en una fase futura.
 */
self.addEventListener('push', (event) => {
    // Por ahora ignoramos pushes del servidor: no hay
    // infraestructura push. El handler queda listo para cuando
    // se anada.
    if (!event.data) {
        return;
    }

    try {
        const payload = event.data.json();
        event.waitUntil(
            self.registration.showNotification(payload.title || 'ClientFlow', {
                body: payload.body || '',
                icon: '/icons/icon-192.png',
                badge: '/icons/icon-192.png',
                tag: payload.tag || 'clientflow-push',
                data: { url: payload.url || '/' },
            })
        );
    } catch (error) {
        // Silencioso: un push malformado no debe romper el SW.
    }
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.focus();
                    if ('navigate' in client) {
                        return client.navigate(targetUrl);
                    }
                    return;
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});

// -----------------------------------------------------------------
// Estrategias de cache (helpers)
// -----------------------------------------------------------------

async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    try {
        const response = await fetch(request);
        if (response && response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Sin red y sin cache: devolvemos un Response vacio
        // para que la peticion no quede colgada.
        return new Response('', { status: 504, statusText: 'Offline' });
    }
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request).then((response) => {
        if (response && response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => cached || new Response('', { status: 504, statusText: 'Offline' }));

    return cached || fetchPromise;
}

function isPublicPage(pathname) {
    return (
        pathname === '/'
        || pathname === '/login'
        || pathname === '/register'
        || pathname.startsWith('/password/reset')
        || pathname.startsWith('/invitation/')
    );
}

async function showClientNotification(data) {
    if (self.Notification && self.Notification.permission !== 'granted') {
        return;
    }

    await self.registration.showNotification(data.title || 'ClientFlow', {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: data.tag || 'clientflow-client',
        data: { url: data.url || '/' },
    });
}
