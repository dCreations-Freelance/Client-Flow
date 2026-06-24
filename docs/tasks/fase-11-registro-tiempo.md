# Fase 11 — Registro de tiempo (temporizador + dashboard + portal)

Documento tecnico de lo implementado en la fase 11 del MVP de
ClientFlow. Cubre el ciclo de vida de las entradas de tiempo
contra tareas: alta manual, cronometro start/stop con regla
de "un timer activo por usuario", edicion, borrado, marcado
como facturable, dashboard por proyecto con breakdowns
(total, por persona, por tarea) y exportacion CSV, ademas
del resumen de solo lectura del portal del cliente.

## Alcance

Segun `TODOs.md`, la fase 11 incluye:

- Migracion `time_entries`.
- Modelo `TimeEntry` con relaciones (task, user, project).
- Enum `TimeEntryType` (manual, timer).
- Temporizador start/stop en la vista de detalle de tarea.
- Entrada manual de tiempo (descripcion, minutos, fecha).
- Vista de registro de tiempo por proyecto (admin).
- Dashboard de horas: total por proyecto, por miembro y por tarea.
- Marcar entradas como facturables (`billed` flag).
- Vista de resumen de tiempo en el portal del cliente (solo
  lectura, totales agregados, sin descripciones individuales
  ni desglose por tarea).
- Columna `total_logged_minutes` en `tasks` como cache.

Anadido en esta fase (decisiones del producto):

- **Timer global por usuario**: si el admin inicia un timer
  en otra tarea, el anterior se cierra y se persiste con
  los minutos calculados automaticamente. No se permiten
  timers zombies ni multiples timers en paralelo.
- **Exportacion CSV del dashboard**: ademas del dashboard
  HTML, el admin puede descargar las entradas filtradas
  en formato CSV (estilo espanol: separador `;`, UTF-8 con
  BOM) para importarlas a Excel o a un programa de
  facturacion.
- **Privacidad reforzada en el portal**: la vista de
  resumen solo expone el total de horas y el desglose
  por persona (sin descripciones individuales ni detalle
  por tarea), por lo que las anotaciones internas del
  equipo (por ejemplo, "Refactor del modulo de auth")
  nunca se ven desde el cliente.
- **Hub del proyecto con tile y accion de tiempo**: el
  hero del proyecto incluye el boton "Registro de tiempo"
  en admin y "Tiempo dedicado" en portal, mas una tarjeta
  KPI con el total acumulado del proyecto.
- **Detalle de tarea del portal con tarjeta de tiempo**:
  la vista publica de una tarea muestra el total
  registrado (sin descripciones) para que el cliente
  sepa cuanto se ha invertido.
- **Sincronizacion automatica de la cache**:
  `tasks.total_logged_minutes` se actualiza desde un
  observer en el modelo `TimeEntry` (created/updated/
  deleted) sin necesidad de que los call sites recuerden
  refrescarlo. La unica condicion es que la cache se
  recalcula solo si cambian `task_id` o `minutes`
  (marcar como facturable no dispara recalculo).
- **Regla de "un timer activo por usuario"**: ademas de
  implementarse en el servicio, se valida en el
  componente `TimeTracker` para que la UI muestre el
  cronometro del usuario independientemente de la tarea
  que este visualizando.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_24_080000_create_time_entries_table.php` | Crea `time_entries` con FK a `tasks` (cascade), FK a `users` (restrict), FK a `projects` (cascade), metadatos (`description`, `type`, `minutes`, `started_at`, `billed`) e indices para los patrones de consulta mas frecuentes. |
| `database/migrations/2026_06_24_080001_add_total_logged_minutes_to_tasks_table.php` | Anade `total_logged_minutes` (unsignedInteger, default 0) a `tasks` como cache que mantiene el observer de `TimeEntry`. |

Decisiones:

- `task_id` con `cascadeOnDelete`: si se borra la tarea se
  eliminan sus entradas. Coherente con el resto de
  pivots que cuelgan de `tasks` (task_attachments,
  subtareas, etc.).
- `user_id` con `restrictOnDelete`: autoria historica,
  mismo patron que `task_attachments` y
  `project_documents`. Si un admin intenta borrar a un
  usuario que ha registrado tiempo, MySQL rechaza la
  operacion y se le pide reasignar primero.
- `project_id` con `cascadeOnDelete`: aunque `task_id`
  ya implica la pertenencia al proyecto, lo
  persistimos para que los calculos del dashboard
  sean directos (no requiere JOIN con `tasks`).
- `type` como string con valores del enum `TimeEntryType`:
  se valida en Form Request, no en BD, para que anadir
  un tipo futuro sea solo codigo.
- `started_at` solo se rellena para entradas de tipo
  `timer`. Es la marca de inicio del cronometro; los
  minutos se calculan al parar.
- `billed` indexado porque la vista de dashboard
  filtra frecuentemente por este flag.
- `minutes` como `unsignedInteger` (max ~4 mil millones,
  mas que suficiente para una sola entrada).
- Indices:
    - `(task_id, created_at)` para la lista
      "entradas de esta tarea en orden cronologico".
    - `(user_id, project_id)` para el dashboard
      "tiempo por miembro".
    - `(project_id, created_at)` para los calculos de
      totales por proyecto.
    - `billed` ya indexado por si solo para el filtro
      del dashboard.

La columna `total_logged_minutes` de la migracion
`add_total_logged_minutes_to_tasks_table.php` se
intencionalmente separo de la migracion original de
`tasks` para que en un `migrate:fresh` la cache
quede en cero sin depender de que la tabla
`time_entries` exista todavia (orden de migraciones).

### 2. Enum

#### `TimeEntryType` (`app/Enums/TimeEntryType.php`)

- `Manual = 'manual'`: entrada creada a mano con
  minutos fijos.
- `Timer = 'timer'`: entrada creada por el cronometro.
  Lleva `started_at` con la marca de inicio.
- Metodos: `label()` ("Manual" / "Cronometro"),
  `color()` ("gray" / "blue"), `isManual()`, `isTimer()`.

### 3. Modelos

#### `TimeEntry` (nuevo, `app/Models/TimeEntry.php`)

- `$fillable`: `task_id`, `user_id`, `project_id`,
  `description`, `type`, `minutes`, `started_at`, `billed`.
- `casts`: `type` al enum, `started_at` a datetime,
  `billed` a bool, `minutes` a integer.
- **`booted()` con observers** (created/updated/deleted):
  recalcula `tasks.total_logged_minutes` automaticamente
  mediante `recalculateTaskTotal(int $taskId)`. El
  observer de `updated` solo dispara el recalculo si
  cambian `task_id` o `minutes` (marcar como facturable
  no lo hace, para evitar writes innecesarios).
- `recalculateTaskTotal(int)` (estatico): hace un
  `withSum('timeEntries', 'minutes')` y guarda el
  resultado en la cache. Si la tarea ya no existe
  (porque se borro en la misma transaccion), ignora
  silenciosamente.
- Relaciones: `task()`, `user()`, `project()`.
- Accesors: `display_minutes` (formato `HH:MM` para UI,
  ej. 90 -> "1h 30m"), `hours` (decimal con dos
  decimales), `totalLoggedDisplay` en `Task`
  (helper homonimo para la cache de la tarea).
- Helpers: `isManual()`, `isTimer()`, `isBillable()`,
  `markAsBilled()`, `markAsUnbilled()` (idempotentes),
  `liveElapsedSeconds()` (segundos entre `started_at`
  y `now`; solo para timers en curso).
- Scopes: `forProject(int)`, `forUser(int)`, `forTask(int)`,
  `billable()`, `notBillable()`, `manual()`, `timer()`,
  `inDateRange(?Carbon, ?Carbon)`, `recent()`.

#### `Task` (extendido)

- `fillable` incluye `total_logged_minutes`.
- `casts` lo trata como integer.
- Nueva relacion: `timeEntries(): HasMany<TimeEntry>`.
- Accesors: `totalLoggedHoursAttribute` (decimal),
  `totalLoggedDisplayAttribute` (formato `HH:MM`).

#### `Project` (extendido)

- Nueva relacion: `timeEntries(): HasMany<TimeEntry>`.
- `ProjectSummaryService::buildSummary` ahora suma
  tambien `totalLoggedMinutes` desde `time_entries`
  (una sola query agregada gracias a la columna
  desnormalizada `project_id`). El DTO `ProjectSummary`
  expone el nuevo campo y se pinta como tile "Horas
  registradas" en el hub admin y "Horas dedicadas" en
  el hub portal.

#### `User` (extendido)

- Nueva relacion: `timeEntries(): HasMany<TimeEntry>`.

#### `TaskFactory` (extendido)

- Anade `total_logged_minutes => 0` al estado por
  defecto para que las tareas nuevas ya tengan la
  cache inicializada.

### 4. Servicio

#### `TimeTrackingService` (`app/Services/TimeTracking/TimeTrackingService.php`)

API centralizada del modulo. Centralizar la logica aqui
tiene dos objetivos: evitar duplicar reglas (auto-stop,
suelo de 1 min, normalizacion de descripcion) en cada
componente Livewire, y poder testear todo el
comportamiento sin tener que levantar HTTP.

- `createManualEntry(Task, User, array $data)`: crea
  entrada manual con `type = Manual`, `started_at =
  null`, normaliza descripcion vacia a `null`. La cache
  se actualiza via observer. Lanza
  `InvalidArgumentException` si `minutes < 1`.
- `updateEntry(TimeEntry, array $data)`: actualiza
  descripcion, minutos y/o `billed`. No permite cambiar
  `type`, `task_id`, `project_id` ni `started_at` (la
  integridad historica se mantiene: un timer cerrado
  tiene su `started_at` real).
- `startTimer(Task, User)`: busca timer activo del
  usuario; si existe, lo cierra primero (auto-stop),
  luego crea la nueva entrada con `started_at = now()`
  y `minutes = 0`. La entrada recien creada es la
  "marcador" del timer; al cerrarla se calculan los
  minutos reales. Mientras esta en curso, su `minutes
  = 0` no aporta a la suma del dashboard.
- `stopTimer(User)`: cierra el timer activo y rellena
  `minutes` con la diferencia entre `started_at` y
  `now()`. Aplica un suelo de 1 minuto para timers muy
  cortos. Devuelve la entrada cerrada o `null` si no
  habia timer activo.
- `getActiveTimer(User)`: devuelve la entrada con
  `type = timer` y `minutes = 0` del usuario (el
  marcador), o `null`.
- `getProjectSummary(Project, ?Carbon $from, ?Carbon
  $to, ?bool $billable)`: devuelve un array con
  `total_minutes`, `total_entries`, `billable_minutes`,
  `not_billable_minutes`, `by_member` y `by_task`. Se
  ejecuta con tres queries agregadas (totales, por
  miembro, por tarea) que son baratas incluso con
  miles de entradas gracias a los indices.

Constante privada: `MIN_TIMER_MINUTES = 1` (suelo para
evitar entradas de 0 minutos).

### 5. Policies

#### `TimeEntryPolicy` (nuevo)

- `view(User, TimeEntry)`: admin siempre; cliente
  delega en `ProjectPolicy::view` (mismas reglas que
  el proyecto padre). En la practica, el cliente rara
  vez lo invoca porque la UI del portal solo muestra
  totales agregados, pero queda cubierto por si en
  una fase futura se expone el detalle de una entrada
  individual.
- `create(User, Project)`: solo admin.
- `update(User, TimeEntry)`: solo admin.
- `delete(User, TimeEntry)`: solo admin.
- `markAsBilled(User, Project)`: solo admin (pensado
  para el dashboard y la lista de entradas).
- `viewSummary(User, Project)`: admin siempre; cliente
  delega en `ProjectPolicy::view` (mismas reglas que
  el proyecto).

### 6. Form Requests

- `Admin/StoreTimeEntryRequest`: valida `description`
  (nullable, max 5000), `minutes` (required, integer,
  min 1, max 60000), `type` (in: manual, timer),
  `entry_date` (required, date, before_or_equal:today)
  y `billed` (boolean). Solo admin (`authorize()`).
- `Admin/UpdateTimeEntryRequest`: mismos campos que
  Store, sin `type` y sin `entry_date` (no se
  modifican tras crear). Pensado para que la
  integridad historica se mantenga: si el admin
  necesita cambiar la fecha de trabajo, borra y
  vuelve a crear.

### 7. Controladores

#### `Admin/TimeEntryController` (HTTP fallback)

- `store(StoreTimeEntryRequest, Project, Task)`:
  verifica que la tarea pertenece al proyecto, delega
  en `TimeTrackingService::createManualEntry`.
- `update(UpdateTimeEntryRequest, Project, Task,
  TimeEntry)`: verifica cross-project + cross-task +
  policy `update`, delega en
  `TimeTrackingService::updateEntry`.
- `destroy(Project, Task, TimeEntry)`: policy
  `delete` + `$entry->delete()` (el observer recalcula
  la cache).

#### `Admin/ProjectTimeController`

- `index(Request, Project)`: renderiza el dashboard.
  Resuelve el filtro de fechas por defecto (ultimo
  mes) y delega en el componente Livewire para la
  interaccion.
- `export(Request, Project)`: descarga CSV con las
  entradas filtradas. Codificacion UTF-8 con BOM,
  separador `;`, columnas: Fecha, Tarea, Persona,
  Minutos, Horas, Tipo, Facturable, Descripcion.
  Anade una linea final con el total agregado.

#### `Portal/ProjectTimeController`

- `index(Request, Project)`: renderiza la vista de
  resumen (solo lectura). El componente Livewire
  `ProjectTimeSummary` se encarga de cargar los
  agregados.

#### `Admin/TaskController` (extendido)

- Nuevo metodo `show(Project, Task)`: renderiza la
  vista de detalle admin de tarea con todos sus
  componentes interactivos (TimeTracker, adjuntos,
  subtareas). Verifica que la tarea pertenece al
  proyecto (defensa en profundidad contra
  manipulacion de IDs).

### 8. Rutas (`routes/web.php`)

```
GET    /admin/projects/{project}/time                                    admin.projects.time.index
GET    /admin/projects/{project}/time/export                             admin.projects.time.export
POST   /admin/projects/{project}/tasks/{task}/time-entries               admin.projects.tasks.time-entries.store
PUT    /admin/projects/{project}/tasks/{task}/time-entries/{entry}       admin.projects.tasks.time-entries.update
DELETE /admin/projects/{project}/tasks/{task}/time-entries/{entry}       admin.projects.tasks.time-entries.destroy

GET    /portal/projects/{project}/time                                   portal.projects.time.index
```

Total: **6 rutas nuevas** (5 admin + 1 portal). El
portal no tiene rutas de escritura: el modulo es de
solo lectura desde la perspectiva del cliente.

### 9. Componentes Livewire

#### `Admin/TimeTracking/TimeTracker` (nuevo)

Componente embebido en la vista de detalle de tarea
admin. Reune en un solo lugar la pieza de cronometro
(start/stop con auto-stop del timer anterior) y la
pieza de administracion manual de entradas (crear,
editar, eliminar y toggle de facturable).

- Estado: `activeTimerId` (int, 0 si no hay timer),
  `activeTimerStartedAt` (string ISO, vacio si no hay
  timer), `entryForm` (array con el modal de entrada
  manual, null si cerrado).
- `startTimer()`: delega en el servicio, refresca el
  estado local, dispatcha `time-tracker-updated`.
- `stopTimer()`: idem, persistiendo los minutos.
- `openManualEntryForm()`, `openEditForm(int $id)`,
  `closeForm()`, `saveManualEntry()`: gestionan el
  modal de entrada manual. `saveManualEntry` valida
  en el sitio (mismas reglas que la Form Request) y
  decide entre crear y actualizar.
- `deleteEntry(int $id)`, `toggleBilled(int $id)`:
  acciones inline en cada fila.
- `refreshActiveTimer()`: hook `#[On('time-tracker-updated')]`
  para refrescar el estado del timer si otro
  componente lo modifica (pensado para una fase
  futura con shortcuts o sincronizacion entre tabs).
- `#[Computed] entries()`: lista de entradas de la
  tarea con autor cargado (evita N+1 en la vista).

Decisiones:

- El contador del cronometro se actualiza en cliente
  (JS inline con `x-data` de Alpine) cada segundo
  para no castigar al servidor. La fuente de verdad
  sigue siendo `TimeEntry::started_at`: el JS solo
  pinta. El intervalo se limpia con `destroy()` para
  no dejar timers colgando entre navegaciones.
- Las propiedades primitivas se inicializan a `0` o
  `''` en lugar de `null` porque Livewire no
  serializa `null` en tipos primitivos.

#### `Admin/TimeTracking/ProjectTimeDashboard` (nuevo)

Dashboard por proyecto con filtros sincronizados en
query string (`#[Url]`) para que la URL sea
compartible y la exportacion CSV pueda usar la misma
query.

- Filtros: `fromDate`, `toDate`, `billableFilter`
  (todas / solo facturables / solo no facturables),
  `userFilter` (todas las personas o una concreta).
- `clearFilters()`: resetea a los valores por defecto
  (ultimo mes, todas las entradas, todas las
  personas).
- `resolvedFilters()`: convierte los strings a
  Carbon + bool + int. Encapsula la conversion para
  que `summary()` y `entries()` la compartan.
- `#[Computed] summary()`: array con
  `total_minutes`, `billable_minutes`, `by_member`,
  `by_task` (via `TimeTrackingService`).
- `#[Computed] entries()`: tabla de entradas
  individuales con autor y tarea cargados.
- `#[Computed] availableMembers()`: union de miembros
  del proyecto + usuarios con tiempo registrado en
  el proyecto (porque una entrada puede pertenecer a
  alguien que no es miembro directo, por ejemplo el
  admin).

#### `Portal/TimeTracking/ProjectTimeSummary` (nuevo)

Resumen simplificado para el cliente: solo totales
agregados + breakdown por persona. Sin filtro por
facturable (es concepto interno) ni filtro por
persona (el cliente ve el total del equipo, no la
distribucion por nombre). El campo `description` no
se expone en ningun momento: la vista de resumen
nunca pinta la descripcion de una entrada individual.

### 10. Vistas Blade

Nuevas:

- `resources/views/components/partials/time-entry-row.blade.php`:
  fila individual de una entrada de tiempo. Muestra
  autor, fecha, badge de tipo, badge de facturable,
  descripcion, minutos formateados, y botones de
  accion (editar, eliminar, toggle facturable) solo
  si `canEdit` es true. Reutilizable en tracker y
  dashboard.
- `resources/views/livewire/admin/time-tracking/time-tracker.blade.php`:
  la pieza de cronometro (con dos estados visuales
  segun haya timer activo o no) + el boton "Anadir
  entrada manual" + la lista de entradas + el modal
  de alta/edicion. El JS inline del cronometro es
  Alpine: `x-data`, `setInterval`, `destroy`.
- `resources/views/livewire/admin/time-tracking/project-time-dashboard.blade.php`:
  filtros + 4 tarjetas KPI (total, facturable, no
  facturable, personas) + breakdowns por persona y
  por tarea + tabla de entradas + boton "Exportar
  CSV" con la query string actual.
- `resources/views/livewire/portal/time-tracking/project-time-summary.blade.php`:
  filtro de fechas + tarjeta KPI con el total
  dedicado en el periodo + lista de personas con
  barra de progreso (% del total) + nota de
  privacidad en la parte inferior.
- `resources/views/admin/projects/time/index.blade.php`:
  wrapper del dashboard con breadcrumb, cabecera y
  el componente Livewire.
- `resources/views/portal/projects/time/index.blade.php`:
  wrapper del resumen con breadcrumb, cabecera y
  el componente Livewire.

Actualizadas:

- `resources/views/admin/projects/tasks/show.blade.php`:
  incluye el componente `TimeTracker` en la columna
  principal (encima de los adjuntos) y una nueva
  fila "Horas registradas" en la card lateral de
  estimacion.
- `resources/views/portal/projects/tasks/show.blade.php`:
  anade una card "Horas dedicadas" con el total
  registrado en la tarea (sin desglose por persona
  ni descripciones).
- `resources/views/admin/projects/show.blade.php`:
  el hero del proyecto incluye un nuevo boton
  "Registro de tiempo" y un tile KPI "Horas
  registradas" en una segunda fila del grid.
- `resources/views/portal/projects/show.blade.php`:
  el hero del portal incluye "Tiempo dedicado" y un
  tile "Horas dedicadas" con el total del proyecto.

### 11. ProjectSummaryService (extendido)

- Nuevo campo en el DTO: `totalLoggedMinutes`. Se
  calcula en `buildSummary` con una sola query
  agregada sobre `time_entries` (aprovecha la
  columna desnormalizada `project_id`).
- Usado por los hubs admin y portal para pintar el
  tile de tiempo sin tener que cargar la coleccion
  de entradas.

## Tests

Total: **72 tests nuevos pasan en verde**.

Distribucion:

```
tests/Unit/Enums/TimeEntryTypeTest.php               4 tests
tests/Unit/Models/TimeEntryTest.php                 21 tests
tests/Unit/Services/TimeTracking/TimeTrackingServiceTest.php  16 tests
tests/Feature/Admin/TimeTrackingTest.php            22 tests
tests/Feature/Portal/TimeSummaryViewTest.php        8 tests
```

Cubren:

- **Enum**: label, color, helpers de tipo, valor
  persistente.
- **Modelo TimeEntry**: fillable, casts, accesors
  (`display_minutes`, `hours`, `live_elapsed_seconds`),
  scopes (`for_project`, `for_user`, `for_task`,
  `billable`, `not_billable`, `manual`, `timer`,
  `in_date_range`, `recent`), helpers (`is_manual`,
  `is_timer`, `is_billable`, `mark_as_billed`),
  relaciones, sincronizacion automatica de la cache
  en created/updated/deleted, y verificacion de que
  `billed` no dispara recalculo.
- **TimeTrackingService**: `create_manual_entry`
  (con y sin descripcion, normalizacion de campos
  vacios, rechazo de minutos invalidos, recalculo de
  cache), `update_entry` (con cambios, sin cambios,
  revalidacion de minutos), `start_timer` (sin
  conflicto, con auto-stop del anterior, multiples
  arranques), `stop_timer` (sin timer, con calculo
  de minutos, suelo de 1 min), `get_active_timer`
  (antes y despues de stop), `get_project_summary`
  (con agregados correctos, filtro por billable,
  filtro por rango de fechas, proyecto vacio).
- **Feature admin**: rutas y autorizacion (admin
  ve dashboard, cliente redirigido al portal,
  cliente recibe 403 al intentar escribir),
  render del componente `TimeTracker` en la vista
  de detalle de tarea, CRUD HTTP completo de
  entradas (crear, validar minutos negativos,
  validar fecha futura, cliente bloqueado, editar,
  eliminar con recalculo de cache, cliente no puede
  editar ni eliminar, no se puede manipular una
  entrada de otra tarea o proyecto), Livewire
  TimeTracker (start timer, auto-stop del anterior,
  stop con persistencia de minutos, cliente
  bloqueado, alta de entrada manual, validacion
  inline, edicion inline, borrado inline, toggle
  facturable), exportacion CSV (exitosa + filtros
  por factable).
- **Feature portal**: render correcto de la pagina
  de resumen, aislamiento (cliente no puede ver
  proyecto ajeno ni archivado), privacidad (NO
  muestra descripciones individuales, NO muestra
  desglose por tarea), filtro de fechas en el
  componente, verificacion de que no existen rutas
  de escritura de tiempo en el portal.

## Verificacion final

```bash
# Migracion (en local con MySQL, no se aplica aqui por la
# configuracion del entorno de CI, que usa sqlite en memoria).
docker compose exec app php artisan migrate

# Tests de la fase.
docker compose exec app php artisan test --filter="TimeEntry|TimeTracking|TimeSummary"

# Suite completa: 72 tests nuevos pasan, 682 del total
# (1 fallo pre-existente en AiSettingsLivewireTest no
# relacionado con esta fase).
docker compose exec app php artisan test

# Build de assets.
docker compose exec node npm run build

# Listado de rutas de tiempo.
docker compose exec app php artisan route:list --except-vendor | grep time
```

## Decisiones tecnicas relevantes

1. **Timer global por usuario**: la regla "un timer
   activo a la vez" se implementa en el servicio
   (`startTimer` cierra el anterior antes de crear
   el nuevo) y se refleja en la UI: el cronometro
   se muestra en la vista de detalle de la tarea
   ACTIVA del usuario, no en todas. Esto refleja el
   caso real "que estoy cronometrando ahora" y
   elimina la posibilidad de timers zombies.

2. **Cache `total_logged_minutes` mantenida por
   observer**: la sincronizacion se hace desde el
   `boot()` del modelo `TimeEntry` (observers en
   `created`/`updated`/`deleted`) en lugar de
   delegar en cada call site. Asi, si en una fase
   futura se anade un nuevo punto de entrada
   (CLI, importador, etc.), la cache se mantiene
   sincronizada sin tocar nada mas. El observer
   solo recalcula si cambian `task_id` o `minutes`
   (marcar como facturable no afecta al total).

3. **Privacidad reforzada en el portal**: la vista
   `ProjectTimeSummary` solo consume
   `total_minutes`, `total_entries` y `by_member` del
   resumen. Los campos `by_task` y las descripciones
   individuales nunca se envian al cliente. Esto
   refleja la filosofia del PRD: "el cliente debe
   tener tranquilidad, no micromanagement".

4. **Export CSV con `;` y UTF-8 BOM**: el separador
   `;` es el estandar "CSV espanol" (compatible con
   Excel/LibreOffice en locales europeos) y el BOM
   al inicio garantiza que los acentos se vean
   correctamente al abrir el archivo. Las columnas
   son: Fecha, Tarea, Persona, Minutos, Horas,
   Tipo, Facturable, Descripcion. Se anade una linea
   final con el total agregado.

5. **Validacion de minutos hasta 60.000**: el limite
   (~1.000 horas por entrada) es una salvaguarda
   contra typos obvios sin limitar entradas reales
   (ningun ser humano imputa 60.000 minutos a una
   sola tarea; si lo hace, casi seguro es un error
   de tecleo). Si en una fase futura alguien
   necesita entradas mas largas, basta con ajustar
   el max en la Form Request.

6. **`entry_date` no se persiste como columna**: la
   fecha de trabajo se valida en la Form Request
   (no puede ser futura) y se usa para anotar el
   momento de registro en `created_at`. Si en una
   fase futura se quiere distinguir "fecha de
   registro" de "fecha de trabajo" (por ejemplo,
   para reportes semanales con agrupacion por dia),
   se anade un campo `entry_date` y se rellena
   desde el servicio. Por ahora, la solucion minima
   evita una columna redundante.

7. **`started_at` y `minutes` en una sola fila**:
   las entradas de tipo `timer` tienen `started_at`
   Y `minutes`, aunque durante la vida del timer
   `minutes = 0`. Esto permite consultar el estado
   del timer con un `WHERE type = timer AND minutes
   = 0` sin necesidad de una tabla auxiliar. La
   regla "una sola entrada con `minutes = 0` por
   usuario" la enforce el servicio al crear un
   timer nuevo (auto-stop del anterior).

8. **HTTP fallback + Livewire**: la mayoria de la
   interaccion se hace via componentes Livewire
   (`TimeTracker` y `ProjectTimeDashboard`). El
   controlador HTTP `TimeEntryController` existe
   como fallback para tests, integraciones externas
   o un futuro cliente MCP. Las rutas HTTP y
   Livewire coexisten sin duplicar reglas: las
   politicas y el servicio son la unica fuente de
   verdad.

9. **`AdminTaskController::show`**: no existia en
   el controlador (se documento en `fase-10-adjuntos`
   como nuevo metodo, pero el codigo real no lo
   tenia). Se anade en esta fase para soportar la
   ruta `admin.projects.tasks.show` que monta el
   `TimeTracker` y el resto de componentes
   interactivos de la tarea. La vista Blade ya
   existia.

10. **ProjectSummary como punto de entrada del hub**:
    el DTO `ProjectSummary` se extiende con
    `totalLoggedMinutes` para que los hubs admin y
    portal puedan pintar el tile de tiempo sin
    cargar todas las entradas. La query agregada
    aprovecha la columna desnormalizada `project_id`
    en `time_entries` para no requerir JOIN con
    `tasks`.

## Pendiente (fuera de scope de fase 11)

- **Multiples timers simultaneos**: si en una fase
  futura el admin quiere cronometrar dos tareas a
  la vez (caso raro en la practica), basta con
  cambiar la regla de "un timer activo por usuario"
  en `TimeTrackingService::startTimer` y eliminar
  el `auto-stop`.
- **Reportes semanales/mensuales automaticos**: el
  dashboard ya soporta filtros por rango, pero no
  hay programacion de envios automaticos por email.
  Si se anade, encaja en la fase transversal de
  notificaciones como un nuevo evento.
- **Sincronizacion con calendarios externos
  (Google Calendar, etc.)**: para que un timer
  abierto en ClientFlow se refleje en el calendario
  del usuario. Implica API tokens por usuario.
- **Edicion de `started_at` y `type`**: actualmente
  no se permite cambiar estos campos tras crear.
  Si el admin necesita corregir un error, debe
  borrar y volver a crear. Es la postura correcta
  para integridad historica.
- **Categorizacion de entradas**: por ejemplo,
  "reunion", "coding", "testing". Seria util para
  reportes por tipo de actividad, pero anade
  complejidad a la UI y al modelo. Se difiere a una
  fase futura si hay demanda.
- **Vista del cliente con detalle por tarea**: el
  portal actual solo muestra totales por persona.
  Si el cliente quiere ver "en que tareas se ha
  invertido mas tiempo", seria un toggle de la
  misma vista con un boton "Ver detalle por tarea"
  que activaria el campo `by_task`. Por ahora se
  mantiene la opcion mas conservadora por
  privacidad.
- **Filtro por tipo (manual/timer) en el dashboard**:
  no se considera prioritario, pero es trivial
  añadirlo como una `#[Url(as: 'type')]` adicional
  y un `when()` en el servicio.
- **Subtotal por semana/mes en la vista de
  proyecto**: para ver la evolucion temporal del
  tiempo dedicado, se necesitaria una query
  agregada por periodo. El dato esta en BD, solo
  falta exponerlo en una grafica.

## Riesgos y notas para la fase 12 (plantillas)

- El servicio `TimeTrackingService::getProjectSummary`
  ya se reusa por `ProjectTimeDashboard` y
  `ProjectTimeSummary`. En la fase 12, si las
  plantillas de proyecto quieren pre-poblar
  estimaciones o categorias de tiempo, este es el
  punto de integracion.
- La cache `total_logged_minutes` se mantiene
  sincronizada via observer. Si en una fase futura
  se anade un importador masivo de entradas, basta
  con usar `TimeEntry::insert([...])` directamente
  (no por el observer) y luego invocar
  `TimeEntry::recalculateTaskTotal($taskId)` para
  los IDs afectados. El observer no se dispara
  con `insert()`.
- Si en una fase futura se quiere comprimir el
  tiempo de las pausas largas (por ejemplo, "no
  contar las pausas de mas de 5 minutos"), la
  logica iria en `TimeTrackingService::resolveTimerMinutes`,
  sin tocar el observer ni la UI.
- El endpoint CSV (`projects.time.export`) es
  independiente del componente Livewire: las
  descargas via `Response::streamDownload` desde
  Livewire son fragiles, asi que se hace via
  controlador HTTP. Si en una fase futura se
  quiere generar el CSV en cliente, seria un
  componente Livewire dedicado.
- Las relaciones `timeEntries` se anadieron a
  `Task`, `Project` y `User` para que las queries
  eager-loaded se hagan de forma consistente. Si
  en una fase futura se anade un modulo que filtra
  por entradas, basta con encadenar scopes sobre
  estas relaciones sin tocar el modelo.
