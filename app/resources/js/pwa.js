/**
 * Registro del service worker, prompt de instalacion y polling
 * de notificaciones client-side para la PWA de ClientFlow.
 *
 * Comportamiento:
 * 1. Registra `/sw.js` con scope `/` en todas las paginas.
 * 2. Captura el evento `beforeinstallprompt` y lo expone
 *    mediante un custom event `pwa-install-available` para que
 *    el partial `pwa-install-prompt` lo muestre como banner.
 * 3. Si el usuario esta autenticado (body tiene clase
 *    `is-authenticated`), arranca un polling cada 30s contra
 *    `/api/notifications/unread-count` y, cuando detecta
 *    nuevos mensajes o tareas, manda un `postMessage` al SW
 *    para que muestre una notificacion del sistema.
 * 4. Permanece como unico punto de entrada para todas las
 *    funcionalidades PWA; el SW y la UI escuchan por custom
 *    events.
 */

const INSTALL_DISMISSED_KEY = 'pwa-install-dismissed';
const POLL_INTERVAL_MS = 30 * 1000;

(function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .catch((error) => {
                console.warn('[PWA] Error registrando el service worker:', error);
            });
    });
})();

(function setupInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (event) => {
        if (localStorage.getItem(INSTALL_DISMISSED_KEY) === '1') {
            return;
        }

        event.preventDefault();
        window.__pwaInstallEvent = event;
        window.dispatchEvent(new CustomEvent('pwa-install-available'));
    });

    window.addEventListener('appinstalled', () => {
        window.__pwaInstallEvent = null;
        window.dispatchEvent(new CustomEvent('pwa-install-completed'));
        localStorage.removeItem(INSTALL_DISMISSED_KEY);
    });
})();

(function exposeInstallActions() {
    window.installPwa = function installPwa() {
        const event = window.__pwaInstallEvent;
        if (!event) {
            return;
        }
        event.prompt();
        event.userChoice.then(() => {
            window.__pwaInstallEvent = null;
            window.dispatchEvent(new CustomEvent('pwa-install-completed'));
        });
    };

    window.dismissPwaInstall = function dismissPwaInstall() {
        localStorage.setItem(INSTALL_DISMISSED_KEY, '1');
        window.__pwaInstallEvent = null;
        window.dispatchEvent(new CustomEvent('pwa-install-dismissed'));
    };
})();

(function setupClientSideNotifications() {
    if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        return;
    }

    if (document.body && !document.body.classList.contains('is-authenticated')) {
        return;
    }

    let lastSnapshot = { messages: 0, tasks: 0, total: 0 };

    async function poll() {
        try {
            const response = await fetch('/api/notifications/unread-count', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const snapshot = {
                messages: Number(data.messages || 0),
                tasks: Number(data.tasks || 0),
                total: Number(data.total || 0),
            };

            if (snapshot.messages > lastSnapshot.messages) {
                const diff = snapshot.messages - lastSnapshot.messages;
                postNotification({
                    type: 'new-message',
                    title: 'Nuevo mensaje',
                    body: diff === 1
                        ? 'Tienes un mensaje nuevo en el chat de un proyecto.'
                        : `Tienes ${diff} mensajes nuevos en el chat.`,
                    url: data.messages_url || '/portal/tablero',
                    tag: 'clientflow-new-message',
                });
            }

            if (snapshot.tasks > lastSnapshot.tasks) {
                const diff = snapshot.tasks - lastSnapshot.tasks;
                postNotification({
                    type: 'new-task',
                    title: 'Tarea asignada',
                    body: diff === 1
                        ? 'Te han asignado una tarea nueva.'
                        : `Tienes ${diff} tareas nuevas.`,
                    url: data.tasks_url || '/portal/tablero',
                    tag: 'clientflow-new-task',
                });
            }

            lastSnapshot = snapshot;
        } catch (error) {
            // Sin red o error transitorio: ignoramos para no
            // spamear la consola. El siguiente poll reintenta.
        }
    }

    function postNotification(payload) {
        navigator.serviceWorker.ready.then((registration) => {
            if (registration.active) {
                registration.active.postMessage(payload);
            }
        });
    }

    if (Notification.permission === 'default') {
        Notification.requestPermission().catch(() => null);
    }

    setTimeout(poll, 5000);
    setInterval(poll, POLL_INTERVAL_MS);
})();
