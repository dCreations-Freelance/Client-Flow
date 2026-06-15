# Ajustes post-Fase 3 — Progreso automatico, traducciones y responsive

Documento tecnico de los ajustes aplicados tras cerrar la fase 3
para corregir dos problemas reportados:

1. El progreso del proyecto se calculaba a partir de un campo
   manual `progress` que el admin editaba a mano. Ahora se calcula
   automaticamente desde las tareas.
2. El kanban tenia problemas de responsive y los titulos de
   algunas "estados" estaban en ingles.

## Alcance

- Eliminar la columna `progress` de `projects`.
- Calcular el progreso del proyecto como porcentaje de tareas
   raiz completadas.
- Quitar toda la infra de "progress manual" (controlador, ruta,
   componente Livewire, request).
- Traducir las columnas default del kanban al castellano.
- Traducir los tipos de tarea al castellano.
- Arreglar el responsive del kanban en pantallas pequenas.

## Cambios por capa

### 1. Progreso automatico desde tareas

#### 1.1 Migracion

`2026_06_15_120000_remove_progress_from_projects_table.php`:
elimina la columna `progress` de `projects`. Es reversible: en
`down()` se recrea como `unsignedTinyInteger`.

#### 1.2 Modelo `Project`

- Quita `progress` de `$fillable` y `$casts`.
- Reemplaza el accessor `progressPercent` (que leia del campo)
  por tres accessors calculados:
  - `tasks_progress_percent` (int 0-100): porcentaje de tareas
    raiz completadas. Si no hay tareas, devuelve 0.
  - `completed_tasks_count`: numero de tareas raiz con
    `completed_at` no nulo.
  - `total_tasks_count`: numero de tareas raiz del proyecto.
- Anade la relacion `rootTasks()` (HasMany con `whereNull
  ('parent_id')`) para soportar los conteos sin N+1.

#### 1.3 Eliminacion de la infra manual

Archivos eliminados:

- `app/Http/Requests/Admin/UpdateProjectProgressRequest.php`
- `app/Http/Controllers/Admin/ProjectProgressController.php`
- `app/Livewire/Admin/Project/ProjectProgress.php`
- `resources/views/livewire/admin/project/project-progress.blade.php`
- `tests/Feature/Admin/ProjectProgressTest.php`

Cambios en archivos existentes:

- `app/Http/Requests/Admin/UpdateProjectRequest.php`: quita
  `progress` de `rules()`, `prepareForValidation()` y
  `messages()`.
- `app/Http/Controllers/Admin/ProjectController.php`: quita la
  asignacion `$data['progress'] = 0` en `store()`.
- `routes/web.php`: quita el import y la ruta
  `admin.projects.progress.update` (PATCH).
- `database/factories/ProjectFactory.php`: quita `progress` del
  `definition()` y del estado `completed()`.

#### 1.4 Vistas

Todas las vistas que mostraban `$project->progress` ahora usan
`$project->tasks_progress_percent`:

- `admin/projects/index.blade.php`
- `admin/projects/show.blade.php` (tambien muestra
  "X de Y tareas completadas" o "Sin tareas todavia")
- `admin/dashboard.blade.php`
- `portal/dashboard.blade.php`
- `portal/projects/index.blade.php`
- `portal/projects/show.blade.php` (tambien muestra
  "X de Y tareas completadas" o "Sin tareas todavia")
- `portal/organizations/show.blade.php`

Los formularios `create` y `edit` ya no tienen el campo
`progress` (de hecho nunca lo tuvieron en `create`; en `edit`
se elimino).

#### 1.5 Tests

- `tests/Unit/Models/ProjectTest.php`: renombrado `test_casts_...`
  y anadidos 3 tests nuevos para el calculo automatico:
  - `progreso_calculado_devuelve_cero_sin_tareas`
  - `progreso_calculado_desde_tareas_raiz` (verifica que las
    subtareas NO cuentan)
  - `progreso_devuelve_cien_si_todas_las_raiz_estan_completadas`
- `tests/Feature/Admin/ProjectManagementTest.php`: actualizado
  para usar `tasks_progress_percent` en vez del campo eliminado.

### 2. Traducciones

#### 2.1 Columnas default del kanban

`app/Services/DefaultBoardColumnsService.php`:

| Antes | Despues |
|---|---|
| To Do | Por hacer |
| In Progress | En curso |
| Review | En revision |
| Done | Hecho |

Los colores se mantienen (`#94A3B8`, `#2563EB`, `#D97706`,
`#16A34A`) y las posiciones 0..3 tambien.

#### 2.2 Tipos de tarea

`app/Enums/TaskType.php`:

| Antes | Despues |
|---|---|
| Feature | Caracteristica |
| Bug | Error |
| Improvement | Mejora |
| Task | Tarea |

#### 2.3 Tests actualizados

- `tests/Unit/Services/DefaultBoardColumnsServiceTest.php`:
  actualizado el assert con los nuevos nombres.
- `tests/Feature/Admin/KanbanTest.php`: actualizado el assert
  del test que verifica las 4 columnas default al crear
  proyecto.

### 3. Responsive del kanban

Cambios en
`resources/views/livewire/admin/kanban/kanban-board.blade.php`:

- **Contenedor del tablero**: en lugar de `-mx-4 flex gap-4
  overflow-x-auto px-4 pb-4` con `w-72` fijo, ahora es
  `-mx-4 flex gap-3 overflow-x-auto px-4 pb-4 sm:mx-0 sm:gap-4
  sm:px-0` con `w-[85vw] sm:w-72` por columna. Esto hace que en
  movil cada columna ocupe el 85% del viewport, dejando un
  pequeno margen para que se intuya que hay scroll horizontal.
- **Filtros**: la barra de filtros pasa de
  `flex flex-wrap items-center gap-2` a
  `grid grid-cols-1 gap-2 ... sm:flex sm:flex-wrap
  sm:items-center`. Asi en movil se apilan en columna sin
  desbordes.
- **Cabecera de cada columna**: el contenedor del nombre
  recibe `min-w-0 flex-1` y el `h3` se trunca con `truncate`.
  El contador `(N)` y el boton "+" reciben `shrink-0` para que
  no se aplasten con nombres largos.
- **Titulo de la card**: el boton del titulo recibe `min-w-0
  flex-1` y el texto se rompe con `line-clamp-3 break-words` para
  que los titulos largos no rompan la card.
- **Footer de la card** (avatar + boton Eliminar): cambia de
  `flex items-center justify-between` a `flex flex-col gap-1.5
  sm:flex-row sm:items-center sm:justify-between`. En movil se
  apila; en sm+ mantiene la fila.
- **Modal de tarea**: el `flex items-center justify-end
  gap-3` del footer se cambia a `flex flex-col-reverse gap-2
  ... sm:flex-row sm:items-center sm:justify-end`. Asi en
  movil el boton primario queda abajo (mas comodo para el
  pulgar) y el secundario arriba.

Cambios en `resources/views/admin/projects/board.blade.php`:

- El header pasa a `lg:flex-row lg:items-center lg:justify-between`
  (antes era `sm:flex-row`). El proyecto con un nombre largo
  ya no empuja el breadcrumb.
- El `h1` recibe `truncate` y `min-w-0` en su contenedor para
  nombres largos.
- El boton "Detalle del proyecto" redundante se elimino (ya
  esta el breadcrumb "Volver al detalle").

## Tests

Total: **160 tests, 389 aserciones, todos en verde**.

Cambios netos: 0 nuevos tests (los de progress se sustituyen por
los nuevos tests de `tasks_progress_percent`).

Cubren:
- Calculo de progreso sin tareas (0% con texto "Sin tareas").
- Calculo con tareas mixtas: raiz pendientes, raiz completadas
  y subtareas (estas ultimas no cuentan).
- Progreso 100% cuando todas las raiz estan completadas.
- Nombres espanol de columnas default en `create` y en el
  servicio.
- Todos los tests anteriores siguen pasando (auth,
  organizations, invitations, projects, members, archive,
  progress, board columns, tasks CRUD, move, complete, reopen,
  portal kanban, dashboards, etc).

## Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:
- Migraciones fresh + seed: OK.
- 160 tests pasan, 389 aserciones.
- Build Vite sin warnings.
- 57 rutas custom (1 menos que en fase 3, la ruta
  `projects.progress.update` se elimino).

## Decisiones tecnicas relevantes

1. **El progreso se calcula siempre desde tareas**: opcion A del
   plan. Se elimino la posibilidad de override manual porque
   introducia desincronizacion. Si en una fase futura se quiere
   "avance forzado" para un proyecto sin tareas, sera un campo
   separado (por ejemplo `manual_progress`) o un checkbox
   "marcar como completado manualmente".

2. **Subtareas no cuentan en el progreso raiz**: el progreso
   refleja "tareas que son entregables principales". Las
   subtareas son un detalle de una tarea padre; su avance se
   ve localmente en la mini-barra `2/5` de la card.

3. **0% cuando no hay tareas, no 100%**: asi el admin entiende
   que el proyecto aun no arranca. El texto "Sin tareas
   todavia" refuerza la lectura.

4. **Columnas 85vw en movil**: en lugar de forzar un ancho fijo
   que descuadre el scroll horizontal, se usa un porcentaje del
   viewport que siempre cabe con un pequeno hint de la siguiente
   columna. En sm+ se mantiene el `w-72` (288px) original.

5. **Texto de las traducciones fijado a espanol sin i18n**: el
   resto del proyecto ya usa textos hard-coded en castellano
   (form requests, mensajes de error, etc). Mantener la misma
   estrategia evita introducir complejidad de i18n en MVP.

6. **Eliminacion total de la infra de progress manual**: en
   vez de dejar la posibilidad de re-habilitar (un flag, un
   settings), se borraron los archivos. El historial de git
   los conserva si se necesitan en el futuro.

## Pendiente (fuera de scope de este ajuste)

- Drag handle separado en cada card (ahora toda la card es
  draggable). Mejora la UX especialmente en touch.
- Toggle kanban / lista en el mismo componente (la vista lista
  se anadio en el TODO de fase 3 pero no se implemento).
- i18n real: ahora todos los textos estan hard-coded en
  castellano. La migracion a un sistema i18n es una
  pre-futura, no una urgencia.
- Animaciones de entrada al arrastrar (drag preview con
  `draggable` nativo es limitado).
