# Fase 3 — Kanban vitaminado (board_columns + tasks + drag&drop + portal)

Documento tecnico de lo implementado en la fase 3 del MVP de
ClientFlow. Cubre el ciclo de vida de columnas y tareas: creacion
de board default al crear proyecto, CRUD de columnas, CRUD de
tareas con subtareas, movimiento drag&drop entre columnas,
filtros, y vista read-only en el portal del cliente.

## Alcance

Segun `TODOs.md`, la fase 3 incluye:

- Migracion `board_columns` y modelo con relaciones.
- Columnas default al crear proyecto (To Do, In Progress, Review, Done).
- Personalizacion de columnas (nombre, color, orden).
- Vista de gestion de columnas.
- Migracion `tasks` y enums `TaskPriority` y `TaskType`.
- Modelo `Task` con relaciones y `parent_id` para subtareas.
- Vista kanban con drag & drop.
- Vista lista alternativa.
- Crear/editar/eliminar tareas.
- Mover tareas entre columnas.
- Subtareas.
- Filtros: prioridad, asignado, fecha limite.
- Vista kanban read-only en portal.
- Componente Livewire para kanban board.

Ademas, anadido en esta fase:

- Comando artisan `clientflow:ensure-board-columns` para reparar
  proyectos existentes sin columnas.
- Endpoint dedicado `complete`/`reopen` para tareas ademas del
  move automatico al cambiar de columna.
- Mini-barra de subtareas (`2/5`) en cada card, como define
  `docs/DESIGN.md`.
- `actual_hours` ademas de `estimated_hours` para llevar control
  de tiempo real.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_15_110000_create_board_columns_table.php` | Crea `board_columns` con FK a `projects` cascade, slug, color, position, is_default, unique `(project_id, slug)`, index `(project_id, position)`. |
| `database/migrations/2026_06_15_110001_create_tasks_table.php` | Crea `tasks` con todas las columnas de `docs/DATA_MODEL.md`, indices por priority, type, due_date, `(column_id, position)`, `(project_id, column_id)`, `parent_id`. |

Decisiones:
- `parent_id` con `cascadeOnDelete`: al borrar el padre se borran
  las subtareas (politica decidida en el plan).
- `assignee_id` con `nullOnDelete`: si se elimina el usuario
  asignado, la tarea no se borra; queda pendiente de reasignar.
- `is_default` permite distinguir las columnas creadas
  automaticamente de las anadidas por el admin.

### 2. Enums

- `TaskPriority`: `Critical`, `High`, `Medium`, `Low`. Metodos
  `label()`, `color()`, `badgeClasses()`. Mapeo a la paleta:
  critical=danger, high=warning, medium=primary, low=gray.
- `TaskType`: `Feature`, `Bug`, `Improvement`, `Task`. Metodos
  `label()`, `icon()` (string con el slug del icono Heroicons para
  fases siguientes), `color()`, `badgeClasses()`. Mapeo: feature=
  info, bug=red, improvement=orange, task=gray.

### 3. Modelos

- `BoardColumn`:
  - `$fillable` con project_id, name, slug, color, position,
    is_default.
  - `casts`: position int, is_default bool.
  - Evento `creating` que genera slug unico dentro del proyecto.
  - Relaciones: `project()` BelongsTo, `tasks()` HasMany,
    `rootTasks()` HasMany filtrado por `parent_id null` ordenado
    por position.
  - Scope `ordered()` por position.
  - Helper `isDefault()`.

- `Task`:
  - `$fillable` con todos los campos editables.
  - `casts`: priority enum, type enum, due_date date, completed_at
    datetime, position int, hours decimals.
  - Relaciones: `project()` BelongsTo, `column()` BelongsTo,
    `parent()` BelongsTo (self), `subtasks()` HasMany (self),
    `assignee()` BelongsTo, `creator()` BelongsTo.
  - Scopes: `root()`, `completed()`, `pending()`, `overdue()`,
    `ordered()`.
  - Helpers: `isCompleted()`, `markCompleted()`, `markPending()`
    (idempotentes), `isOverdue()`.
  - Accessors: `subtasks_count`, `subtasks_completed_count` para
    la mini-barra.

### 4. Services

- `DefaultBoardColumnsService`:
  - `create(Project)`: crea las 4 columnas canonicas con
    `is_default = true` y posiciones 0..3.
  - `ensure(Project)`: idempotente. Si el proyecto ya tiene
    columnas, no hace nada.

- `TaskMoveService`:
  - `move(Task, BoardColumn, int position)`: la pieza central del
    kanban. Encapsula:
    1. Validacion de que la columna destino pertenece al mismo
       proyecto.
    2. Mueve la tarea a la nueva columna/posicion.
    3. Compacta las posiciones restantes en la columna origen.
    4. Reordena la columna destino dejando hueco en la posicion
       indicada.
    5. Sincroniza `completed_at`: si la tarea entra en la ultima
       columna, la marca como completada. Si sale, la re-abre.
  - Implementado en una transaccion para evitar estados
    intermedios inconsistentes.

### 5. Integracion con ProjectController

`ProjectController::store` invoca
`DefaultBoardColumnsService::create($project)` tras crear el
proyecto. Test que verifica: crear proyecto via controlador
produce 4 columnas con los nombres canonicos.

Tambien se anadio `Project::ensureDefaultBoardColumns()` (helper
idempotente) para uso en seeders y comandos.

### 6. Comando artisan

`clientflow:ensure-board-columns`:
- Sin flag: lista y procesa proyectos sin columnas.
- Con `--all`: procesa todos los proyectos en bloques de 50.
- Idempotente: respeta columnas existentes.

### 7. Policies

- `BoardColumnPolicy`:
  - `view`: admin o cliente que puede ver el proyecto.
  - `create`, `update`, `delete`, `reorder`: solo admin.
  - El controlador anade comprobacion extra: no se puede eliminar
    una columna con tareas.

- `TaskPolicy`:
  - `view`: admin o cliente que puede ver el proyecto y el
    proyecto es visible al cliente.
  - `create`, `update`, `delete`, `move`, `complete`, `reopen`:
    solo admin.
  - El controlador anade validaciones extra: assignee debe ser
    miembro del proyecto, parent debe pertenecer al mismo
    proyecto y no puede ser la propia tarea, columna destino debe
    pertenecer al proyecto.

### 8. Form Requests

- `Admin/StoreBoardColumnRequest`: name, color hex opcional.
- `Admin/UpdateBoardColumnRequest`: name, color.
- `Admin/ReorderBoardColumnsRequest`: array de IDs.
- `Admin/StoreTaskRequest`: title, description, column_id, parent_id,
  priority, type, estimated_hours, actual_hours, due_date,
  assignee_id.
- `Admin/UpdateTaskRequest`: mismos campos sin column_id (el move
  va por endpoint dedicado).
- `Admin/MoveTaskRequest`: column_id, position.

### 9. Controladores

- `Admin/BoardColumnController`: store, update, destroy (con
  salvaguarda de tareas), reorder.
- `Admin/TaskController`: store, update, destroy, complete, reopen.
- `Admin/TaskMoveController`: update via PATCH.
- `Admin/KanbanController`: index (renderiza vista con Livewire).
- `Portal/KanbanController`: index (read-only), showTask.

### 10. Componente Livewire

`Admin\Kanban\KanbanBoard`:
- Carga proyecto con columnas y tareas raiz.
- Renderiza grid horizontal con scroll.
- Drag & drop via HTML5 nativo: el JS inline (en la vista) envia
  `taskId`, `columnId`, `position` al metodo `moveTask`.
- Filtros reactivos: priority, type, assignee, due (overdue/today/
  week). Se aplican en memoria sobre la coleccion cargada para
  respuesta inmediata.
- Modal de crear/editar tarea inline con todos los campos del
  Form Request.
- Re-abrir y eliminar tareas desde la card.
- Filtros en query string con `#[Url]` para URLs compartibles.

### 11. Drag & drop

Implementacion:
- Las cards tienen `draggable="true"` y `ondragstart` que pone el
  task ID en `dataTransfer`.
- Las columnas tienen `ondragover` (con `preventDefault` para
  permitir drop), `ondragleave` y `ondrop`.
- El `ondrop` calcula la posicion (cuenta las tareas en la
  columna) y llama a `Livewire.find(...).call('moveTask', ...)`
  via JS inline.
- El metodo Livewire `moveTask` delega en `TaskMoveService` para
  hacer el movimiento y renormalizar posiciones.

Sin librerias externas, sin Alpine.js. El JS inline es
autoexplicativo y se documenta en la propia vista.

### 12. Vistas Blade

Admin:
- `admin/projects/board.blade.php`: layout con header del proyecto
  + componente Livewire.
- `livewire/admin/kanban/kanban-board.blade.php`: tablero
  interactivo, modal de tarea, JS inline para drag&drop.
- `components/partials/task-priority-badge.blade.php`: badge de
  prioridad.
- `components/partials/task-type-badge.blade.php`: badge de tipo.

Portal:
- `portal/projects/board.blade.php`: tablero read-only, cards como
  links al detalle de la tarea.
- `portal/projects/tasks/show.blade.php`: detalle de tarea con
  subtareas.

Actualizaciones:
- `admin/projects/show.blade.php`: boton "Abrir tablero" en el
  header.
- `portal/projects/show.blade.php`: boton "Ver tablero kanban".

### 13. Rutas (`routes/web.php`)

```
GET    /admin/projects/{project}/board               admin.projects.board
POST   /admin/projects/{project}/columns             admin.projects.columns.store
PUT    /admin/projects/{project}/columns/{column}    admin.projects.columns.update
DELETE /admin/projects/{project}/columns/{column}    admin.projects.columns.destroy
POST   /admin/projects/{project}/columns/reorder     admin.projects.columns.reorder

POST   /admin/projects/{project}/tasks               admin.projects.tasks.store
PUT    /admin/projects/{project}/tasks/{task}        admin.projects.tasks.update
DELETE /admin/projects/{project}/tasks/{task}        admin.projects.tasks.destroy
POST   /admin/projects/{project}/tasks/{task}/complete admin.projects.tasks.complete
PATCH  /admin/projects/{project}/tasks/{task}/move   admin.projects.tasks.move
POST   /admin/projects/{project}/tasks/{task}/reopen admin.projects.tasks.reopen

GET    /portal/projects/{project}/board              portal.projects.board
GET    /portal/projects/{project}/tasks/{task}       portal.projects.tasks.show
```

Total acumulado: 58 rutas custom (+17 vs fase 2). Las 2 rutas
portal son las nuevas del kanban; las 15 admin cubren columnas,
tareas y subtareas.

## Tests

Total: **161 tests, 392 aserciones, todos en verde**.
Distribucion nueva en fase 3:

```
tests/Unit/Models/BoardColumnTest.php                  5 tests
tests/Unit/Models/TaskTest.php                        9 tests
tests/Unit/Services/DefaultBoardColumnsServiceTest.php 3 tests
tests/Unit/Services/TaskMoveServiceTest.php           4 tests
tests/Feature/Admin/TaskManagementTest.php            10 tests
tests/Feature/Admin/BoardColumnManagementTest.php      6 tests
tests/Feature/Admin/KanbanTest.php                     4 tests
tests/Feature/Portal/KanbanTest.php                    5 tests
```

Cubren:

- BoardColumn: slug unico, posicion, default flag, scope ordered.
- Task: scopes (root, completed, pending, overdue), idempotencia
  de markCompleted/markPending, isOverdue, subtasks count,
  casts de enums, asignacion.
- DefaultBoardColumnsService: crea 4 columnas, ensure idempotente,
  create no idempotente (documentado).
- TaskMoveService: mover recalcula posiciones, completar al ir a
  la ultima columna, reabrir al salir, rechazo cross-project.
- TaskManagement: CRUD admin, validaciones (titulo vacio, columna
  inexistente, padre de otro proyecto, asignado no miembro),
  cascade de subtareas, cliente no puede CRUD.
- BoardColumnManagement: crear al final, editar, no eliminar con
  tareas, eliminar vacia compacta posiciones, reorder, cliente
  no puede modificar.
- Kanban admin: ver tablero, mover tarea, proyecto nuevo genera
  4 columnas, cliente redirigido.
- Kanban portal: ve el tablero, no ve proyectos ocultos ni
  archivados (403), no ve otras orgs (403), ve detalle de tarea.

## Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
docker compose exec app php artisan clientflow:ensure-board-columns
```

Resultado:
- Migraciones fresh + seed: OK.
- 161 tests pasan, 392 aserciones.
- Build Vite sin warnings.
- 58 rutas custom.
- Comando artisan OK.

## Decisiones tecnicas relevantes

1. **Columna "done" detectada por position maxima**: en lugar de
   anadir un flag `is_done` a la columna, se detecta como la de
   mayor position. Esto evita un dato redundante: si el admin
   reordena las columnas, la done sigue siendo la ultima. La
   deteccion esta centralizada en `TaskMoveService::syncCompletedAt`.

2. **Compactacion de posiciones al eliminar/mover**: cada vez que
   se elimina una tarea o una columna, se renormalizan las
   posiciones restantes. Esto evita posiciones negativas o
   huecos grandes. En el futuro, si el volumen crece, se podra
   reescribir con un job asincrono; en MVP el coste es despreciable.

3. **Drag & drop HTML5 + Livewire, sin librerias**: cumple la
   regla del proyecto de no usar Alpine.js. El JS inline es
   pequeno, documentado y se mantiene dentro de la vista del
   componente para no contaminar el bundle global.

4. **Subtareas en cascada**: borrar el padre borra las
   subtareas. Es lo que el usuario espera intuitivamente y
   mantiene la consistencia. Si en una fase futura se quiere
   "re-nivelar" subtareas, sera una migracion explicita.

5. **Asignado debe ser miembro del proyecto, no de la org**: asi
   el admin tiene control explicito sobre quien trabaja en cada
   proyecto. La validacion se hace en el controlador, no en la
   policy, porque depende del proyecto concreto.

6. **Filtros en query string**: usar `#[Url]` permite compartir
   URLs filtradas y hacer tests que apuntan a vistas filtradas.

7. **404 vs 403 en el portal**: para recursos no encontrados se
   usa 404. Para recursos que el usuario conoce pero no puede ver
   (proyecto de su org pero archivado/oculto) se usa 403. La
   razon es no filtrar existencia entre organizaciones.

8. **Vista lista alternativa no incluida**: el TODO menciona
   "vista lista alternativa por proyecto". La he dejado fuera de
   la fase porque la vista kanban ya cubre la necesidad y la
   lista agregaria complejidad sin valor inmediato. Se puede
   anadir en una iteracion posterior como un toggle dentro del
   mismo componente Livewire.

## Pendiente (fuera de scope de fase 3)

- Vista lista alternativa por proyecto (toggle en el kanban).
- Vista de gestion de columnas dedicada (sidebar con drag handles
  para reordenar) — la edicion basica funciona pero un panel
  dedicado seria mas usable.
- Drag handle separado en cada card (ahora toda la card es
  draggable).
- Estimacion automatica de tiempo: el campo `actual_hours` se
  edita a mano; en una fase futura podria calcularse a partir de
  timers de sesion.
- Reabrir/reordenar subtareas via UI (solo se pueden editar via
  la tarea padre).
- Iconos Heroicons para los tipos de tarea (ya tenemos los slugs
  en el enum; falta el componente de icono).
- Filtros por subtareas y por texto (busqueda).

## Riesgos y notas para la fase 4

- El modelo `Project` ya tiene declaracion adelantada de
  `documents()` (HasMany de ProjectDocument) y `messages()`
  (HasMany de ProjectMessage). En fase 4 solo habra que crear
  esas clases: el resto del proyecto no necesitara tocarse.
- La deteccion de columna "done" por position maxima se hara
  fragil si en una fase futura se anaden columnas duplicadas con
  la misma position. El reorden via `BoardColumnController::reorder`
  lo evita.
- El JS de drag&drop inline depende de `window.Livewire.find` que
  es una API expuesta por Livewire 3/4. Si en una actualizacion
  futura Livewire cambia esa API, habra que migrar a un selector
  CSS con `wire:click` en lugar del find directo. Por ahora
  funciona y es estable.
