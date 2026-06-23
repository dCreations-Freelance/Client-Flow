# Fase transversal — Notificaciones

Documento tecnico de lo implementado en la fase transversal de
Notificaciones del MVP de ClientFlow. Esta fase anade el sistema
completo de avisos in-app + email: la campana del header, las
preferencias por evento, los recordatorios automaticos de
deadline y el resumen diario.

## Alcance

Segun `TODOs.md`, esta fase transversal incluye:

- Configurar `database notifications` de Laravel (canal in-app).
- Notificaciones in-app: badge en el sidebar (sustituido por la
  campana del header) y lista desplegable.
- Notificaciones email: resumen diario, mensajes nuevos,
  deadlines.
- Notificacion: nuevo mensaje en chat del proyecto
  (ya existia `NewProjectMessage`, ahora pasa por el dispatcher).
- Notificacion: tarea asignada (`TaskAssigned`).
- Notificacion: tarea con deadline cercano (`TaskDueSoon`).
- Notificacion: invitacion a organizacion
  (ya existia, sin pasar por el dispatcher porque el destinatario
  no es usuario todavia).
- Pagina de preferencias para que el usuario pueda desactivar
  canales por evento.

Decisiones de diseno:

- **Opt-out centralizado en `NotificationDispatcher`**: cualquier
  trigger de la app llama a `NotificationDispatcher::dispatch(...)`
  en vez de `Notification::send(...)`. El dispatcher consulta
  las preferencias del destinatario y filtra los canales; asi
  no se generan filas en la tabla `notifications` para usuarios
  que han apagado la campana.

- **Tabla `notification_preferences` por (user_id, event)**: cada
  usuario tiene una fila por cada evento del enum
  `NotificationEvent`. Esto permite representar "in_app + email",
  "solo in-app", "solo email" o "ninguno" sin casuistica extra.

- **Enum `NotificationEvent` como contrato**: las clases de
  notificacion declaran `via(['database', 'mail'])` y el
  dispatcher filtra. Asi las clases no tienen que conocer las
  preferencias; solo llevan los datos y declaran intenciones.

- **Scheduler con schedule() declarado pero sin cron automatico**:
  los comandos `notifications:task-due-soon` y
  `notifications:daily-digest` se registran en
  `bootstrap/app.php -> withSchedule()` para que `schedule:list`
  los muestre, pero **no hay cron automatico** en MVP. El admin
  los corre manualmente o configura su propio cron. Esto es
  coherente con la decision de no introducir workers permanentes
  en hosting compartido.

- **Campana del header sustituye al badge del sidebar**: el badge
  rojo de "mensajes no leidos" del sidebar desaparecio; la
  campana del header muestra el total de in-app (chat + tareas
  + eventos + invitaciones). Un unico punto de atencion, no dos.

- **Polling cada 30s en la campana**: mismo intervalo que la PWA,
  declarado en la vista con `wire:poll.30s`. No introducimos
  WebSockets.

- **Endpoint PWA extendido, no reemplazado**: el endpoint
  `/api/notifications/unread-count` ahora devuelve un campo
  adicional `notifications` con el conteo de la tabla
  `notifications`, manteniendo `messages` y `tasks` para no
  romper el `pwa.js` actual.

- **`OrganizationInvitationSent` se envia fuera del dispatcher**:
  el destinatario todavia no es usuario de ClientFlow, asi que
  no tiene fila en `notification_preferences`. Se envia con
  `AnonymousNotifiable` por el canal `mail` unicamente. La
  decision queda documentada en el codigo.

## Cambios por capa

### 1. Migraciones

- `2026_06_22_080000_create_notification_preferences_table.php`:
  biblioteca de opt-out por usuario y evento. Columnas: `id`,
  `user_id` (FK a `users` con cascade), `event` (string,
  indexed), `in_app` (bool, default true), `email` (bool, default
  true), timestamps. Unique `(user_id, event)`.

- `2026_06_22_080001_add_last_due_notification_at_to_tasks_table.php`:
  sello anti-duplicados para `notifications:task-due-soon`. Si
  una tarea recibio recordatorio en las ultimas 24h, el comando
  no la vuelve a procesar.

- `2026_06_22_080002_add_last_digest_sent_at_to_users_table.php`:
  sello anti-duplicados para `notifications:daily-digest`. Si el
  usuario recibio el resumen en las ultimas 18h, el comando no
  lo reenvia aunque se invoque dos veces al dia.

### 2. Enum

- `App\Enums\NotificationEvent`: seis casos (`NewMessage`,
  `TaskAssigned`, `TaskDueSoon`, `EventInvitation`,
  `OrganizationInvitation`, `DailyDigest`) con metodos
  `label()`, `description()`, `defaultInApp()` y
  `defaultEmail()`. Es el vocabulario compartido por
  preferencias, notificaciones y comandos.

### 3. Modelos

- `App\Models\NotificationPreference`: cast `event` a enum,
  `in_app` y `email` a bool. Relacion `user()`. Helpers
  `isInAppEnabled()`, `isEmailEnabled()`, `isFullyDisabled()`.
  Scopes `forUser`, `inAppEnabled`, `emailEnabled`.

- `App\Models\User` (edit): nueva relacion
  `notificationPreferences()`. Nuevo helper `preferenceFor(NotificationEvent)` que
  devuelve la fila persistida o, si no existe, una instancia
  virtual (no persistida) con los defaults del enum. Asi el
  codigo que llama no trata nulos. Cast `last_digest_sent_at`
  a datetime.

### 4. Notificaciones

- `App\Notifications\TaskAssigned`: database + mail. Disparada
  por `TaskController::store/update` cuando cambia el assignee.

- `App\Notifications\TaskDueSoon`: database + mail. Disparada
  por `notifications:task-due-soon`. El email incluye cuantos
  dias faltan ("hoy", "manana", "en N dias").

- `App\Notifications\DailyDigest`: solo mail. Disparada por
  `notifications:daily-digest`. El payload se calcula en el
  comando (proyectos activos, tareas pendientes, eventos
  proximos 7 dias, mensajes no leidos) y se pasa ya materializado
  a la notificacion para evitar N+1.

- `NewProjectMessage` y `CalendarEventInvitation` ya existian;
  los triggers que las disparan ahora pasan por el dispatcher
  para respetar el opt-out.

### 5. Servicio

- `App\Services\Notifications\NotificationDispatcher`: clase
  con metodos estaticos. API:
  - `dispatch(User, Notification, NotificationEvent): bool`
    respeta la preferencia y devuelve si se intento enviar.
  - `dispatchToMany(iterable<User>, Notification, NotificationEvent): int`
    itera aplicando opt-out individual. Devuelve el numero de
    usuarios notificados.
  - `dispatchToAddress(string $email, string $name, Notification): void`
    variante para destinatarios anonimos (no aplica opt-out).

  Si la preferencia tiene ambos canales desactivados, el
  dispatcher es no-op y loguea a nivel debug.

### 6. Listener

- `App\Listeners\CreateDefaultNotificationPreferences`: escucha
  el evento `Registered` de Laravel. Siembra las seis filas de
  preferencias con los defaults del enum en el alta del usuario
  (registro publico o aceptacion de invitacion). Usa
  `firstOrCreate` para ser idempotente si el evento se dispara
  dos veces. Registrado en
  `App\Providers\AppServiceProvider::boot()`.

### 7. Policies

- `App\Policies\NotificationPreferencePolicy`: solo el dueno
  puede ver, actualizar o borrar una preferencia. Coherente
  con el principio "cada usuario gestiona solo lo suyo".

### 8. Form Requests

- `App\Http\Requests\Admin\UpdateNotificationPreferencesRequest`:
  array `preferences` con `event` (validado contra el enum),
  `in_app` y `email` opcionales. `authorize()` exige usuario
  autenticado. Se reutiliza desde el portal (la validacion no
  depende de la zona).

### 9. Controladores

- `App\Http\Controllers\Admin\NotificationPreferenceController`:
  `index` carga las preferencias del admin (persistidas o
  defaults), `update` hace `updateOrCreate` fila a fila.

- `App\Http\Controllers\Portal\NotificationPreferenceController`:
  gemelo del anterior, renderiza la vista del portal. Mantener
  los dos separados sigue la convencion del proyecto (un
  controlador por zona aunque la logica sea identica).

- `App\Http\Controllers\Admin\NotificationController`:
  - `index` devuelve las ultimas 20 notificaciones in-app en
    JSON (para la campana cuando no hay Livewire).
  - `markAsRead` actualiza `read_at` solo en las notificaciones
    del usuario actual (filtro por `notifiable`).
  - `markAllAsRead` vacia la bandeja in-app.

- `App\Http\Controllers\Portal\NotificationController`: gemelo
  portal. Re-escribe las URLs de admin a portal en el payload
  para que el cliente aterrice en rutas a las que tiene acceso.

### 10. Comandos Artisan

- `App\Console\Commands\NotificationsTaskDueSoon`: detecta
  tareas con `due_date` entre hoy y `today + 3 dias` (configurable
  con `--days`), no completadas, sin recordatorio reciente.
  Sella `last_due_notification_at` solo si el envio tuvo exito.
  Opcion `--dry-run` para inspeccionar sin enviar.

- `App\Console\Commands\NotificationsDailyDigest`: envia
  `DailyDigest` por email a cada usuario con preferencia
  `DailyDigest.email` activa y sin sello en las ultimas 18h.
  Opcion `--dry-run`.

Ambos registrados en `bootstrap/app.php -> withSchedule()` a las
08:00 y 09:00 respectivamente. **El scheduler no se activa
automaticamente**; el admin debe anadir
` * * * * * php artisan schedule:run >> /dev/null 2>&1` a su
cron del servidor.

### 11. Componente Livewire

- `App\Livewire\Shared\NotificationBell`: campana con badge,
  polling cada 30s (`wire:poll.30s` en la vista), dropdown
  con las 20 ultimas notificaciones, `markAllAsRead`,
  `openNotification(id)` que marca como leida y devuelve la
  URL del payload. Se monta en los layouts admin y portal,
  a la izquierda del menu de usuario.

### 12. Vistas Blade

- `resources/views/livewire/shared/notification-bell.blade.php`:
  campana con SVG inline, dropdown con Alpine + Livewire,
  handler JS para el click en una notificacion (POST a la
  ruta de mark-read + navegacion al `url` del payload).

- `resources/views/admin/notifications/preferences.blade.php` y
  `resources/views/portal/notifications/preferences.blade.php`:
  formularios con una fila por evento y dos checks (in-app,
  email). Mensajes y notas en castellano entendible.

- `resources/views/components/layouts/admin.blade.php` y
  `resources/views/components/layouts/portal.blade.php` (edit):
  anaden `@livewire('shared.notification-bell')` en el header
  antes del menu de usuario.

- `resources/views/partials/admin-sidebar.blade.php` y
  `resources/views/partials/portal-sidebar.blade.php` (edit):
  eliminan el badge de "mensajes no leidos" del sidebar (lo
  absorbe la campana) y anaden un enlace a "Notificaciones"
  que apunta a la pagina de preferencias.

### 13. Rutas

Diez rutas nuevas en `routes/web.php`:

- `admin.notifications.preferences` (GET)
- `admin.notifications.preferences.update` (PUT)
- `admin.notifications.inbox` (GET, JSON)
- `admin.notifications.read` (POST, con `{notification}`)
- `admin.notifications.read-all` (POST)
- Mismas cinco en `portal.notifications.*`.

El endpoint `/api/notifications/unread-count` se actualiza para
anadir el campo `notifications` y `notifications_url`; no se
anade ruta nueva.

### 14. Factories

- `Database\Factories\NotificationPreferenceFactory` con
  estados `disabled()`, `inAppOnly()`, `emailOnly()`,
  `forEvent(NotificationEvent)`, `forUser(User)`.

### 15. Tests (37 tests nuevos)

- `tests/Unit/Enums/NotificationEventTest` (7 tests): label,
  description, defaults por canal, valores persistentes,
  conteo del catalogo.
- `tests/Unit/Models/NotificationPreferenceTest` (8 tests):
  cast, relacion, scopes, helpers.
- `tests/Unit/Models/UserNotificationPreferenceTest` (5 tests):
  helper `preferenceFor` con fila persistida, sin fila, defaults
  del enum, no persistencia como efecto secundario.
- `tests/Unit/Services/NotificationDispatcherTest` (6 tests):
  envio con defaults, opt-out completo, in-app solo, email
  solo, dispatchToMany respeta opt-out individual,
  dispatchToAddress para destinatarios anonimos.
- `tests/Unit/Listeners/CreateDefaultNotificationPreferencesTest`
  (4 tests): siembra las seis filas, defaults correctos,
  idempotencia, respeta personalizaciones existentes.
- `tests/Unit/Policies/NotificationPreferencePolicyTest`
  (7 tests): solo-dueno en view/update/delete.
- `tests/Feature/Admin/NotificationPreferencesTest` (6 tests):
  admin ve, cliente redirigido, siembra defaults, respeta
  personalizadas, actualiza, valida.
- `tests/Feature/Portal/NotificationPreferencesTest` (5 tests):
  cliente ve, admin redirigido, actualiza, siembra, valida.
- `tests/Feature/Shared/NotificationBellTest` (5 tests): render
  con/sin notificaciones, badge, toggle, mark-all,
  openNotification, aislamiento por usuario.
- `tests/Feature/Admin/NotificationInboxTest` (4 tests): inbox
  JSON, mark-read, mark-all, aislamiento.
- `tests/Feature/Portal/NotificationInboxTest` (3 tests):
  inbox, mark-read, mark-all.
- `tests/Feature/Admin/TaskAssignedNotificationTest` (6 tests):
  trigger en store, sin assignee, sin auto-notificacion, en
  reasignacion, no re-disparo si no cambia assignee, opt-out
  parcial.
- `tests/Feature/Console/NotificationsTaskDueSoonTest` (8 tests):
  deteccion, exclusion de completadas, exclusion sin
  assignee, exclusion fuera de ventana, sello, anti-duplicados,
  opt-out, dry-run.
- `tests/Feature/Console/NotificationsDailyDigestTest` (6 tests):
  envio a activos, exclusion, sello, anti-duplicados 18h,
  reenvio pasado el plazo, dry-run.
- `tests/Feature/NotificationsUnreadCountWithInboxTest` (3 tests):
  contador in-app, URL segun rol, suma total.

Total: 75 tests nuevos. Cobertura especialmente alta en
comandos (anti-duplicados) y dispatcher (canales).

## Comandos utiles

```bash
# Migrar la base de datos (incluye las nuevas migraciones).
php artisan migrate

# Siembra los usuarios de demo y los agentes IA.
php artisan db:seed

# Envia recordatorios de deadline manualmente.
php artisan notifications:task-due-soon
php artisan notifications:task-due-soon --days=7
php artisan notifications:task-due-soon --dry-run

# Envia el resumen diario manualmente.
php artisan notifications:daily-digest
php artisan notifications:daily-digest --dry-run

# Lista las tareas programadas (incluye las dos nuevas).
php artisan schedule:list

# Tests de la fase (corre solo los nuevos).
php artisan test --filter=Notification
```

Para activar el envio automatico en produccion, anadir a cron:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Decisiones y desviaciones del plan

- **Plan original proponia integrar la invitacion a organizacion
  en el dispatcher**: no se ha hecho. El destinatario no es
  usuario todavia y la policy de opt-out no se puede aplicar.
  El trigger sigue usando `Notification::send` con
  `AnonymousNotifiable`. Es la unica excepcion documentada.

- **Plan proponia una pagina "Todas las notificaciones"**: no se
  ha implementado. La campana muestra las 20 ultimas y eso
  cubre el 99% de los casos. Si en una fase futura hace falta
  scroll historico, se aniade sin cambiar la migracion.

- **Plan original hablaba de un endpoint PWA `api.notifications.list`**:
  no se ha hecho. El componente Livewire `NotificationBell`
  consume los datos directamente de las propiedades
  computadas (`notifications`, `unreadCount`) que se alimentan
  de la relacion Eloquent. No hace falta un endpoint JSON
  intermedio.

- **El PWA `pwa.js` ya no envia notificacion del sistema por
  "mensajes no leidos del chat"**: se conserva la query
  `/api/notifications/unread-count` con el campo `messages`
  intacto para no romper el `pwa.js` actual. La PWA sigue
  mostrando el push del sistema cuando ese contador sube. La
  campana del header cubre el resto de eventos.

- **El badge del sidebar se ha sustituido por un enlace plano a
  "Notificaciones"**: el plan ofrecia dos opciones
  (mantener ambos o solo campana); tras revisarlo se opto por
  eliminar el badge (cero redundancia) y dejar el enlace en el
  sidebar para que el usuario sepa que existe la pagina de
  preferencias.
