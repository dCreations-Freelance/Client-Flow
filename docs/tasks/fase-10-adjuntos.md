# Fase 10 — Adjuntos en tareas y mensajes (upload, download, eliminacion)

Documento tecnico de lo implementado en la fase 10 del MVP de
ClientFlow. Cubre el ciclo de vida de los archivos adjuntos en
tareas del kanban y mensajes del chat: subida (drag & drop o
selector), descarga, eliminacion, validacion de MIME y tamano,
aislamiento entre proyectos y generacion de mensaje de sistema
en el chat al subir a una tarea.

## Alcance

Segun `TODOs.md`, la fase 10 incluye:

- Migracion `task_attachments`.
- Migracion `message_attachments`.
- Modelo `TaskAttachment` con relaciones.
- Modelo `MessageAttachment` con relaciones.
- Subida de archivos en creacion/edicion de tarea.
- Subida de archivos en chat (drag & drop / boton).
- Lista de adjuntos en detalle de tarea.
- Adjuntos en burbujas de chat (icono + nombre + tamano).
- Servir archivos mediante controlador con autorizacion.
- Validar tipos MIME y tamano maximo (configurable).
- Eliminacion de adjuntos.

Anadido en esta fase (decisiones del producto):

- Configuracion centralizada en `config/clientflow.php`
  (disco, tamano maximo, lista de MIMEs, maximo de archivos
  por subida) leida desde variables de entorno.
- Servicio `AttachmentService` que encapsula la logica de
  almacenamiento fisico y borrado de disco + BD, invocado
  desde controladores y desde el componente Livewire del
  chat.
- Subida de adjuntos al chat desde el cliente (consistente
  con su permiso para enviar mensajes de texto).
- Mensaje de sistema automatico en el chat cuando se sube
  un adjunto a una tarea ("Daniel subio 1 adjunto a la
  tarea X"). Se emite via `ProjectActivityLogger`.
- Vista de detalle admin de tarea
  (`admin/projects/tasks/show.blade.php`) con la lista
  interactiva de adjuntos. Antes el admin no tenia una
  vista asi; las tareas solo se gestionaban desde el modal
  del kanban.
- Partial `task-attachment-row` (icono segun tipo, autor,
  tamano, fecha, botones Descargar / Eliminar).
- Partial `chat-attachment-row` (icono segun tipo, nombre,
  tamano, boton Descargar; boton Eliminar solo admin).
- Si un mensaje queda sin texto y sin adjuntos tras borrar
  el ultimo adjunto, se elimina tambien el mensaje para
  no dejar una burbuja fantasma en el chat.
- Eliminacion exclusiva del admin: la policy
  `TaskAttachmentPolicy::delete` y
  `MessageAttachmentPolicy::delete` requieren
  `$user->isAdmin()` aunque el cliente haya sido el autor.
  Decision del producto coherente con "el cliente es de
  solo lectura".
- Componente Livewire compartido `TaskAttachmentList` que
  renderiza la lista + formulario de subida. Usado tanto
  en admin como en portal: la policy decide en tiempo de
  render si el usuario actual puede subir o solo descargar.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_23_120000_create_task_attachments_table.php` | Crea `task_attachments` con FK a `tasks` (cascade), FK a `users` (restrict), metadatos (`filename`, `original_name`, `mime_type`, `size`) e indice `(task_id, created_at)` para la vista de detalle. |
| `database/migrations/2026_06_23_120001_create_message_attachments_table.php` | Idem, apuntando a `project_messages` con `cascadeOnDelete`. |

Decisiones:

- `user_id` con `restrictOnDelete` en ambas tablas: autoria
  historica, mismo patron que `project_documents`. Si un
  admin intenta borrar un usuario que subio adjuntos, MySQL
  rechaza la operacion y se le pide reasignar.
- `task_id` y `message_id` con `cascadeOnDelete`: borrar la
  tarea o el mensaje borra sus adjuntos en BD. El borrado
  fisico del archivo en disco lo coordina el `AttachmentService`
  cuando se invoca `delete()` desde el modelo relacionado
  (no se ha implementado observer automatico; el borrado se
  hace via el servicio en los endpoints de destroy).
- `size` como `unsignedBigInteger` para admitir archivos
  grandes aunque la validacion los limita.
- Indice compuesto `(parent_id, created_at)` para que la
  vista "adjuntos de esta tarea/mensaje en orden
  cronologico" sea O(1) sin escaneo lineal.

### 2. Configuracion

`app/config/clientflow.php` (nuevo) define:

- `attachments.disk`: disco de almacenamiento (default
  `local` que apunta a `storage/app/private/`).
- `attachments.max_size_kb`: tamano maximo por archivo
  (default 10240 KB = 10 MB).
- `attachments.max_files_per_upload`: maximo de archivos
  por operacion (default 5).
- `attachments.allowed_mimes`: lista de extensiones
  permitidas. Por defecto: `pdf, png, jpg, jpeg, gif, webp,
  docx, xlsx, pptx, zip, txt, csv, md, json`. La lista se
  filtra y normaliza en el config para soportar espacios y
  mayusculas.
- `attachments.subdirectory`: subdirectorio base. El servicio
  lo completa con `{project_id}/attachments/{tasks|messages}/`.

Todas las claves leen desde `env()` con un default razonable,
asi el admin puede ajustar limites via `.env` sin tocar
codigo.

### 3. Modelos

#### `TaskAttachment`

- `$fillable`: `task_id`, `user_id`, `filename`,
  `original_name`, `mime_type`, `size`.
- `casts`: `size` a `integer`.
- `generateFilename(string)`: nombre interno unico (timestamp
  + 8 chars random + extension). Centralizado para que
  factories y tests lo puedan usar.
- `formatBytes(int)`: helper estatico que formatea a KB/MB/GB.
- Relaciones: `task()`, `user()`.
- Accesors: `human_size`, `download_name` (nombre original
  para `Content-Disposition`), `disk_path` (ruta completa en
  disco basada en el subdirectorio y el project_id).
- Helper `belongsToProject(int)`: verificacion cross-project
  para los controladores.
- Scopes: `forTask(int)`, `forProject(int)` (via join con
  `tasks`), `recent()`.

#### `MessageAttachment`

Estructura paralela a `TaskAttachment` apuntando a
`ProjectMessage`. Reutiliza `TaskAttachment::formatBytes()`
para no duplicar la logica de formato. `disk_path` resuelve
a `.../attachments/messages/`.

#### `Task` y `ProjectMessage` actualizados

- `Task::attachments()` → `HasMany<TaskAttachment>` (ordered
  por `created_at desc` para la vista de detalle).
- `ProjectMessage::attachments()` → `HasMany<MessageAttachment>`.
- `ProjectMessage::isEmpty()`: helper que devuelve `true` si
  el `content` esta en blanco tras `trim`. Usado por la
  vista del chat y por el `MessageAttachmentController`
  para eliminar mensajes fantasma tras borrar el ultimo
  adjunto.

### 4. Servicio

`app/Services/Attachments/AttachmentService.php`:

- `store(Project, string $context, int $parentId,
  UploadedFile, User)`: sube el archivo al disco via
  `Storage::disk(config('clientflow.attachments.disk'))->
  putFileAs(...)` y crea la fila correspondiente
  (`TaskAttachment` o `MessageAttachment`). El contexto es
  `'tasks'` o `'messages'`. Lanza `InvalidArgumentException`
  si el contexto no es valido.
- `deleteTaskAttachment(TaskAttachment)`: borra el archivo
  del disco y la fila. Idempotente: si el archivo ya no
  existe, no lanza excepcion.
- `deleteMessageAttachment(MessageAttachment)`: análogo.
- `deleteAllForTask(Task)` y `deleteAllForMessage(...)`:
  pensados para uso en eventos `deleting` de los modelos
  padre si en una fase futura se prefiere no confiar
  exclusivamente en el `cascadeOnDelete` de la FK.

Decisiones:

- La validacion de MIME y tamano vive en la Form Request
  (porque necesita reglas de Laravel); el servicio asume
  que el archivo ya paso esa validacion.
- `Storage::exists` antes de `Storage::delete` para hacer
  el borrado idempotente.

### 5. Policies

#### `TaskAttachmentPolicy`

- `view` / `download`: delegan en `TaskPolicy::view` (mismo
  aislamiento que la tarea padre).
- `create` (contra `Project`): solo admin.
- `delete`: solo admin, sin importar el autor.

#### `MessageAttachmentPolicy`

- `view` / `download`: delegan en `ProjectPolicy::view`
  contra el proyecto del mensaje.
- `create` (contra `Project`): el cliente puede subir
  adjuntos a mensajes, consistente con su permiso para
  enviar mensajes de texto.
- `delete`: solo admin.

### 6. Form Requests

- `Admin/UploadTaskAttachmentRequest`: valida `attachment`
  como `file` con `max` y `mimes` leidos del config. Solo
  admin (`authorize()`).
- `Admin/UploadMessageAttachmentRequest`: mismas reglas,
  autorizacion delega en la policy.
- `Portal/UploadMessageAttachmentRequest`: mismas reglas
  para el portal cliente.

### 7. Controladores

#### `Admin/TaskAttachmentController`

- `store(UploadTaskAttachmentRequest, Project, Task)`:
  verifica que la tarea pertenece al proyecto, sube via
  el servicio, dispara `ProjectActivityLogger::
  attachmentUploadedToTask`.
- `download(Project, Task, TaskAttachment)`: verifica
  cross-project + cross-task + policy `download`. Llama a
  `Storage::download` con el nombre original del usuario.
- `destroy(Project, Task, TaskAttachment)`: policy `delete`
  + `AttachmentService::deleteTaskAttachment`.

#### `Admin/MessageAttachmentController`

- `download` y `destroy`. El destroy, si tras borrar el
  adjunto el mensaje queda sin texto y sin adjuntos, lo
  elimina tambien (mensaje fantasma).

#### `Portal/TaskAttachmentController`

- `download` (solo). El cliente no puede subir ni borrar.

#### `Portal/MessageAttachmentController`

- `store` (subida, consistente con permiso de enviar
  mensajes) y `download`.

#### `Admin/TaskController` (extendido)

- Nuevo metodo `show(Request, Project, Task)` que renderiza
  la vista de detalle admin de tarea con el componente
  `TaskAttachmentList`. Incluye verificacion de que la
  tarea pertenece al proyecto.

### 8. `ProjectActivityLogger` (extendido)

- Nuevo metodo `attachmentUploadedToTask(Project, Task,
  int $count, User $actor)`: emite un system message en
  el chat con el formato "X subio N adjuntos a la tarea Y".

### 9. Componentes Livewire

#### `Shared/ChatWindow` (extendido)

- `use WithFileUploads;` (primer componente del proyecto
  con subida de archivos; establece el patron).
- `public array $attachments = []`: array de
  `TemporaryUploadedFile` para permitir hasta
  `max_files_per_upload` archivos en una sola operacion.
- `sendMessage()` actualizado: si hay adjuntos, los sube
  via `AttachmentService` y crea el mensaje con `type =
  File` y `content = ''` si no hay texto. Si hay texto y
  adjuntos, `type = Text` y los adjuntos se suben en el
  mismo flujo.
- `removePendingAttachment(int)`: quitar un adjunto de la
  lista antes de enviar.
- `deleteMessageAttachment(int)`: eliminar un adjunto ya
  enviado (solo admin; la policy lo bloquea para el
  cliente). Si el mensaje queda vacio, lo borra tambien.
- `getMessagesProperty` ahora hace `with(['user',
  'attachments.user'])` para no disparar N+1 al pintar las
  burbujas con adjuntos.
- `getUnreadCountProperty`, `markAsRead`, etc. sin cambios.

#### `Shared/TaskAttachmentList` (nuevo)

Renderiza la lista de adjuntos de una tarea con su
formulario de subida. Usado en admin (con formulario y
botones de borrado) y en portal (solo descarga).

Estado: `Project`, `Task`, `array $pendingAttachments`.

Metodos publicos:
- `mount(Project, Task)`: valida cross-project.
- `upload()`: valida, sube via el servicio, dispara
  `ProjectActivityLogger::attachmentUploadedToTask`,
  limpia `pendingAttachments`, flash de confirmacion.
- `delete(int $attachmentId)`: policy `delete` + servicio.
- `removePending(int)`: quitar un adjunto pendiente.
- `getAttachmentsProperty`: adjuntos con autor (evita
  N+1 en la vista).
- `render()`: pasa `attachments`, `canUpload`,
  `canDelete` segun `Auth::user()->isAdmin()`.

### 10. Vistas Blade

Nuevas:
- `resources/views/components/partials/chat-attachment-row.blade.php`:
  fila de adjunto dentro de una burbuja del chat. Icono
  segun tipo (imagen / PDF / generico), nombre, tamano,
  boton Descargar. Boton Eliminar visible solo si
  `canDelete` (admin). Resuelve la ruta de descarga via
  `route($routeName, ...)` para que la misma fila funcione
  en admin y portal.
- `resources/views/components/partials/task-attachment-row.blade.php`:
  fila de adjunto en la vista de detalle de tarea. Icono
  segun tipo, nombre, "Subido por X · N MB · hace 2h",
  botones Descargar y Eliminar (admin).
- `resources/views/livewire/shared/task-attachment-list.blade.php`:
  el componente que pinta la lista + el formulario de
  subida. Si `canUpload` (admin), muestra el formulario
  con `wire:model="pendingAttachments"` (multiple),
  preview de archivos pendientes, boton "Subir", y un
  mensaje informativo del limite.
- `resources/views/admin/projects/tasks/show.blade.php`:
  vista de detalle admin de tarea. Breadcrumb, cabecera
  con prioridad / tipo / fecha limite, descripcion,
  card de adjuntos (con `TaskAttachmentList`), card de
  subtareas, card lateral de asignado y estimacion.

Actualizadas:
- `livewire/shared/chat-window.blade.php`: input file
  multiple (`wire:model="attachments"`), preview de
  adjuntos pendientes con boton X para quitar cada uno,
  y dos `@error` blocks (uno para `attachments` y otro
  para `attachments.*`).
- `components/partials/chat-message.blade.php`: el bucle
  de adjuntos se pinta dentro de la burbuja. Si el
  mensaje no tiene texto, se omite el `<p>` del contenido
  para que la burbuja no quede con espacio vacio; solo
  aparece el bloque de adjuntos.
- `livewire/admin/kanban/kanban-board.blade.php`: en el
  modal de crear tarea, bloque "Adjuntos" con input file
  multiple, preview de pendientes y mensaje informativo
  del limite. Solo en modo `create`; en `edit` la gestion
  se hace desde el detalle admin.
- `admin/projects/show.blade.php`: boton "Ver tarea" en
  la card de tareas del proyecto que enlaza al detalle
  admin.
- `portal/projects/tasks/show.blade.php`: extendido para
  incluir el componente `TaskAttachmentList`.

### 11. Rutas (`routes/web.php`)

```
GET    /admin/projects/{project}/tasks/{task}                              admin.projects.tasks.show
POST   /admin/projects/{project}/tasks/{task}/attachments                 admin.projects.tasks.attachments.store
GET    /admin/projects/{project}/tasks/{task}/attachments/{attachment}     admin.projects.tasks.attachments.download
DELETE /admin/projects/{project}/tasks/{task}/attachments/{attachment}     admin.projects.tasks.attachments.destroy
POST   /admin/projects/{project}/messages/{message}/attachments            admin.projects.messages.attachments.store
GET    /admin/projects/{project}/messages/{message}/attachments/{attachment} admin.projects.messages.attachments.download
DELETE /admin/projects/{project}/messages/{message}/attachments/{attachment} admin.projects.messages.attachments.destroy

GET    /portal/projects/{project}/tasks/{task}/attachments/{attachment}     portal.projects.tasks.attachments.download
POST   /portal/projects/{project}/messages/{message}/attachments           portal.projects.messages.attachments.store
GET    /portal/projects/{project}/messages/{message}/attachments/{attachment} portal.projects.messages.attachments.download
```

Total: **9 rutas nuevas** (7 admin + 2 portal). Ademas se
restauro la ruta `admin.projects.tasks.move` que se perdio
en una limpieza intermedia.

### 12. Modelos actualizados (resumen)

- `Task` (+`attachments()`)
- `ProjectMessage` (+`attachments()` y `isEmpty()`)

## Tests

Total: 47 tests nuevos pasan en verde (12 unit + 35 feature).

Distribucion:

```
tests/Unit/Models/TaskAttachmentTest.php           12 tests
tests/Unit/Models/MessageAttachmentTest.php        8 tests
tests/Unit/Services/Attachments/AttachmentServiceTest.php  6 tests
tests/Feature/Admin/AttachmentTest.php            14 tests
tests/Feature/Portal/AttachmentViewTest.php        7 tests
```

Cubren:

- **Modelos**: fillable, casts, accesors
  (`humanSize`, `downloadName`, `diskPath`), scopes
  (`forTask`, `forProject`, `recent`), helpers
  (`belongsToProject`, `generateFilename`,
  `formatBytes`).
- **AttachmentService**: store/delete para tareas y
  mensajes, contextos invalidos, borrado idempotente.
- **Admin feature**: subida con archivo fisico en disco,
  generacion de system message, validacion de MIME y
  tamano, rechazo a clientes (redirect al portal),
  descarga con verificacion cross-project y
  cross-task, borrado por admin con limpieza de disco,
  cascade al borrar tarea, mensaje fantasma.
- **Portal feature**: descarga de adjuntos propios,
  rechazo de adjuntos de proyectos ajenos, rechazo de
  adjuntos de tareas ajenas del mismo proyecto,
  verificacion de que la ruta de borrado no existe en
  el portal, subida y descarga de adjuntos a mensajes.

## Verificacion final

```bash
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test --filter="Attachment"
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado: 47 tests nuevos pasan, el resto del suite
mantiene su estado (los fallos pre-existentes en
TaskDueSoon, PasswordReset, NotificationDispatcher,
HomeTest, etc. son ajenos a esta fase).

## Decisiones tecnicas relevantes

1. **Config centralizado en `clientflow.php`**: ningun
   valor hardcodeado en codigo. El admin puede subir el
   limite, ampliar MIMEs o cambiar el disco via `.env`
   sin reiniciar nada salvo PHP-FPM.

2. **Storage local, nunca `public/`**: el disco `local`
   apunta a `storage/app/private/` y el servicio genera
   rutas bajo `clientflow/projects/{id}/attachments/...`.
   Los archivos se sirven exclusivamente via
   `Storage::download` desde un controlador que ya ha
   pasado la policy.

3. **Doble barrera en el portal cliente**: ademas de la
   policy `download`, el `Portal\TaskAttachmentController`
   verifica que la tarea pertenece al proyecto Y que el
   adjunto pertenece a la tarea. Asi aunque un cliente
   descubra IDs validos de otro proyecto, no llega al
   `Storage::download`.

4. **Subida multi-archivo en chat, mono en tarea**: el
   chat admite hasta `max_files_per_upload` archivos
   simultaneos (`wire:model="attachments" multiple`). En
   el modal de crear tarea, lo mismo. En el detalle de
   tarea (vista persistente), tambien multiple. Se
   podria limitar a uno en una fase futura si el
   rendimiento se resiente, pero con `max_files_per_upload
   = 5` y `max_size_kb = 10240` el volumen esta
   controlado.

5. **Validacion de tipo en Form Request con `mimes:` y
   `mimetypes:`**: usamos `mimes:` que en Laravel verifica
   la extension declarada y la "suposicion" de tipo basada
   en ella. Para produccion, anadir tambien
   `mimetypes:application/pdf,image/png,...` seria mas
   estricto. En MVP, `mimes:` es suficiente y mas
   amigable con el usuario (los `mimetypes:` pueden
   rechazar archivos con extension correcta pero
   mimetype no estandar).

6. **Mensaje de sistema solo al subir a tarea, no al
   mensaje del chat**: en la tarea el system message
   avisa a los miembros del proyecto que hay archivos
   nuevos. En el chat la propia burbuja con el adjunto
   ya es la notificacion visible: el miembro que ve el
   scroll detecta el icono. Emitir un system message
   ademas seria ruido.

7. **Borrado exclusivo del admin**: la regla es
   deliberadamente estricta. El cliente es de solo
   lectura, tambien sobre sus propios adjuntos. Si en
   una fase futura se quiere permitir, basta con cambiar
   el `delete` de la policy a `$user->isAdmin() || $
   user->id === $attachment->user_id`. El cambio seria
   de una linea.

8. **Mensaje fantasma**: si tras borrar el ultimo
   adjunto de un mensaje este queda sin texto Y sin
   adjuntos, se elimina el mensaje. La razon es que un
   `ProjectMessage::type = File` con `content = ''` y
   cero adjuntos es una burbuja invisible y solo
   contamina el historial.

9. **Sin previsualizacion de imagenes inline**: el chat
   pinta el icono segun el tipo MIME pero no muestra
   thumbnails. Esto se difiere a una fase futura para
   evitar el coste de generar y cachear previews.

10. **Componente compartido `TaskAttachmentList`**: la
    vista de detalle admin y portal usan el mismo
    componente. La policy decide en tiempo de render si
    el usuario actual puede subir o solo descargar, asi
    evitamos duplicar la vista o mantener dos archivos
    sincronizados.

## Pendiente (fuera de scope de fase 10)

- Versionado / historial: si se re-sube el mismo
  archivo, se crea una fila nueva (no se sustituye la
  antigua).
- Previsualizacion de imagenes inline en el chat.
- Generacion de thumbnails para imagenes y PDFs.
- Busqueda full-text en `original_name` o en el
  contenido de PDFs.
- Migracion a S3 u otro driver externo (el
  `attachments.disk` ya permite el cambio).
- Subida multiple con barra de progreso por archivo
  (Livewire 4 ya soporta `wire:model` con barra por
  archivo, pero requiere pulir la UI).
- Cuota de almacenamiento por proyecto o por organizacion.

## Riesgos y notas para la fase 11 (registro de tiempo)

- El servicio `AttachmentService` queda preparado para
  extender a otros contextos (`events`, `documents`):
  basta con anadir un nuevo `const CONTEXT_*` y un metodo
  `storeFor...`. La logica de disco es generica.
- El modelo `Task` ya tiene `attachments()` y pronto
  tendra `timeEntries()`. Mantener el helper
  `belongsToProject` consistente en ambos modelos para
  evitar duplicacion en los controladores.
- `with('user')` en `getAttachmentsProperty` del
  `TaskAttachmentList` es para evitar N+1 en la lista;
  en una fase futura con muchos adjuntos por tarea se
  podria paginar (en MVP son pocos por tarea).
- La validacion `mimes:` en Form Request es
  relativamente laxa. Si el hosting permite configurar
  `upload_max_filesize` en `php.ini`, conviene
  sincronizarlo con `attachments.max_size_kb` para que
  PHP no rechace la subida antes de que Laravel la vea.
- Si en una fase futura se quiere comprimir imagenes
  automaticamente, el lugar adecuado es el
  `AttachmentService::store` antes del `putFileAs`.
