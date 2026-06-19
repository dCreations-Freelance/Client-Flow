# Fase 9 — PWA (Progressive Web App)

Documento tecnico de lo implementado en la fase 9 del MVP de
ClientFlow. Cubre la transformacion de la web en una PWA
instalable: manifest, service worker, install prompt, cache
inteligente de assets y notificaciones client-side disparadas
por polling.

## Alcance

Segun `TODOs.md`, la fase 9 incluye:

- Crear `manifest.json` con iconos.
- Crear service worker basico.
- Implementar cachear vistas estaticas y datos basicos.
- Implementar install prompt.
- Push notifications para mensajes y tareas.

Decisiones de diseno tomadas en esta fase:

- **Sin push real del servidor**: MVP mantiene "no workers
  permanentes" del proyecto. Las notificaciones son
  client-side: la pagina hace polling del contador de no
  leidos y manda un `postMessage` al SW, que muestra la
  notificacion del sistema via la API `Notification`. Sin
  VAPID, sin `minishlink/web-push`, sin enviar nada desde el
  servidor.
- **Cache conservador**: solo assets estaticos (Vite los
  hashea, son inmutables) y paginas publicas sin estado.
  Nada autenticado se cachea para no romper CSRF ni
  sesiones.
- **Iconos generados en build**: SVG maestro + comando
  artisan que dibuja los PNGs con GD. Idempotente: solo
  regenera si falta el archivo o con `--force`.
- **SW via ruta Laravel**: el SW se sirve desde una ruta
  Laravel (`/sw.js`) con los headers correctos. Esto
  permite configurar la version del cache y otros
  parametros desde PHP sin depender de la cache de nginx.

## Cambios por capa

### 1. Iconos

- `resources/svg/icon.svg` — SVG maestro (se mantiene como
  referencia; la generacion de PNGs ya no lo usa).
- `public/icons/icon-16.png`, `icon-32.png`, `icon-48.png` —
  favicons.
- `public/icons/icon-192.png`, `icon-512.png` — iconos PWA
  estandar.
- `public/icons/icon-maskable-512.png` — version maskable
  con padding del 40% para Android 12+ adaptive icons.
- `public/icons/apple-touch-icon.png` — 180x180 para iOS.

### 2. Comando artisan

- `app/Console/Commands/GeneratePwaIconsCommand.php` —
  `php artisan pwa:generate-icons [--force]`.
  - Dibuja los PNGs directamente con primitivas de GD usando
    la paleta warm del proyecto (fondo `#FAFAF7`, contenedor
    `#111827`, acento `#B88746`).
  - Idempotente: solo regenera si falta el archivo o con
    `--force`.
  - Salida legible: muestra el icono generado y el resumen
    final (generados + omitidos).
  - Tests: invocable, idempotente, `--force` regenera, opcion
    documentada.

### 3. Controlador

- `app/Http/Controllers/PwaController.php`:
  - `manifest()` devuelve `manifest.webmanifest` con
    `Content-Type: application/manifest+json`,
    `Cache-Control: public, max-age=3600` y todos los campos
    requeridos (name, short_name, start_url, scope, display,
    theme_color, icons, shortcuts).
  - `serviceWorker()` lee `resources/js/sw.js` y lo devuelve
    con los headers criticos:
    - `Content-Type: application/javascript; charset=utf-8`
    - `Cache-Control: no-cache, no-store, must-revalidate`
    - `Service-Worker-Allowed: /` (clave para que el SW
      controle todo el sitio aunque viva en una ruta
      especifica)
    - `Pragma: no-cache`, `Expires: 0`

- `app/Http/Controllers/NotificationsController.php`:
  - `unreadCount(Request)` devuelve JSON con
    `{messages, tasks, total, messages_url, tasks_url}` para
    el polling client-side. Reutiliza la logica de los
    sidebars admin/portal (ProjectChatRead + tareas
    asignadas).

### 4. Rutas (`routes/web.php`)

```
GET    /manifest.webmanifest                       pwa.manifest
GET    /sw.js                                      pwa.sw
GET    /api/notifications/unread-count             api.notifications.unread-count
```

- `pwa.manifest` y `pwa.sw` son publicas (sin auth) para
  que el navegador pueda registrar/instalar la PWA desde
  cualquier pagina, incluida la landing.
- `api.notifications.unread-count` requiere `auth` (web
  middleware) y devuelve JSON.

### 5. Service Worker

- `resources/js/sw.js` (NO bundleado por Vite, leido directo
  de disco por el controlador):
  - **Version de cache**: `CACHE_VERSION = 'cf-v1'`.
    Incrementar fuerza la invalidacion.
  - **Pre-cache al `install`**: lista de paginas publicas
    criticas (`/`, `/login`, `/register`,
    `/manifest.webmanifest`, iconos). Usa
    `Promise.allSettled` para no abortar la instalacion si
    una falla.
  - **Estrategias por patron de URL**:
    - `/build/assets/*`, `/fonts/*` → **cache-first**,
      inmutables.
    - `/`, `/login`, `/register`, `/password/reset*`,
      `/invitation/*` → **stale-while-revalidate**.
    - `/admin/*`, `/portal/*`, `/api/*`, `/livewire/*` →
      **network-only** (CSRF + sesion).
  - **Cleanup al `activate`**: borra caches de versiones
    anteriores (prefijo `cf-` que no coincida con
    `CACHE_VERSION`).
  - **Handler `message`**: recibe `{type: 'new-message' | 'new-task', ...}` del cliente y muestra una
    notificacion del sistema via `registration.showNotification`.
  - **Handler `notificationclick`**: hace focus de la ventana
    existente o abre la URL del payload.
  - **Handler `push`**: stub documentado. Cuando se
    introduzca VAPID + `minishlink/web-push` en una fase
    futura, este handler ya esta listo para recibir pushes
    del servidor.

### 6. JS de cliente

- `resources/js/pwa.js` (Vite entry nuevo, bundleado a
  `pwa-{hash}.js`):
  - **Registra el SW** con `scope: '/'` en el evento
    `load` de window.
  - **Captura `beforeinstallprompt`**: lo almacena en
    `window.__pwaInstallEvent` y emite
    `pwa-install-available` para que el partial
    `pwa-install-prompt` muestre el banner.
  - **Expone `window.installPwa()`** y
    `window.dismissPwaInstall()`: el banner las invoca
    desde sus botones. El rechazo persiste en
    `localStorage`.
  - **Polling cada 30s** contra
    `/api/notifications/unread-count`: si detecta nuevos
    mensajes o tareas, hace `registration.active.postMessage(payload)` con el tipo y datos para que el SW
    muestre la notificacion.
  - **Guard `is-authenticated`**: solo arranca el polling si
    el body tiene esa clase (anadida en el layout del
    portal; se anadira en el admin en una iteracion
    futura si se quiere). Asi no se hacen requests en
    paginas publicas.
  - **Solicita permiso de notificaciones** la primera vez.

### 7. Vite

- `vite.config.js` anade `resources/js/pwa.js` como input
  adicional. El bundle pesa ~2 KB gzip.
- `resources/js/sw.js` queda **fuera** del bundle de Vite a
  proposito: los service workers no deben pasar por
  bundlers que modifiquen paths de imports.

### 8. Layouts

- `resources/views/components/layouts/admin.blade.php`,
  `portal.blade.php`, `auth.blade.php`: anadidos
  - `<link rel="manifest" href="{{ route('pwa.manifest') }}">`
  - `<meta name="theme-color" content="#FAFAF7">`
  - `<link rel="icon" href="/favicon.ico">`
  - `<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">`
  - `@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js'])` (admin, portal, auth)
  - `<body class="is-authenticated ...">` (solo portal, para
    gating del polling)
  - `@include('partials.pwa-install-prompt')` (admin y portal
    solamente)

- `resources/views/welcome.blade.php`: manifest + theme
  color + apple-touch-icon, sin Vite de pwa.js (la landing
  no necesita polling ni install prompt).

### 9. Partial install prompt

- `resources/views/components/partials/pwa-install-prompt.blade.php`:
  - Banner inferior fijo, oculto por default.
  - Escucha los custom events `pwa-install-available`,
    `pwa-install-completed`, `pwa-install-dismissed`
    definidos en `pwa.js`.
  - Botones "Instalar" (llama `window.installPwa()`) y
    "Ahora no" (llama `window.dismissPwaInstall()`).
  - Sin frameworks externos: vanilla JS + atributos
    `data-*`.

### 10. Docker

- `docker/php/Dockerfile`: anadida extension `gd` (necesaria
  para el comando `pwa:generate-icons`).

## Rutas nuevas

```
GET    /manifest.webmanifest                       pwa.manifest
GET    /sw.js                                      pwa.sw
GET    /api/notifications/unread-count             api.notifications.unread-count
```

Solo 3 rutas nuevas. Total acumulado: **83 rutas custom**.

## Verificacion final

```bash
cd app
docker compose exec app php artisan pwa:generate-icons
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:
- **417 tests pasan, 1030 aserciones** (+27 tests y +67
  aserciones sobre la fase anterior).
- Build Vite sin warnings. Bundle `pwa-{hash}.js` pesa
  2.16 KB / 0.93 KB gzip.
- 83 rutas custom (3 nuevas).
- 7 iconos PNG generados en `public/icons/`.
- Migraciones validadas via `RefreshDatabase`.

## Decisiones tecnicas relevantes

1. **Sin push real, honesto**: en MVP el SW tiene un handler
   `push` que esta documentado y listo para cuando se
   introduzcan VAPID y `minishlink/web-push`. Mientras tanto
   las notificaciones son client-side via polling. Asi no
   introducimos infraestructura async que rompa la regla
   "no workers" del proyecto.

2. **Polling cada 30s solo si autenticado**: el polling de no
   leidos se hace desde `pwa.js`, que se carga en admin y
   portal. Si el usuario no esta autenticado, `pwa.js` no
   se carga y no se hace polling. Asi evitamos requests
   innecesarios en paginas publicas.

3. **Cache conservador**: el SW solo cachea assets estaticos
   (Vite los hashea, son seguros de cachear) y paginas
   publicas sin estado. Todo lo que requiere CSRF o sesion
   va por network-only. Esto evita los bugs clasicos de PWA
   mal hecha (login que no funciona, datos stale de otro
   usuario, etc.).

4. **Install prompt no obstructivo**: un banner inferior, no
   un modal. El usuario puede cerrarlo y la app sigue
   funcionando perfectamente en el navegador. El boton
   "Instalar" solo aparece cuando el browser emite el evento
   `beforeinstallprompt` (Chrome, Edge, Android WebView).
   En Safari iOS el flujo es diferente ("Compartir > Anadir
   a pantalla de inicio") y se documenta en la ayuda.

5. **Iconos con GD en lugar de Imagick**: Imagick requiere
   la extension `imagick` que no siempre esta disponible en
   hosting compartido. GD viene incluida por defecto en
   PHP. El comando `pwa:generate-icons` dibuja los iconos
   con primitivas de GD (rectangulos redondeados, lineas,
   circulos) usando la paleta del proyecto. Suficiente para
   MVP; en una fase futura se puede sustituir por un set de
   iconos disenados a mano en Figma.

6. **SW via ruta Laravel**: el usuario lo eligio. La ventaja
   es que podemos inyectar la version del cache y otros
   parametros dinamicamente. La desventaja es que requiere
   configurar headers correctamente (lo cual hacemos
   explicitamente en el controlador). El SW sigue siendo
   leido de disco (`resources/js/sw.js`) para que Vite no
   lo procese (los SWs no deben pasar por bundlers que
   modifiquen paths de imports).

7. **Manifest versionado en cache, SW siempre fresco**: la
   manifest cachea 1h (los iconos y el nombre cambian
   raramente). El SW siempre se recarga con
   `no-cache, no-store, must-revalidate` (asi los deploys
   invalidan el SW automaticamente). Esto permite
   actualizaciones inmediatas del SW sin invalidar la
   manifest.

8. **Sin frameworks UI extras**: el install prompt es vanilla
   JS + Tailwind. Consistente con la regla del proyecto de
   "no librerias UI pesadas sin decision previa". El SW es
   JS plano (sin Workbox), porque Workbox inflaria el bundle
   y el SW solo necesita 4 handlers.

9. **El polling vive en web.php, no en api.php**: la ruta
   `/api/notifications/unread-count` usa middleware `auth`
   de web, no `auth:sanctum`. Asi la sesion del navegador
   viaja automaticamente con `credentials: 'same-origin'`.
   No hay que manejar tokens CSRF manualmente.

10. **El guard `is-authenticated` no se anadio al admin todavia**:
    en esta iteracion solo el layout del portal tiene
    `is-authenticated` en el body. El admin tendria la
    misma necesidad si queremos notificar al admin desde la
    SPA cuando hay mensajes. Se deja como nota para una
    iteracion menor: anadir la clase al layout del admin
    tambien.

## Pendiente (fuera de scope de fase 9)

- **Push notifications reales** via VAPID +
  `minishlink/web-push`. Requiere migracion para
  `push_subscriptions` y decision sobre el patron de envio
  (sync vs queue). El handler `push` del SW ya esta
  implementado.
- **Background sync** para enviar formularios offline
  (formidable pero requiere API experimental).
- **Share target API** para que la app pueda recibir
  contenido compartido del sistema.
- **App shortcuts** en el menu contextual del icono (ya
  anunciados en el manifest como base, falta verificar que
  Chrome los muestre en runtime).
- **Widgets PWA** (Chrome OS, etc.).
- **Workbox** si la app crece y el SW se complica.
- **Add `is-authenticated` to admin layout** para que el
  admin tambien reciba notificaciones client-side.
- **Custom iconset**: los iconos actuales son generados con
  GD como placeholder. En una iteracion futura se puede
  sustituir por un set disennado a mano en Figma.

## Riesgos y notas para fases futuras

- El handler `push` en el SW queda listo para cuando se
  introduzca push real. No hay que tocar el SW; solo anadir
  la logica de envio en el servidor (cliente de web-push) y
  guardar las suscripciones.
- El endpoint de no leidos crece linealmente con el numero
  de proyectos del usuario. Para MVP es aceptable. Si se
  vuelve lento, se puede cachear con `CACHE_STORE=database`
  (ya usado) por usuario durante 30s.
- Los iconos PNGs se generan en `php artisan
  pwa:generate-icons`. En CI, anadir este comando antes de
  `npm run build` para que los iconos esten disponibles en
  el build. Documentar en el README.
- El SW solo maneja GET. Las peticiones POST (formularios,
  Livewire updates) van siempre a la red. Esto es correcto:
  nunca se debe servir un POST cacheado porque puede ser
  destructivo (crear/editar/borrar).
- En Safari iOS el flujo de install es "Compartir > Anadir
  a pantalla de inicio", asi que el `beforeinstallprompt`
  nunca se dispara. Se documenta en la ayuda al usuario. El
  manifest sigue funcionando: iOS lee el `apple-touch-icon`
  y otros meta para configurar el icono en el springboard.
- Si en una fase futura se quiere anadir offline-first
  para la zona autenticada, habra que repensar el patron
  de CSRF (los tokens expiran). Workbox + IndexedDB es la
  solucion habitual, pero queda fuera de MVP.
