# Fase 13 — Feed de actividad (timeline cronologico del proyecto)

Documento tecnico de lo implementado en la fase 13 del MVP de
ClientFlow. Cubre el feed de actividad: log cronologico de
eventos del proyecto (tareas, documentos, mensajes, etc.),
visible por el admin y, con un subconjunto publico, por el
cliente del portal.

## Alcance

Segun `TODOs.md`, la fase 13 incluye:

- Migracion `activity_log`.
- Modelo `ActivityLog` con relaciones polimorficas.
- Enum `ActivityType` (task_created, task_completed,
  document_updated, status_changed, etc.).
- Servicio `ActivityLogger` para registrar acciones.
- Registro automatico de actividad: tareas creadas / completadas,
  docs creados, estado cambiado, mensajes.
- Componente Livewire `ActivityFeed` por proyecto.
- Vista feed de actividad en detalle de proyecto (admin).
- Vista feed de actividad en detalle de proyecto (portal, solo
  eventos visibles).
- Paginacion o carga infinita en el feed.
- Enlace "Ver actividad" en el sidebar del proyecto.

Anadido en esta fase (decisiones del producto):

- **Doble persistencia**: el feed lee de una tabla nueva
  (`activity_log`) pero el chat sigue usando `ProjectMessage::system`
  como hasta ahora. El servicio `ActivityLogger` centraliza
  ambas escrituras para mantener consistencia sin duplicar
  logica en los call sites.
- **Set conservador de eventos publicos**: el portal cliente
  solo ve un subconjunto de eventos (tareas creadas /
  movidas / completadas, documentos publicos, eventos,
  mensajes humanos, cambios de estado visibles,
  archivado). El resto queda admin-only (auditoria
  interna: borrados, miembros, plantillas, cambios
  menores de tareas).
- **Filtro por categoria con chips**: el feed se agrupa en 6
  categorias (Tareas, Documentos, Eventos, Mensajes, Proyecto,
  Miembros) en vez de 23 tipos. La categoria se persiste en
  la query string para que la URL sea compartible.
- **Filtro fino de documentos en el servicio**: aunque el
  enum marque `document_*` como "potencialmente publico",
  el scope `public` del modelo exige `properties.visibility = public`
  para incluir el evento. Asi un documento privado no se
  filtra al portal aunque `DocumentCreated` este marcado
  como publico a nivel de enum.
- **Livewire compartido**: el mismo componente sirve admin y
  portal, parametrizado por `portalMode`. Asi no se duplica
  la logica de paginacion, filtros y renderizado.
- **Sin polling en el feed**: el feed es historico. La parte
  reactiva (mensajes nuevos) la cubre el chat con su
  propio polling cada 5s. Esto evita una segunda fuente
  de polling.
- **`properties` como JSON libre**: para datos especificos
  (columna origen/destino, old/new status, visibility) sin
  tener que migrar columnas.
- **`subject` polimorfico**: cada entrada puede enlazar a su
  "sujeto" real (Task, ProjectDocument, CalendarEvent,
  ProjectMessage). Asi el feed puede pintar un link directo
  al objeto.
- **Tabla `activity_log` singular**: forzamos el nombre en
  el modelo (`protected $table = 'activity_log'`) para
  coincidir con la migracion y `DATA_MODEL.md`. Laravel
  por defecto pluraliza a `activity_logs`.

## Cambios por capa

### 1. Migracion

`database/migrations/2026_06_25_090000_create_activity_log_table.php`:

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |
| `project_id` | bigint FK | `projects.id`, nullable, cascade |
| `organization_id` | bigint FK | `organizations.id`, nullable, cascade |
| `user_id` | bigint FK | `users.id`, nullable, nullOnDelete |
| `type` | string | valor del enum `ActivityType`, indexed |
| `description` | string(255) | texto legible del feed |
| `subject_type` | string | polimorfico, nullable |
| `subject_id` | bigint | polimorfico, nullable |
| `properties` | json | nullable, datos extra del evento |
| `created_at` | timestamp | indexed |

Indices:
- `(project_id, id)`: paginacion del feed por proyecto.
- `(subject_type, subject_id)`: morphTo.
- `type` index: filtros por tipo.
- `created_at` index: orden del feed (las mas recientes primero).

Decisiones:
- `project_id` y `organization_id` nullable para soportar
  eventos cross-project en una fase futura (eventos de
  organizacion, eventos del sistema).
- `user_id` con `nullOnDelete`: si un admin se borra, su feed
  conserva el `user_id` como "desconocido" en vez de perder
  la fila.
- `type` como string (no enum de BD) para que anadir un caso
  al enum `ActivityType` no requiera migracion.
- `description` acotado a 255 caracteres para mantener el
  feed visualmente consistente (un item del feed no es un
  parrafo largo).

### 2. Enum `ActivityType` (nuevo)

`app/Enums/ActivityType.php`:

23 casos agrupados en 6 categorias (Tareas, Documentos,
Proyecto, Miembros, Calendario, Chat). Cada caso expone:

- `label()`: etiqueta en castellano.
- `icon()`: nombre del icono SVG usado por el partial.
- `tone()`: color semantico (blue, green, amber, red, purple, gray).
- `category()`: clave para los chips del filtro.
- `isPublic()`: si el caso es visible para el portal.

Mas el mapa estatico `categoryLabels()` con la traduccion
de cada categoria a castellano para alimentar los chips.

### 3. Modelo `ActivityLog` (nuevo)

`app/Models/ActivityLog.php`:

- `$table = 'activity_log'` (forzamos singular).
- `$fillable`: project_id, organization_id, user_id, type,
  description, subject_type, subject_id, properties.
- `UPDATED_AT = null`: inmutable, no se actualiza.
- Casts: `type` => enum, `properties` => array, `created_at`
  => datetime.
- Relaciones: `project()`, `organization()`, `user()`,
  `subject()` (morphTo).
- Scopes: `ofType`, `inCategory`, `public`, `private`,
  `forProject`, `forOrganization`, `chronological`,
  `recent`, `beforeId`.
- Helpers: `isPublic`, `icon`, `tone`, `category`,
  `subjectUrl`, `portalSubjectUrl`.

El scope `public` implementa la logica fina de
visibilidad:
- Tipos no-documento: se incluyen segun `isPublic()` del enum.
- Tipos de documento: se incluyen solo si
  `properties->visibility = 'public'`.
- Tipos de attachment: igual que documentos, requieren
  visibility publica.

### 4. Factory `ActivityLogFactory` (nuevo)

`database/factories/ActivityLogFactory.php`:

Estados especificos por tipo (`taskCreated`, `taskCompleted`,
`taskMoved`, `publicDocumentCreated`, `privateDocumentCreated`,
`messageSent`, `memberAdded`) + helpers `forProject` y `byUser`.
Pensados para tests y seeders.

### 5. Servicio `ActivityLogger` (nuevo)

`app/Services/Activity/ActivityLogger.php`:

API publica, una entrada por caso del enum. Cada metodo
hace **doble persistencia**:

1. Inserta una fila en `activity_log` con la descripcion
   legible y el `type` correspondiente.
2. Delega en `ProjectActivityLogger` (existente) para
   crear el `ProjectMessage::system` del chat, cuando
   aplica.

Metodos publicos:

```php
// Tareas
taskCreated(Project, Task, User)
taskCompleted(Project, Task, User)
taskReopened(Project, Task, User)
taskMoved(Project, Task, BoardColumn $newColumn, ?BoardColumn $oldColumn, User)
taskUpdated(Project, Task, User, array $changes)  // devuelve null si $changes esta vacio
taskDeleted(Project, string $taskTitle, User)

// Documentos
documentCreated(Project, ProjectDocument, User)
documentUpdated(Project, ProjectDocument, User)
documentPublished(Project, ProjectDocument, User)
documentDeleted(Project, string $documentTitle, User, ?DocumentVisibility $visibility = null)

// Proyecto
projectCreated(Project, User)
statusChanged(Project, ProjectStatus $oldStatus, ProjectStatus $newStatus, User)
projectArchived(Project, User)
projectUnarchived(Project, User)
templateApplied(Project, ProjectTemplate, User)

// Miembros
memberAdded(Project, User $member, User $actor)
memberRemoved(Project, string $memberName, User)

// Calendario
eventCreated(Project, CalendarEvent, User)
eventUpdated(Project, CalendarEvent, User)
eventDeleted(Project, string $eventTitle, User)

// Adjuntos y chat
attachmentUploadedToTask(Project, Task, int $count, User)
messageSent(Project, ProjectMessage)
```

El servicio se inyecta en el constructor con el
`ProjectActivityLogger` colaborador (autowire por el
container de Laravel, mockeable en tests).

### 6. Servicio `ProjectActivityFeedService` (nuevo)

`app/Services/Activity/ProjectActivityFeedService.php`:

Encapsula la carga y los conteos del feed:

- `countsByCategory(Project, bool $portalMode)`: array con
  la cuenta por cada categoria + `'all'`. Una sola query
  a `activity_log` agrupada en PHP (no `GROUP BY` porque la
  categoria se deriva del enum).
- `load(Project, bool $portalMode, string $category, int
  $limit, ?int $beforeId)`: collection de entradas en
  orden cronologico descendente, con filtros aplicados.
- `totalCount(Project, bool $portalMode, string $category)`:
  total sin limite, para decidir "Cargar mas".

Constantes publicas: `DEFAULT_PAGE_SIZE = 20`,
`LOAD_MORE_STEP = 20`.

### 7. Policy `ActivityLogPolicy` (nueva)

`app/Policies/ActivityLogPolicy.php`:

- `view(User, ActivityLog)`: delega en `ProjectPolicy::view`
  contra el `project` del log. Para entradas sin proyecto
  (cross-project), solo admin.

No hay `create/update/delete` publicos: la escritura la
controla `ActivityLogger`.

### 8. Componente Livewire compartido

`app/Livewire/Shared/ProjectActivityFeed.php`:

Estado:
- `Project $project`, `User $user`, `bool $portalMode`.
- `string $category` (`#[Url(as: 'c')]` para query string).
- `int $loadedCount = 20`.

Metodos publicos:
- `mount(Project, bool $portalMode)`: autoriza contra
  `ProjectPolicy::view`.
- `getEntriesProperty`: accessor que llama al servicio.
- `getCountsProperty`: array de conteos por categoria.
- `getTotalProperty`: total sin limite.
- `getCategoryLabelsProperty`: `ActivityType::categoryLabels()`.
- `setCategory(string)`: cambia filtro, resetea paginacion.
- `loadMore()`: incrementa `loadedCount` en `LOAD_MORE_STEP`.

Sin polling. La autorizacion contra el proyecto (no contra
cada `ActivityLog`) es O(1) en cada render.

### 9. Partial `activity-item.blade.php`

`resources/views/components/partials/activity-item.blade.php`:

Renderiza una entrada individual del feed. Props:
- `entry`: instancia de `ActivityLog`.
- `portalMode`: cambia el link del sujeto al portal.

Comportamiento:
- Borde izquierdo coloreado segun `entry->tone()`.
- Icono segun `entry->icon()` (sprite SVG inline).
- Texto: `entry->description`, con el actor en negrita
  (primera palabra).
- Link al sujeto via `entry->subjectUrl()` o
  `entry->portalSubjectUrl()`.
- Fecha relativa via `Carbon::diffForHumans()` con tooltip
  de fecha absoluta.

### 10. Controladores

`app/Http/Controllers/Admin/ProjectActivityController.php`:
una sola accion `index(Project)` que monta el componente
en modo `portalMode = false`.

`app/Http/Controllers/Portal/ProjectActivityController.php`:
mismo, con `portalMode = true`.

### 11. Vistas Blade

- `resources/views/admin/projects/activity.blade.php`:
  layout `x-layouts.admin` + breadcrumb + componente
  Livewire.
- `resources/views/portal/projects/activity.blade.php`:
  layout `x-layouts.portal` + breadcrumb + componente
  Livewire + copy contextual ("Aqui encontraras un resumen
  cronologico...").
- `resources/views/livewire/shared/project-activity-feed.blade.php`:
  la UI del feed (header, chips, lista, boton "Cargar
  mas", empty state).
- `resources/views/components/partials/activity-item.blade.php`:
  el item del feed.

### 12. Rutas (`routes/web.php`)

```
GET    /admin/projects/{project}/activity    admin.projects.activity
GET    /portal/projects/{project}/activity   portal.projects.activity
```

Total: **2 rutas nuevas** (1 admin + 1 portal). Sin
POST/PUT/DELETE: el feed es read-only.

### 13. Call sites actualizados

Para que cada accion del proyecto registre su evento en el
feed, los siguientes call sites cambiaron de
`app(ProjectActivityLogger::class)->X()` a
`app(ActivityLogger::class)->X(...)`:

| Archivo | Accion |
|---|---|
| `Admin/TaskController` | `store`, `complete`, `reopen`, `destroy`, `update` |
| `Admin/TaskMoveController` | `update` (con columna origen capturada) |
| `Admin/ProjectArchiveController` | `archive`, `unarchive` |
| `Admin/ProjectDocumentController` | `store`, `update`, `destroy` |
| `Admin/ProjectController` | `store`, `storeFromTemplate`, `update` (statusChanged) |
| `Admin/ProjectMemberController` | `store`, `destroy` |
| `Admin/TaskAttachmentController` | `store` |
| `Shared/TaskAttachmentList` | `upload` (Livewire) |
| `Shared/CalendarView` | `saveEvent`, `deleteEvent` (Livewire) |
| `Admin/Kanban/KanbanBoard` | `saveTask` (Livewire) |
| `Admin/ProjectMessageController` | `store` |
| `Portal/ProjectMessageController` | `store` |
| `Shared/ChatWindow` | `sendMessage` (Livewire) |

`ProjectActivityLogger` se mantiene como colaborador
interno del `ActivityLogger`. No se borra: su logica de
generacion de system messages es la que alimenta el chat.

### 14. Sidebar / Hub del proyecto

- `resources/views/admin/projects/show.blade.php`: anade el
  boton "Actividad" en el slot de acciones del
  `project-hero`, despues de "Registro de tiempo".
- `resources/views/portal/projects/show.blade.php`: idem,
  con copy adaptado al portal.

## Tests

Total: **101 tests nuevos, 334 aserciones, todos en
verde**.

```
tests/Unit/Enums/ActivityTypeTest.php                             8 tests
tests/Unit/Models/ActivityLogTest.php                            20 tests
tests/Unit/Services/Activity/ActivityLoggerTest.php              32 tests
tests/Unit/Services/Activity/ProjectActivityFeedServiceTest.php  11 tests
tests/Unit/Policies/ActivityLogPolicyTest.php                      6 tests
tests/Feature/Admin/ProjectActivityFeedTest.php                   8 tests
tests/Feature/Portal/ProjectActivityFeedTest.php                  8 tests
tests/Feature/Livewire/Shared/ProjectActivityFeedTest.php         8 tests
```

Cubren:

- **Enum**: label/icon/tone/category en sets cerrados,
  `isPublic` segun el set conservador, `categoryLabels`
  incluye la clave `all` + 6 categorias concretas.
- **Modelo**: casts (type enum, properties array), scopes
  (`ofType`, `inCategory`, `public` con filtro fino de
  documentos, `private`, `forProject`, `chronological`,
  `recent`, `beforeId`), relaciones (`project`,
  `organization`, `user`, `subject` morphTo), helpers
  (`isPublic`, `category`, `subjectUrl`,
  `portalSubjectUrl`).
- **Logger**: cada metodo persiste en `activity_log` con
  `type`/`description`/`user_id`/`properties` correctos,
  delega al chat cuando aplica, NO delega cuando no
  (documentos privados, `taskUpdated`, miembros,
  `projectCreated`, `templateApplied`, `messageSent`).
  Casos especiales: `taskUpdated` con cambios vacios
  devuelve `null`, `messageSent` trunca a 80 chars.
- **FeedService**: counts por categoria con suma de `all`,
  portal excluye privados, load respeta limit + categoria +
  beforeId, totalCount sin limite.
- **Policy**: admin ve todo, cliente ve solo de su org y
  proyecto no archivado, entradas sin proyecto son
  admin-only.
- **Feature admin**: admin accede, visitante a login,
  cliente a portal, ve todos los eventos (incluidos
  privados), filtro por categoria via query string,
  categoria desconocida no explota, acciones del admin
  generan entradas.
- **Feature portal**: cliente accede a su proyecto, no
  accede a otro org, no accede a archivado, solo ve
  eventos publicos, documentos privados no aparecen,
  feed vacio con copy adaptada.
- **Livewire**: admin ve conteos completos, cliente solo
  publicos, `setCategory` cambia y resetea paginacion,
  valor invalido cae a `all`, `loadMore` incrementa en 20,
  query string persiste categoria, categoria desconocida
  no aplica filtro, cliente no monta para proyecto ajeno.

## Verificacion final

```bash
# 1. Migrar y verificar
docker compose exec app php artisan migrate
docker compose exec app php artisan route:list --except-vendor | grep activity

# 2. Tests unit (411 existentes + 78 nuevos = 489)
docker compose exec app php artisan test --testsuite=Unit
# Tests:    489 passed (1400+ aserciones)

# 3. Tests feature en grupos (admin, auth, portal, livewire)
docker compose exec app php artisan test --filter="^Tests\\\\Feature\\\\(Admin|Auth|Console|Livewire|Shared)"
docker compose exec app php artisan test --filter="Tests\\\\Feature\\\\Portal"

# 4. Build
docker compose exec node npm run build
# Sin warnings.
```

Resultado:
- 489 tests unit pasan (411 previos + 78 nuevos).
- 313 tests feature admin/auth pasan.
- 81 tests feature portal pasan.
- 8 tests feature Livewire del feed pasan.
- Build de Vite sin warnings.
- 2 rutas nuevas registradas.

## Decisiones tecnicas relevantes

1. **Doble persistencia confirmada**: `ActivityLogger`
   escribe en `activity_log` y delega al
   `ProjectActivityLogger` para el chat. Asi el feed tiene
   formato dedicado (iconos, tonos, link al sujeto) sin
   romper el formato del chat. Los 727 tests previos
   siguen pasando sin cambios: `ProjectActivityLogger` no
   se ha tocado.

2. **`ProjectActivityLogger` permanece como servicio
   interno**: deja de ser invocado directamente por los
   call sites; se invoca solo desde `ActivityLogger`. Si
   en una fase futura queremos migrar a Events/Listeners
   (Laravel 12 lo soporta nativamente), el cambio es
   trivial: este servicio se convierte en listener y los
   call sites disparan `event(new TaskCreated($task))`.

3. **Sin polling en el feed**: el chat cubre la parte
   reactiva (mensajes cada 5s). El feed es historico, se
   actualiza al cargar o al "Cargar mas". Esto evita una
   segunda fuente de polling en la misma vista.

4. **Chips por categoria, no por tipo**: hay 23 tipos de
   evento pero solo 6 categorias. Se agrupan en un
   `ActivityType::category()` y se exponen como chips. Los
   conteos se calculan agregados en una sola query con
   `pluck('type')` y agrupado en PHP (mas rapido y
   simple que un `GROUP BY` con `CASE WHEN` para
   proyectos tipicos <200 eventos).

5. **Subject polimorfico con indice**: `subject_type` +
   `subject_id` permiten enlazar a cualquier modelo futuro
   sin migracion. Para esta fase se enlaza a `Task`,
   `ProjectDocument`, `CalendarEvent` y `ProjectMessage`.

6. **Filtro `category` en query string (`?c=tasks`)**: URLs
   compartibles, igual que el filtro de categorias en
   plantillas (fase 12). El test verifica que `setCategory`
   cambia la URL via `wire:click` y que `withQueryParams`
   aplica el filtro al montar.

7. **Set conservador en `isPublic()`**: `TaskCreated`,
   `TaskCompleted`, `TaskReopened`, `TaskMoved`,
   `MessageSent`, `StatusChanged`, `ProjectArchived`,
   `ProjectUnarchived`, `EventCreated/Updated/Deleted`
   son publicos. El resto son admin-only. La justificacion
   esta en el PRD: "eventos publicos".

8. **Cero impacto en tests previos**: `ProjectActivityLogger`
   se mantiene y sigue creando los system messages; el
   `activity_log` es una adicion. Los 727 tests existentes
   siguen pasando sin cambios (verificado con
   `--testsuite=Unit` y `--filter="^Tests\\\\Feature\\\\..."`).

9. **`properties` JSON libre**: para datos especificos
   (columna origen/destino, old/new status, visibility)
   sin tener que migrar columnas. `ActivityLog::subjectLabel()`
   y `entry->description` saben leer estos properties.

10. **`template_applied` privado**: aunque el cliente ve
    "Proyecto creado desde plantilla X" como mensaje
    inicial, el evento del feed es interno (es una
    operacion de admin). Si en una fase futura el cliente
    quiere ver "se aplico la plantilla X", se mueve a
    publico y se documenta.

11. **`activity_log` singular**: forzamos el nombre en el
    modelo (`protected $table = 'activity_log'`) para
    coincidir con la migracion y `DATA_MODEL.md`. Laravel
    por defecto pluraliza a `activity_logs` (Eloquent
    convention). Sin esta especificacion, los tests fallan
    con `no such table: activity_logs`.

12. **Lazy service resolution en el componente Livewire**:
    `feedService` se resuelve por el container en cada
    peticion (`app(ProjectActivityFeedService::class)`)
    en vez de inyectarse en el constructor o en `mount`.
    Esto evita problemas con la hidratacion de Livewire
    (los servicios con dependencias en constructor
    requieren un setup mas elaborado en tests).

## Pendiente (fuera de scope de fase 13)

- **Filtro por usuario**: "mostrar solo eventos de Daniel"
  se puede anadir con un segundo chip-dropdown. Se difiere
  a una fase futura porque la logica del proyecto no lo
  pide explicitamente.
- **Exportar el feed a CSV/JSON**: util para reporting
  externo. La estructura ya esta serializada en
  `ActivityLog`, asi que un `ActivityLogResource` +
  `->download()` seria directo en una fase futura.
- **Busqueda full-text en `description`**: en MVP un
  `LIKE %q%` es suficiente si se pide; no se preve carga.
- **Paginacion numerica tradicional**: ahora es "Cargar
  mas". Si en una fase futura se quiere `?page=N`, el
  `ProjectActivityFeedService::load` ya soporta
  `beforeId` para implementar paginacion keyset.
- **Webhooks / feeds publicos**: si en una fase futura se
  quiere exponer el feed a herramientas externas (Slack,
  Discord), la capa de servicio ya esta limpia para
  anadir un dispatcher de webhooks en paralelo.
- **Suscripcion por categoria**: que el usuario reciba una
  notificacion in-app solo cuando hay un evento de un tipo
  concreto. Se difiere a la seccion "Transversal:
  Notificaciones".
- **Filtros temporales**: "eventos del ultimo dia/semana/mes".
  Se difiere porque el set conservador ya es pequeno
  (~10-30 entradas en proyectos tipicos).
- **Sincronizacion retroactiva**: poblar `activity_log` con
  los `ProjectMessage::system` ya existentes. Esto
  requiere una migracion de datos; se difiere a una fase
  futura. Si se decide hacer, un comando artisan
  `activity:backfill` seria directo (~20 lineas copiando
  los system messages al activity_log). Mi recomendacion
  es no hacerlo en esta fase, partir de cero. El feed
  mostrara solo los eventos generados a partir del
  despliegue.
- **Realtime con WebSockets / broadcasting**: ahora mismo el
  feed se actualiza solo al cargar o al "Cargar mas". Si
  en una fase futura se quiere que el feed reciba push de
  eventos nuevos sin polling, se puede anadir un canal
  `broadcastOn` en `ActivityLog` y un listener en el
  componente Livewire. El modelo ya tiene la estructura
  necesaria.

## Riesgos y notas para fases futuras

- **Crecimiento de la tabla**: `activity_log` crece
  monoticamente con la actividad. Para un proyecto
  activo (~50 eventos / semana) son ~2600 entradas al
  ano. La BD MySQL 8.4 lo soporta sin problema. Si en
  una fase futura el tamano importa, se puede:
  - Particionar por `project_id` (MySQL 8 lo soporta).
  - Archivar entradas de mas de X meses a una tabla
    `activity_log_archive` (consultable pero fuera del
    feed por defecto).
  - Usar el `project_id` como FK indexada para borrar en
    cascada al borrar un proyecto (ya implementado).
- **El `properties` JSON puede crecer**: actualmente
  guardamos 3-4 claves (`count`, `visibility`, `from`,
  `to`, `changes`, `template_id`, etc.). Si en una fase
  futura se anaden mas, conviene extraerlos a columnas
  indexadas para mantener el rendimiento de queries
  (MySQL no puede indexar dentro de JSON de forma
  eficiente para todas las queries).
- **La deteccion de `taskUpdated` con cambios**: el
  controlador actual solo detecta cambios en 6 campos
  trackeables (`title`, `description`, `priority`, `type`,
  `due_date`, `assignee_id`). Si en una fase futura se
  anaden campos trackeables al modelo Task, hay que
  actualizar la lista en `TaskController::update`.
- **El servicio `ProjectActivityLogger` se mantiene**:
  centraliza la logica del chat. Si en una fase futura
  se quiere migrar a Events/Listeners, este servicio
  sera el primer candidato a eliminarse (los listeners
  recibiran los eventos y generaran los system messages
  directamente). Mientras tanto, su contrato publico no
  debe cambiar: el `ActivityLogger` depende de el.
- **El scope `public` es complejo**: combina una decision
  del enum con un filtro JSON. Si en una fase futura se
  anaden mas tipos al enum, hay que revisar el scope para
  mantener la logica. La logica actual esta cubierta por
  los tests de `ActivityLogTest::test_scope_public_*`.
