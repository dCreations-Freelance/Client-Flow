# Fase 8 — Calendario

Documento tecnico de lo implementado en la fase 8 del MVP de
ClientFlow. Cubre el ciclo de vida del calendario por proyecto:
eventos `meeting` / `milestone`, invitados, vista mensual y
semanal, deadlines virtuales derivados de `tasks.due_date`,
notificaciones in-app y compatibilidad con el modo read-only del
portal cliente.

## Alcance

Segun `TODOs.md`, la fase 8 incluye:

- Migracion `calendar_events`.
- Migracion `calendar_event_user` (asistentes).
- Enum `CalendarEventType` (`meeting`, `deadline`, `milestone`).
- Modelo `CalendarEvent` con relaciones.
- Vista calendario mensual/semanal (admin).
- Vista calendario (portal).
- Crear/editar eventos ligados a proyectos.
- Invitar asistentes a eventos.
- Notificaciones antes de eventos.

Decisiones de diseno tomadas en esta fase:

- **Eventos solo ligados a proyecto**: `project_id` es NOT NULL.
  Se descartan eventos cross-project (ferias, eventos internos)
  para simplificar la UI y la policy.
- **Deadlines virtuales**: las tareas con `due_date` se renderizan
  como entradas sinteticas en el calendario (no se persisten en
  `calendar_events`). Cambiar el `due_date` de una tarea
  actualiza el calendario al instante.
- **UI custom con Livewire**: cero librerias externas. Crea una
  grilla mensual y una lista semanal coherentes con el resto del
  admin/portal.
- **Notificaciones in-app al crear/editar**: sin scheduler, sin
  recordatorios previos. MVP mantiene el principio "sin workers
  permanentes".
- **Flag `is_all_day`**: anadido como desviacion controlada del
  data model para soportar hitos y eventos de jornada completa.
  Cuando es true, `starts_at` se normaliza a `00:00:00` y
  `ends_at` a `23:59:59`.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_19_072121_create_calendar_events_table.php` | Crea `calendar_events` con FK a `projects` (cascade), FK a `users` como `created_by` (restrict), `type` indexado, `starts_at`/`ends_at` (datetime), `is_all_day` boolean, `title`, `description`, e indices `(project_id, starts_at)` y `starts_at`. |
| `database/migrations/2026_06_19_072122_create_calendar_event_user_table.php` | Pivot `calendar_event_user` con `id` surrogate, FKs con `cascadeOnDelete` en ambos lados, unique `(calendar_event_id, user_id)` e indice en `user_id`. |

Decisiones:
- `project_id` NOT NULL: en MVP todos los eventos pertenecen a
  un proyecto concreto. Ver seccion "Alcance".
- `is_all_day` boolean: desviacion controlada del data model
  original. Justificada en el PHPDoc de la migracion.
- `is_all_day = true` + `ends_at` null: el modelo y los Form
  Requests normalizan a `00:00:00` y `23:59:59` respectivamente.
- `restrictOnDelete` en `created_by`: preserva autoria historica.
- Indice compuesto `(project_id, starts_at)`: patron de query
  "eventos del mes X" sin filesort.

### 2. Enum

`app/Enums/CalendarEventType.php`:

| Caso | Valor | Etiqueta | Color |
|---|---|---|---|
| `Meeting` | `meeting` | Reunion | blue |
| `Milestone` | `milestone` | Hito | green |
| `Deadline` | `deadline` | Fecha limite | orange |

Metodos: `label()`, `color()`, `badgeClasses()` (string CSS
completo), `isMeeting()`, `isMilestone()`, `isDeadline()`.

El caso `Deadline` se reserva para la representacion virtual
derivada de `tasks.due_date`. Nunca se persiste un
`CalendarEvent` con este tipo.

### 3. Modelos

#### `CalendarEvent`

- `$fillable`: `project_id`, `title`, `description`, `type`,
  `starts_at`, `ends_at`, `is_all_day`, `created_by`.
- Casts: `type` enum, `starts_at`/`ends_at` datetime, `is_all_day`
  boolean.
- Relaciones: `project()` BelongsTo, `creator()` BelongsTo(User,
  `created_by`), `attendees()` BelongsToMany(User via
  `calendar_event_user`) con timestamps.
- Scopes:
  - `forProject(int $projectId)`.
  - `betweenDates(Carbon $from, Carbon $to)`: incluye eventos
    que solapan con el rango (multi-dia cubiertos).
  - `byType(string $type)`.
  - `upcoming(int $limit = 10)`: solo eventos futuros.
  - `ordered()`: por `starts_at` ascendente.
- Helpers: `isAllDay()`, `isOnDate(Carbon)`,
  `isPast()`/`isUpcoming()`, `occursInRange(Carbon, Carbon)`,
  `durationMinutes()`, `isMeeting()`/`isMilestone()`.
- Accessors: `end_for_query` (devuelve `ends_at` o `starts_at
  + 30min` por defecto).

#### `User` (actualizado)

- Anadidas relaciones:
  - `attendedEvents()` BelongsToMany CalendarEvent via
    `calendar_event_user` con timestamps.
  - `createdCalendarEvents()` HasMany CalendarEvent via
    `created_by`.

### 4. Policies

`app/Policies/CalendarEventPolicy.php`:

- `viewAny(User, Project)`: delega en `ProjectPolicy::view`.
- `view(User, CalendarEvent)`: admin o cliente con acceso al
  proyecto del evento.
- `create(User)`: solo admin.
- `update(User, CalendarEvent)`: solo admin.
- `delete(User, CalendarEvent)`: solo admin.
- `manageAttendees(User, CalendarEvent)`: solo admin.

La verificacion de visibilidad se delega en `ProjectPolicy::view`
para mantener una unica fuente de verdad.

### 5. Form Requests

- `Admin/StoreCalendarEventRequest`: `title` (2-200),
  `description` (max 5000), `type` (in `meeting|milestone`,
  excluye `deadline` para evitar que se persista el caso virtual),
  `is_all_day` boolean, `starts_at` (date required), `ends_at`
  (nullable, `after_or_equal:starts_at`), `attendees` (array de
  `exists:users,id`). Expone `eventData()` que normaliza
  all-day a `00:00:00` / `23:59:59`.
- `Admin/UpdateCalendarEventRequest`: mismas reglas y misma
  logica de normalizacion.

Mensajes en castellano. `authorize()` pre-chequea `isAdmin()`
como defensa en profundidad.

### 6. Servicios

#### `CalendarQueryService`

Centraliza todo lo relativo a obtener los datos que pinta la
vista del calendario. Metodos publicos:

- `getEventsForPeriod(Project, Carbon $from, Carbon $to)`:
  eventos persistidos que solapan con el rango, con eager load
  de `creator` y `attendees`.
- `getVirtualDeadlines(Project, Carbon $from, Carbon $to)`:
  tareas raiz con `due_date` en el rango, mapeadas a objetos
  ligeros con flag `is_virtual = true` y `type =
  CalendarEventType::Deadline`.
- `getCalendarData(Project, Carbon $currentDate, string $view)`:
  payload completo para el componente Livewire (grilla + eventos
  por dia + deadlines por dia).
- `getMonthRange(Carbon)` y `getWeekRange(Carbon)`: rangos
  segun la vista, empezando en lunes.
- `buildMonthDays(Carbon)` y `buildWeekDays(Carbon)`: arrays de
  Carbon con los dias a renderizar.
- `groupByDay(Collection)`: agrupacion por clave `Y-m-d` para
  lookup O(1) en la vista.
- `availableAttendees(Project, ?int $excludeUserId)`: union de
  miembros del proyecto + miembros de la org, deduplicados,
  excluyendo al emisor si se pasa.

#### `ProjectActivityLogger` (extendido)

Anadidos metodos:

- `eventCreated(Project, CalendarEvent, User $actor)`: mensaje
  "Actor creo el evento X (Tipo) en el calendario."
- `eventUpdated(Project, CalendarEvent, User $actor)`:
  "Actor actualizo el evento X del calendario."
- `eventDeleted(Project, string $eventTitle, User $actor)`:
  "Actor elimino el evento X del calendario."

### 7. Notificaciones

`app/Notifications/CalendarEventInvitation.php`:

- Canales: `['database', 'mail']`.
- `toArray()`: payload con `project_id`, `project_name`,
  `event_id`, `event_title`, `event_type`, `event_type_label`,
  `starts_at`, `starts_at_formatted` (en es-ES), `is_all_day`,
  `creator_name`, `url`.
- `toMail()`: saludo, datos del evento (titulo, tipo, cuando
  formateado), boton "Ir al calendario" con URL al calendario
  del proyecto.
- `formattedStart()`: helper privado que formatea la fecha en
  castellano via `Carbon::locale('es')->translatedFormat(...)`.

### 8. Componente Livewire compartido

`app/Livewire/Shared/CalendarView.php`:

Estado:
- `Project $project`, `User $user` (capturados en mount).
- `bool $readOnly` (activado por el controlador del portal).
- `string $view = 'month'` (month|week), con `#[Url]`.
- `string $currentDate` (formato Y-m-d), con `#[Url]`.
- `?array $eventForm = null` (modal state).
- `array $attendeeIds = []` (multi-select state).

Metodos publicos:
- `mount(Project, bool $readOnly = false)`: authorize view.
- Navegacion: `shiftPeriod(int)`, `goToToday()`, `setView(string)`,
  `resolveCurrentDate()`.
- Modal: `openCreateForm(string $date)`, `openEditForm(int
  $eventId)`, `closeForm()`, `addAttendee(int $userId)`.
- Persistencia: `saveEvent()` (delega en
  `StoreCalendarEventRequest` para reusar reglas),
  `deleteEvent(int $eventId)`.
- `guardNotReadOnly()`: salvaguarda para evitar acciones en
  modo read-only.

Propiedades computadas:
- `getCalendarDataProperty()`: payload completo (grilla + eventos
  por dia + deadlines por dia + eventos del mes).
- `getAvailableAttendeesProperty()`: usuarios disponibles para
  invitar, excluyendo al emisor.
- `getUpcomingEventsProperty()`: eventos de los proximos 7 dias
  (pensado para badge futuro).

Reuso de validacion: `saveEvent()` instancia la Form Request
`StoreCalendarEventRequest`, copia los datos con `merge()` y
valida con `validator($data, $request->rules(), $request->messages())`.
Asi hay una sola fuente de verdad para las reglas.

Reuso de la politica: `saveEvent()` y `deleteEvent()` llaman a
`$this->authorize()` con la pista de la policy correspondiente.
Se valida la membresia con `resolveAttendees()` que filtra
contra los `availableAttendees` para evitar ids manipulados.

### 9. Controladores

- `App\Http\Controllers\Admin\ProjectCalendarController`: solo
  `index(Request, Project)` que renderiza la vista con el
  componente Livewire. La autorizacion la hace la policy via
  el componente, pero se pre-verifica aqui como salvaguarda.
- `App\Http\Controllers\Portal\ProjectCalendarController`:
  mismo `index`, pero la vista monta el componente con
  `readOnly = true`.

### 10. Vistas Blade

#### Admin

- `resources/views/admin/projects/calendar.blade.php`: layout
  admin + `<livewire:shared.calendar-view :project="$project" />`
  + breadcrumb + boton "Volver al proyecto".

#### Portal

- `resources/views/portal/projects/calendar.blade.php`: layout
  portal + `<livewire:shared.calendar-view :project="$project"
  :readOnly="true" />` + breadcrumb + descripcion.

#### Livewire

- `resources/views/livewire/shared/calendar-view.blade.php`:
  vista del componente. Contiene:
  - Header: navegacion (anterior / "Mes Y" / siguiente), boton
    "Hoy", switch Mes/Semana, y boton "Nuevo evento" (solo en
    admin).
  - Leyenda con los tres tipos (reunion, hito, fecha limite).
  - Grid mensual: 7 columnas (L M X J V S D) x N filas. Cada
    celda muestra hasta 3 eventos y un "+N mas" si hay mas.
    Click en celda vacia abre el modal de creacion con la
    fecha pre-rellenada.
  - Lista semanal: 7 secciones, una por dia, con todos los
    eventos.
  - Modal de evento: formulario completo (titulo, tipo,
    descripcion, fechas, all-day, attendees) con selector de
    attendees (chips con busqueda y dropdown).
  - Atajos: `x-on:keydown.escape.window` cierra el modal.
  - Sin JS externo: usa `@script` y los handlers de Livewire
    (`wire:click`, `wire:model`).

#### Partials

- `resources/views/components/partials/calendar-event-type-badge.blade.php`:
  badge segun `CalendarEventType::badgeClasses()`. Pensado para
  reutilizarse en listados de eventos futuros.
- `resources/views/components/partials/calendar-event-card.blade.php`:
  tarjeta compacta. Soporta modo `compact` (oculta descripcion
  y asistentes para encajar en celdas pequenas) y modo
  `readOnly` (oculta los botones de editar/eliminar). En modo
  no-readOnly los botones aparecen al `group-hover` para no
  saturar visualmente la grilla.

### 11. Actualizaciones a archivos existentes

- `routes/web.php`: anadidas las rutas:
  - `GET /admin/projects/{project}/calendar` ->
    `admin.projects.calendar`
  - `GET /portal/projects/{project}/calendar` ->
    `portal.projects.calendar`

  Tambien anadidos los `use` para los aliases
  `AdminProjectCalendarController` y
  `PortalProjectCalendarController`.

- `resources/views/admin/projects/show.blade.php`: anadido boton
  "Calendario" en el header (junto a "Abrir tablero" y "Chat"),
  envuelto en `Route::has(...)`.

- `resources/views/portal/projects/show.blade.php`: anadido
  boton "Ver calendario" en el header (junto a "Ver tablero
  kanban" y "Ver documentos"), envuelto en `Route::has(...)`.

- `app/Models/User.php`: anadidas relaciones `attendedEvents()`
  y `createdCalendarEvents()` (en linea con el resto del data
  model).

- `app/Services/Activity/ProjectActivityLogger.php`: anadidos
  `eventCreated`, `eventUpdated`, `eventDeleted`.

### 12. Factory

`database/factories/CalendarEventFactory.php`:

- Estados: `meeting()` (default), `milestone()`,
  `allDay()`, `forProject(Project)`, `createdBy(User)`,
  `inPast()`, `inFuture()`.
- Genera titulos realistas segun el tipo (e.g. "Reunion de
  kickoff", "Lanzamiento MVP").

### 13. Seeder

`database/seeders/DatabaseSeeder.php`:

- Crea un `Proyecto Demo` (si no existe) con descripcion y
  status `in_progress`.
- Crea las 4 columnas por defecto del kanban.
- Crea 2 meetings y 1 milestone all-day, con attendees
  (admin + cliente).
- Crea 2 tareas con `due_date` para que el calendario muestre
  deadlines virtuales visibles.

### 14. Tests

Distribucion nueva en fase 8:

```
tests/Unit/Models/CalendarEventTest.php                        17 tests
tests/Unit/Enums/CalendarEventTypeTest.php                     5 tests
tests/Unit/Services/Calendar/CalendarQueryServiceTest.php     12 tests
tests/Feature/Admin/CalendarManagementTest.php                13 tests
tests/Feature/Portal/CalendarViewTest.php                      9 tests
```

Total: **56 tests, 133 aserciones, todos en verde**. Incremento
respecto a fase 6 (MCP) y siguientes: +56 tests, +133 aserciones.

Cubren:

- `CalendarEventTest`: cast a enum, scopes `forProject`/
  `betweenDates`/`byType`/`upcoming`/`ordered`, helpers
  `isAllDay`/`isOnDate`/`isPast`/`isUpcoming`/
  `occursInRange`/`end_for_query`/`durationMinutes`,
  relaciones `project`/`creator`/`attendees`,
  `isMeeting`/`isMilestone`.
- `CalendarEventTypeTest`: labels, colors, `badgeClasses`,
  helpers de tipo, value string.
- `CalendarQueryServiceTest`: query de eventos en rango,
  exclusion de otros proyectos, deadlines virtuales desde
  tareas, payload completo mensual y semanal, rangos
  mensuales y semanales (lunes a domingo), grilla multiplo de
  7, agrupacion por dia, asistentes disponibles (union +
  exclusion).
- `CalendarManagementTest` (admin): ver calendario, crear
  meeting, crear milestone all-day, normalizacion de horas
  all-day, editar, eliminar, validacion (titulo vacio, tipo
  invalido, ends_at anterior a starts_at), mensaje de sistema
  en chat al crear, notificacion a asistentes, no auto-
  notificacion al emisor, exclusion de attendees ajenos al
  proyecto, cliente no accede a admin.
- `CalendarViewTest` (portal): ver calendario, modo readOnly
  (verifica propiedad), ver evento del proyecto, no puede
  crear/editar/eliminar (devuelve 403 via `assertForbidden`),
  ver deadlines virtuales, no accede a proyectos de otras orgs
  o archivados.
- Adicionalmente, se extendio `ProjectActivityLoggerTest` con
  3 tests para `eventCreated`/`eventUpdated`/`eventDeleted`.

## Rutas nuevas

```
GET    /admin/projects/{project}/calendar          admin.projects.calendar
GET    /portal/projects/{project}/calendar         portal.projects.calendar
```

Solo 2 rutas nuevas (toda la interaccion se hace via
componente Livewire, mismo patron que fase 5 chat y fase 4
documentos).

## Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:
- **390 tests pasan, 963 aserciones** (+56 tests y +133
  aserciones sobre la fase anterior).
- Build Vite sin warnings.
- **80 rutas custom** (2 nuevas respecto a fase 7).
- Migraciones validadas via `RefreshDatabase` (MySQL en este
  entorno).

## Decisiones tecnicas relevantes

1. **Eventos solo ligados a proyecto**: el data model permitia
   `project_id` nullable. Se opto por NOT NULL para simplificar
   la UI (sin necesidad de vista cross-project) y la policy
   (siempre hay un proyecto al que verificar). Esto se puede
   revertir en una fase futura eliminando la constraint.

2. **Deadlines virtuales, no sincronizados**: las tareas con
   `due_date` se renderizan directamente desde
   `CalendarQueryService::getVirtualDeadlines()`. Cambiar el
   `due_date` de una tarea actualiza el calendario al instante.
   Esto evita duplicacion de datos y simplifica el borrado de
   tareas (no hay que limpiar eventos sinteticos).

3. **Flag `is_all_day` anadido al modelo**: el data model no
   lo preveia pero sin el flag los hitos y eventos de jornada
   completa no son representables (no tendrian sentido con hora
   de inicio). Documentado como desviacion controlada en el
   PHPDoc de la migracion y normalizado en los Form Requests
   a `00:00:00` / `23:59:59`.

4. **Reuso de la Form Request en el componente Livewire**:
   `CalendarView::saveEvent()` instancia
   `StoreCalendarEventRequest`, hace `merge()` con los datos
   del formulario y valida con `validator($data, $rules(),
   $messages())`. Asi hay una sola fuente de verdad para las
   reglas y los mensajes en castellano.

5. **Sin scheduler para recordatorios**: el TODO pedia
   "notificaciones antes de eventos" pero el proyecto no tiene
   scheduler ni queue workers en MVP. La notificacion se envia
   al crear/editar el evento; los recordatorios 24h antes
   quedan como nota para una fase futura.

6. **Lunes como primer dia de la semana**: convencion es-ES.
   El CSS grid usa `grid-cols-7` con orden L M X J V S D.
   `Carbon::MONDAY` y `endOfWeek(Carbon::SUNDAY)` se usan en
   `getMonthRange` y `getWeekRange`.

7. **Sin polling en el calendario**: a diferencia del chat, el
   calendario no necesita actualizarse en tiempo real. No
   hay WebSocket ni `wire:poll`. Esto es coherente con la
   decision de "no workers permanentes" del proyecto.

8. **El sidebar sigue sin enlace a "Calendario"**: el
   calendario se accede desde el header del proyecto, igual
   que el kanban y los documentos. El placeholder "Calendario"
   en `portal-sidebar.blade.php` se mantiene disabled hasta
   una fase futura donde se implemente una vista consolidada
   cross-project (no en MVP).

9. **Vista compartida admin/portal via flag `readOnly`**: el
   mismo componente se monta en ambos lados. La policy de
   `CalendarEventPolicy::create/update/delete` es solo admin,
   y `guardNotReadOnly()` en el componente lanza 403 incluso
   si un cliente manipulara el HTML. Doble barrera.

10. **El sidebar no se ve afectado**: en fase 8 no se anaden
    items al sidebar. El calendario se descubre desde el
    header del proyecto. Esto mantiene la consistencia con
    kanban, documentos y chat.

11. **Asistentes validados contra miembros del proyecto y
    org**: `CalendarQueryService::availableAttendees()` devuelve
    la union de miembros directos del proyecto y miembros de
    la organizacion. `CalendarView::resolveAttendees()` filtra
    los `attendeeIds` contra esta lista antes de persistir.
    Asi un admin no puede invitar a usuarios ajenos al
    proyecto aunque manipule el HTML.

12. **Mensajes de sistema via `ProjectActivityLogger`**: la
    creacion, edicion y eliminacion de eventos quedan
    registradas en el chat del proyecto como mensajes de
    sistema, igual que en fases anteriores. La logica
    centralizada en el servicio facilita la migracion futura
    a Events/Listeners.

## Pendiente (fuera de scope de fase 8)

- **Vista consolidada cross-project**: el TODO mencionaba
  `/portal/calendar` (consolidado) pero con la decision de
  "solo eventos ligados a proyecto" no aplica. En una fase
  futura se podria anadir como un panel lateral con todos los
  proyectos del cliente.
- **Eventos recurrentes**: el TODO no lo pedia explicitamente
  pero seria una adicion natural (semanal, mensual, etc.).
- **Recordatorios 24h antes**: requieren scheduler + queue
  (aunque sea sync). Documentado como nota para fase futura.
- **Drag & drop de eventos**: el kanban lo tiene; el calendario
  no, porque reordenar eventos en el calendario es menos
  comun y complica la gestion de eventos multi-dia.
- **Asistentes con estado de aceptacion**: el TODO no lo
  pedia; los invitados reciben la notificacion pero no pueden
  aceptar/declinar. Es una columna extra en el pivot.
- **Eventos en multiples proyectos**: descartado en MVP.
- **Exportar a iCal**: utilidad comun pero fuera de scope.
- **Vista de agenda (lista vertical cronologica)**: en MVP
  solo mes y semana. La agenda se puede anadir como una vista
  mas del switch.

## Riesgos y notas para fase 9 (PWA)

- `CalendarEvent` y `Task` con `due_date` son candidatos
  naturales para enviar push notifications desde el service
  worker. La estructura ya queda lista: `starts_at` y
  `due_date` son faciles de serializar en el JSON que la PWA
  cacheara.
- El `CalendarQueryService` se puede reutilizar desde el MCP
  server (fase 6 ya cerrada, pero como nota: una tool
  `get_calendar_events(project_id, from, to)` seria trivial de
  anadir).
- La query de "proximos eventos" en el dashboard admin/portal
  se beneficiaria de un widget. Lo dejo como nota para una
  fase posterior (no en MVP).
- El componente Livewire ya esta estructurado para añadir un
  badge de "eventos en los proximos 7 dias" via la propiedad
  computada `upcomingEventsProperty`. Solo hay que
  renderizarlo en el dashboard cuando se quiera.
- El modal de creacion de eventos se beneficia del estado
  reactivo de Livewire. Si en una fase futura se quiere
  soporte para recurrencia o asistentes externos (no usuarios
  de la app), la estructura de `eventForm` admite anadir
  campos sin reescribir el componente.
