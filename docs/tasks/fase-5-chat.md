# Fase 5 — Chat por proyecto (texto + system messages + polling + notificaciones)

Documento tecnico de lo implementado en la fase 5 del MVP de
ClientFlow. Cubre el ciclo de vida del chat de un proyecto:
mensajes de texto, mensajes automaticos del sistema, polling
Livewire cada 5s, tracking de no leidos, e integracion con el
sistema de notificaciones de Laravel (in-app + email).

## Alcance

Segun `TODOs.md`, la fase 5 incluye:

- Migracion `project_messages`.
- Enum `MessageType` (`text`, `system`, `file`).
- Modelo `ProjectMessage` con relaciones.
- Vista chat por proyecto (admin).
- Vista chat por proyecto (portal).
- Polling Livewire para mensajes nuevos (cada 5s).
- Generar mensajes de sistema automaticos (tarea creada, estado
  cambiado, etc.).
- Notificaciones in-app por mensajes nuevos.
- Notificaciones email por mensajes nuevos.
- Indicador de mensajes no leidos por proyecto en sidebar.

Ademas, anadido en esta fase:

- Tabla `project_chat_reads` para tracking eficiente de no leidos
  (un registro por usuario-proyecto con `last_read_message_id`).
- Tabla `notifications` nativa de Laravel (canal `database`).
- Servicio `ProjectActivityLogger` que centraliza el formato de
  los mensajes de sistema. Invocado desde `TaskController`,
  `TaskMoveController`, `ProjectArchiveController` y
  `ProjectDocumentController`.
- Componente Livewire compartido `Shared\ChatWindow` montado
  tanto desde el controlador admin como desde el portal. Misma
  UI, misma logica.
- Badges de no leidos en: header de detalle del proyecto (boton
  Chat), listado de proyectos y sidebar.
- Paginacion del historial con boton "Cargar mensajes anteriores"
  (saltos de 50).
- Auto-scroll al fondo del chat al recibir/enviar mensajes via
  `x-data` + `x-ref` de Alpine (solo en el componente Livewire,
  fuera de logica de negocio).

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_15_140000_create_project_messages_table.php` | Crea `project_messages` con FK a `projects` (cascade) y `users` (nullOnDelete, para autores de system), `content` text, `type` enum indexado, indice `(project_id, id)` para ordenamiento y no leidos. |
| `database/migrations/2026_06_15_140001_create_project_chat_reads_table.php` | Tracking de lectura: una fila por (project_id, user_id) con `last_read_message_id` y `last_email_sent_at` (este ultimo para futuro debounce). |
| `database/migrations/2026_06_15_140002_create_notifications_table.php` | Tabla nativa de Laravel para el canal `database` de notificaciones. Soporta la polimorfica `notifiable` (User, etc). |

Decisiones:
- `user_id` con `nullOnDelete`: un system message no tiene autor
  humano; si un usuario se borra, sus mensajes quedan con autor
  desconocido en lugar de eliminarse en cascada (que seria
  destructivo para el historial del proyecto).
- `last_read_message_id` con `nullOnDelete`: si se borra el
  mensaje, el marcador no debe invalidar al usuario.
- `last_email_sent_at` queda libre para implementar debounce
  (no en esta fase: lo difiere la seccion "Transversal:
  Notificaciones").

### 2. Enums

`app/Enums/MessageType.php`:

| Caso | Valor | Etiqueta | Color |
|---|---|---|---|
| `Text` | `text` | Texto | blue |
| `System` | `system` | Sistema | gray |
| `File` | `file` | Archivo | info |

Metodos: `label()`, `color()`, `isText()`, `isSystem()`, `isFile()`.
El caso `file` se reserva para una fase futura (subida de
archivos adjuntos); en esta fase solo se persisten `text` y
`system`.

### 3. Modelos

#### `ProjectMessage`

- `$fillable`: `project_id`, `user_id`, `content`, `type`.
- Casts: `type` -> enum.
- Relaciones: `project()` BelongsTo, `user()` BelongsTo.
- Scopes: `text()`, `system()`, `before($id)`, `after($id)`,
  `chronological()`, `recent($limit = 50)`.
- Helpers: `isText()`, `isSystem()`, `isFile()`,
  `isFromUserId($id)`.

#### `ProjectChatRead`

- `$fillable`: `project_id`, `user_id`,
  `last_read_message_id`, `last_email_sent_at`.
- Casts: `last_email_sent_at` -> datetime.
- Relaciones: `project()`, `user()`, `lastReadMessage()`.
- Helper estatico `markAsRead(Project, User, int): ?self`:
  upsert idempotente, no-op si `$lastMessageId <= 0`, no
  rebobina (solo actualiza si el id es estrictamente mayor).
- Metodo `unreadCount(): int` para badges.

### 4. Policies

`app/Policies/ProjectMessagePolicy.php`:

- `view(User, Project)`: delega en `ProjectPolicy::view` (admin o
  cliente que puede ver el proyecto).
- `viewMessage(User, ProjectMessage)`: idem, contra el proyecto
  del mensaje.
- `create(User, Project)`: delega en `ProjectPolicy::view`. Asi
  clientes que pueden ver el proyecto pueden enviar mensajes.
- `createSystem(User)`: siempre false. Los system messages solo
  los crea la app.

Importante: el controlador usa
`authorize('create', [ProjectMessage::class, $project])` para
forzar la policy correcta. Si se usara
`authorize('create', $project)`, Laravel inferiria
`ProjectPolicy::create` (que es admin-only) y los clientes no
podrian chatear. La doble pista [clase, modelo] es la
convencion correcta en Laravel para este caso.

### 5. Servicios

#### `App\Services\Activity\ProjectActivityLogger`

Centraliza el formato de los mensajes de sistema. Metodos:

- `taskCreated(Project, Task)`: "Nueva tarea creada: X (Tipo,
  Prioridad)."
- `taskCompleted(Project, Task, User $actor)`: "Actor completo
  la tarea X."
- `taskReopened(Project, Task, User $actor)`: "Actor re-abrio
  la tarea X."
- `taskMoved(Project, Task, BoardColumn $newColumn, User $actor)`:
  "Actor movio X a la columna Y."
- `projectArchived(Project, User $actor)`: "Actor archivo el
  proyecto."
- `projectUnarchived(Project, User $actor)`: "Actor desarchivo
  el proyecto."
- `documentPublished(Project, ProjectDocument, User $actor)`:
  retorna `null` si el documento no es publico, evitando
  notificar internamente sobre documentos privados.

Internamente todos los mensajes se crean con
`ProjectMessage::create([..., 'user_id' => null, 'type' => System])`
via el helper privado `log()`.

### 6. Notificaciones

`app/Notifications/NewProjectMessage.php`:

- Canales: `['database', 'mail']`.
- `toArray()`: payload con `project_id`, `project_name`,
  `message_id`, `sender_id`, `sender_name`, `content_preview`,
  `url`. Es la base del badge in-app y de los contadores.
- `toMail()`: saludo, preview del mensaje en una linea, boton
  "Ir al chat" con URL al chat del proyecto.
- `preview()`: helper privado que escapa HTML, colapsa espacios
  y trunca a 160 caracteres.
- `chatUrl()`: devuelve la ruta admin (los clientes acaban
  viendo el chat del portal por el middleware).

#### Disparador

El envio se hace desde tres puntos (todos con la misma logica):

1. `App\Livewire\Shared\ChatWindow::sendMessage` (envio via
   Livewire).
2. `App\Http\Controllers\Admin\ProjectMessageController::store`
   (envio HTTP admin).
3. `App\Http\Controllers\Portal\ProjectMessageController::store`
   (envio HTTP portal).

En los tres casos:

- Se crea el `ProjectMessage` con `type = text`.
- Se marca como leido para el emisor.
- Se calculan destinatarios = miembros del proyecto + miembros
  de la org, excluyendo al emisor.
- Se envia `NewProjectMessage` via `Notification::send` (canales
  `database` y `mail`).

### 7. Componente Livewire compartido

`App\Livewire\Shared\ChatWindow`:

Estado:
- `Project $project` (inyectado en mount).
- `User $user` (capturado en mount desde `Auth::user()`).
- `string $newMessage = ''` (input).
- `int $loadedCount = 50` (paginacion del historial).
- `bool $initialized = false` (anti-doble mark-as-read).

Metodos publicos:
- `mount(Project)`: autoriza `view` y captura el usuario.
- `getMessagesProperty`: accessor computed; carga los ultimos
  `$loadedCount` mensajes y los devuelve en orden cronologico.
  Livewire lo cachea entre renders.
- `getTotalMessagesProperty`: total de mensajes del proyecto, para
  decidir si mostrar "Cargar mensajes anteriores".
- `getUnreadCountProperty`: mensajes con id > last_read_message_id.
- `booted()`: hook de Livewire; se ejecuta al final del primer
  render. Marca como leidos los mensajes existentes.
- `refresh()`: llamado por el polling cada 5s. Re-renderiza +
  re-marca como leido (idempotente, no rebobina).
- `sendMessage()`: valida, crea el mensaje, lo marca como leido
  para el emisor, dispara notificaciones, dispatch del evento
  `chat-message-sent` (la vista usa Alpine para auto-scroll).
- `loadMore()`: incrementa `loadedCount` en 50 para paginar
  hacia atras.
- `render()`: pasa `messages`, `totalMessages`, `unreadCount`
  a la vista.

Privados:
- `markAsRead()`: upsert idempotente via
  `ProjectChatRead::markAsRead`.
- `resolveRecipients(int $senderId)`: union de miembros del
  proyecto + miembros de la org, excluyendo al emisor.

#### Vista

`resources/views/livewire/shared/chat-window.blade.php`:

- Contenedor con `x-data` (Alpine) y `wire:poll.5s="refresh"`.
- Header: nombre del proyecto, badge "X sin leer" si > 0,
  indicador "En vivo" verde.
- Lista de mensajes: `wire:key` por mensaje, render via
  partial `chat-message`.
- Boton "Cargar mensajes anteriores" si `totalMessages > loadedCount`.
- Empty state si no hay mensajes.
- Form con textarea + boton Enviar.
- `x-on:keydown.enter.prevent`: Enter envia, Shift+Enter
  salto de linea.
- `$wire.call('sendMessage')` se invoca desde el listener
  Alpine del keydown.

### 8. Partials

- `components/partials/chat-message.blade.php`: renderiza un
  mensaje. Mensaje de sistema: centrado, fondo gris claro,
  sin avatar. Mensaje propio: derecha, fondo azul. Mensaje
  ajeno: izquierda, fondo blanco con borde.
- `components/partials/chat-user-avatar.blade.php`: avatar con
  iniciales y color derivado de un hash determinista del
  nombre (estable entre renders).

### 9. Controladores

- `App\Http\Controllers\Admin\ProjectMessageController`:
  `index` (renderiza la vista) y `store` (POST HTTP). El POST
  resuelve destinatarios via `resolveRecipients` y envia
  notificacion.
- `App\Http\Controllers\Portal\ProjectMessageController`:
  idem, pero con la policy del portal. El cliente solo ve sus
  proyectos.

### 10. Vistas Blade

- `admin/projects/chat.blade.php`: layout admin + componente
  Livewire + breadcrumb.
- `portal/projects/chat.blade.php`: layout portal + componente
  Livewire + breadcrumb + descripcion de uso.

### 11. Rutas (`routes/web.php`)

```
GET    /admin/projects/{project}/chat          admin.projects.chat
POST   /admin/projects/{project}/messages     admin.projects.chat.store
GET    /portal/projects/{project}/chat         portal.projects.chat
POST   /portal/projects/{project}/messages     portal.projects.chat.store
```

Total: **4 rutas nuevas** (2 admin + 2 portal). Acumulado:
**69 rutas custom**.

### 12. Badges de no leidos

Los badges se calculan en cada render con un bucle por
proyecto. Para mantener el coste bajo, se hace:

- En el sidebar: una query para los `ProjectChatRead` del
  usuario, luego un `count` por proyecto. Para N proyectos del
  usuario son N+1 queries; aceptable para MVP.
- En el listado admin/portal: una query agregada en el
  controlador (`unreadCountsFor`) que devuelve
  `[project_id => count]`. La vista lo consulta con
  `$unreadByProject[$project->id] ?? 0`.
- En el detalle del proyecto: se calcula inline en la vista,
  una query por proyecto. Aceptable porque solo se ve un
  proyecto a la vez.

### 13. Actualizaciones a archivos existentes

- `TaskController::store`: tras `Task::create`, invoca
  `taskCreated`.
- `TaskController::complete`: tras `markCompleted`, invoca
  `taskCompleted`.
- `TaskController::reopen`: tras `markPending`, invoca
  `taskReopened`.
- `TaskMoveController::update`: tras el `mover->move`,
  invoca `taskMoved`.
- `ProjectArchiveController::archive`/`unarchive`: tras
  `$project->archive()`/`unarchive()`, invoca
  `projectArchived`/`projectUnarchived`.
- `ProjectDocumentController::store`: tras crear el
  documento, invoca `documentPublished` (que es no-op si es
  privado).
- `ProjectDocumentController::update`: detecta transicion de
  privado a publico y solo entonces invoca
  `documentPublished`. Asi no se duplica el mensaje en cada
  edicion de un documento que ya era publico.
- `admin/projects/show.blade.php`: boton "Chat" con badge
  de no leidos en el header.
- `portal/projects/show.blade.php`: idem.
- `admin/projects/index.blade.php`: columna "Chat" con badge
  por proyecto.
- `portal/projects/index.blade.php`: badge en cada card.
- `partials/admin-sidebar.blade.php`: badge agregado en el
  item "Proyectos".
- `partials/portal-sidebar.blade.php`: idem, pero filtrado
  adicionalmente a proyectos visibles al cliente.

## Tests

Total: **236 tests, 569 aserciones, todos en verde** (+40 tests
y +87 aserciones respecto a fase 4).

Distribucion nueva en fase 5:

```
tests/Unit/Models/ProjectMessageTest.php                       9 tests
tests/Unit/Models/ProjectChatReadTest.php                     6 tests
tests/Unit/Services/Activity/ProjectActivityLoggerTest.php    7 tests
tests/Feature/Admin/ChatManagementTest.php                   11 tests
tests/Feature/Portal/ChatViewTest.php                         7 tests
```

Cubren:

- `ProjectMessageTest`: cast a enum, scopes `text`/`system`/
  `before`/`after`/`recent`/`chronological`, helpers
  `isText`/`isSystem`/`isFromUserId`, relaciones.
- `ProjectChatReadTest`: `markAsRead` crea, actualiza, no
  rebobina, no-op con id <= 0. `unreadCount` calcula bien.
  Cast de `last_email_sent_at` a Carbon.
- `ProjectActivityLoggerTest`: cada metodo genera un mensaje
  de sistema con el contenido correcto. `documentPublished`
  retorna null para privados.
- `ChatManagementTest` (admin): ver chat, enviar mensaje,
  validacion, mark-as-read para el emisor, notificacion a
  destinatarios (con `Notification::fake`), mensajes de sistema
  al crear/completar/mover tarea, mensaje de sistema al
  publicar documento publico (no para privado), cliente no
  accede a ruta admin.
- `ChatViewTest` (portal): cliente ve el chat, envia mensaje,
  no accede a proyectos archivados/ocultos/de otras orgs,
  recibe notificacion al recibir mensaje, marca como leido
  y se reduce el contador.

## Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:
- 236 tests pasan, 569 aserciones.
- Build Vite sin warnings.
- 69 rutas custom (4 nuevas respecto a fase 4).
- Migraciones validadas via `RefreshDatabase` (SQLite en memoria).

## Decisiones tecnicas relevantes

1. **Tabla `project_chat_reads` con `last_read_message_id`**:
   alternativa mucho mas eficiente que un pivot por mensaje.
   El calculo de no leidos es un `count` con `id > X`,
   independientemente de cuantos mensajes haya en el chat.

2. **Componente Livewire compartido en `App\Livewire\Shared`**: el
   chat es identico entre admin y portal; duplicarlo seria
   duplicar ~250 lineas. La autorizacion la hace el componente
   contra `ProjectPolicy::view`, asi que admin y cliente
   funcionan sin cambios.

3. **`authorize` con doble pista [clase, modelo]**: vimos en
   tests que `authorize('create', $project)` infiere
   `ProjectPolicy::create` (admin-only), no
   `ProjectMessagePolicy::create`. Forzando la pista con
   `[ProjectMessage::class, $project]` se resuelve
   correctamente. Este patron ya lo usan las policies de
   `TaskPolicy::create` (recibe `Project`) y
   `BoardColumnPolicy::create` (recibe `Project`).

4. **System messages via servicio, no via Events**: el proyecto
   no tiene `app/Events` ni `app/Listeners`, y crearlos solo
   para 7 eventos seria sobre-ingenieria. El servicio
   `ProjectActivityLogger` es invocado directamente desde los
   controladores. Si en una fase futura queremos migrar a
   Events, el cambio es trivial: sustituir las llamadas por
   `event(new TaskCreated($task))` y el listener invoca el
   servicio. El formato del mensaje sigue centralizado.

5. **`documentPublished` solo en transicion privado->publico**:
   la primera llamada a `documentPublished` cuando se crea un
   documento ya publico registra el evento (porque no hay
   transicion). En la edicion, capturamos `wasPublic` y solo
   emitimos si pasa de privado a publico. Asi un documento
   que ya es publico y se edita varias veces no genera ruido
   en el chat.

6. **Notificacion `database` + `mail`, no solo `database`**: el
   TODO pide ambos. El canal `database` es el badge in-app (ya
   estaba implementado via `ProjectChatRead`, pero la
   notificacion da el contexto del mensaje). El canal `mail`
   envia a la direccion del destinatario. El debounce real
   (no enviar si ya se le ha enviado en la ultima hora) se
   difiere a la seccion "Transversal: Notificaciones".

7. **Polling 5s sin backoff**: el TODO especifica 5s exactos.
   Para una fase futura se podria implementar un "smart
   polling" que pase a 30s cuando no hay mensajes nuevos, pero
   el coste de 1 GET cada 5s por usuario activo es despreciable.

8. **El sidebar tiene badge agregado, no por chat individual**:
   el TODO menciona "indicador por proyecto" pero eso
   requeriria un submenu de chats. La opcion "badge agregado
   en el item Proyectos + badge por proyecto en el listado" da
   la misma informacion con menos cambios estructurales. Si
   en una fase futura el numero de proyectos con chat crece
   mucho, se podra migrar a un submenu.

9. **`x-data` y `x-ref` de Alpine solo para auto-scroll y
   atajos de teclado**: la logica de negocio del chat es 100%
   Livewire. Alpine se usa unicamente para glue de UI:
   posicionar el cursor tras enviar y detectar Shift+Enter.
   Esto respeta la regla del proyecto de "no Alpine.js para
   logica de negocio".

10. **El sistema de tracking de no leidos se duplica en
    `getUnreadCountProperty` y en el sidebar**: es a proposito.
    El componente lo calcula para el proyecto actual, el
    sidebar lo calcula agregado. Las dos implementaciones
    usan el mismo helper `markAsRead` y el mismo modelo, asi
    que un cambio en la logica se hace en un sitio y se
    propaga.

## Pendiente (fuera de scope de fase 5)

- Subida de archivos adjuntos (el enum `file` se reserva).
- WebSockets / real-time push (sigue siendo polling).
- Debounce de notificaciones email (seccion "Transversal:
  Notificaciones" del TODO).
- Edicion/borrado de mensajes: en MVP un mensaje es inmutable.
- Reacciones, hilos, respuestas especificas, "escribiendo...".
- Submenu dedicado de chats en el sidebar (con badge por
  proyecto). Por ahora el badge esta agregado en el item
  "Proyectos".
- Vista de "actividad reciente" del proyecto (mezcla de
  tareas, documentos, chat) que se menciona en el PRD pero
  que en MVP se descompone en las vistas individuales.
- Busqueda full-text en mensajes (LIKE sobre `content` basta
  en MVP).

## Riesgos y notas para la fase 6 (MCP)

- La tabla `notifications` queda lista para usarse en el
  badge unificado de la seccion "Transversal: Notificaciones".
  La fase 6 (MCP) la aprovecha para saber "que le puedo
  enseñar al IDE" en el listado de notificaciones.
- La relacion `Project::messages()` (declarada como firma
  adelantada en fase 2) ya tiene su modelo y migracion en
  esta fase. No se ha tocado `Project.php`.
- El `ProjectActivityLogger` se invoca desde 5
  controladores. En fase 6 no hace falta tocarlo: el MCP solo
  lee.
- La query de "no leidos" en el sidebar admin/portal es
  N+1. Para una fase futura con muchos proyectos por usuario
  se puede migrar a una query agregada. Para MVP es
  aceptable.
- El test que cubre el caso de "cliente recibe notificacion
  al recibir mensaje" usa `Notification::fake()`. Esto
  implicitamente verifica que la notificacion se envia por
  los canales `database` y `mail`, y que el payload tiene la
  forma esperada. Si en una fase futura se cambia el formato
  del payload, habra que actualizar el test.
