# Fase 2 — Projects (CRUD + portal + miembros + progreso + archivar)

Documento tecnico de lo implementado en la fase 2 del MVP de
ClientFlow. Cubre el ciclo de vida de un proyecto: alta, edicion,
gestion de miembros, progreso manual, archivado y vista desde el
portal del cliente.

## Alcance

Segun `TODOs.md`, la fase 2 incluye:

- Migracion `projects` y `project_user` (pivot).
- Enum `ProjectStatus` con seis valores.
- Modelo `Project` con relaciones.
- CRUD de proyectos (admin, asociados a organizacion).
- Listado y detalle de proyectos en admin.
- Listado y detalle de proyectos en portal (cliente), filtrado por
  organizacion y visibilidad.
- Asignacion de miembros a proyecto (anadir y quitar).
- Barra de progreso manual editable.
- Archivar y desarchivar proyectos.

Ademas, anadido en esta fase:

- Vista dedicada `GET /portal/projects` con paginacion.
- Vista de organizacion en portal `GET /portal/organizations/{org}`.
- Dashboard admin y portal actualizados con proyectos recientes.
- Componentes Livewire para edicion inline de progreso y gestion de
  miembros sin recargar pagina.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_15_100000_create_projects_table.php` | Crea `projects` con todas las columnas de `docs/DATA_MODEL.md`. FK a `organizations` con cascade. Slug unico, status indexed, is_visible_to_client indexed, doble index `(organization_id, status)` para filtros habituales. |
| `database/migrations/2026_06_15_100001_create_project_user_table.php` | Pivote con FKs cascade y unique `(project_id, user_id)`. |

Decisiones:
- Se anadio un indice compuesto `(organization_id, status)` porque es
  el patron de filtrado mas comun en listados.
- `cover_path` se crea en la migracion aunque el upload se difiere
  a una fase posterior. Asi no sera necesario modificar la tabla
  cuando se anada el upload.

### 2. Enums

`app/Enums/ProjectStatus.php`: casos `Planning`, `InProgress`,
`OnHold`, `WaitingClient`, `Completed`, `Archived`. Metodos
`label()`, `color()`, `badgeClasses()` (este ultimo devuelve el
string CSS completo para evitar logica en la vista) y `isOpen()`
para excluir completados/archivados.

### 3. Modelos

- `Project`:
  - `$fillable` con todas las columnas publicables.
  - Casts: `status` enum, `progress` int, `is_visible_to_client`
    bool, `starts_at` y `estimated_ends_at` date, `archived_at`
    datetime.
  - Evento `creating` que genera el slug unico con sufijo numerico
    si hay colision.
  - Relaciones: `organization()`, `members()`,
    declaraciones adelantadas con type-hints para `columns()`,
    `tasks()`, `documents()`, `messages()`, `calendarEvents()`,
    `agents()`, `aiConfig()` y `aiChatSessions()` (fases 3-8).
  - Helper `owner()` que delega en la organizacion (no es
    relacion almacenada).
  - Helpers de archivado: `isArchived()`, `archive()`,
    `unarchive()` (todos idempotentes).
  - Accessor `progressPercent` que devuelve float 0-100.
  - `isVisibleToClient()`: combina flag + archivado.
  - Scopes: `active()`, `archived()`, `visibleToClient()`,
    `forUser(User)`.

Decisiones clave:

- **El scope `forUser` revisa la organizacion, no el proyecto**.
  Esto significa que un cliente ve los proyectos visibles de todas
  sus organizaciones, aunque no este asignado al proyecto
  concreto. La asignacion via pivot `project_user` queda para
  indicar quien trabaja en el proyecto (kanban, tareas,
  notificaciones), no para limitar la visibilidad. Asi el cliente
  puede explorar todos los proyectos de su empresa aunque no sea
  el asignado directo.
- **El archivado se hace via `archived_at`, no eliminando filas**,
  para preservar la integridad referencial con tareas, documentos
  y mensajes que vendran en fases siguientes.

### 4. Policies

`ProjectPolicy`:

- `viewAny`: solo admin.
- `view`: admin o (cliente miembro de la org del proyecto y
  proyecto visible).
- `create`, `update`, `delete`, `archive`, `manageMembers`: solo
  admin.
- `belongsToOrganization`: helper reutilizable para verificar
  membresia.

La doble barrera (middleware `admin` + policy) sigue el mismo
esquema que la fase 1: si en una fase futura se mezcla el routing,
las policies seguiran garantizando el aislamiento.

### 5. Form Requests

- `Admin/StoreProjectRequest`: name, organization_id, description,
  status, fechas, is_visible_to_client. Normaliza el checkbox via
  `prepareForValidation`.
- `Admin/UpdateProjectRequest`: mismos campos + progress (0-100).
  Acepta status `archived` para archivar desde el formulario de
  edicion.
- `Admin/UpdateProjectProgressRequest`: solo progress, para
  endpoints pequenos.
- `Admin/AttachProjectMemberRequest`: user_id. La regla "debe ser
  miembro de la organizacion" se enforce en el controlador.

### 6. Controladores

- `Admin/ProjectController` (resource): index con busqueda y
  filtros, create, store, show, edit, update (con sincronizacion
  de `archived_at` segun el status), destroy.
- `Admin/ProjectMemberController`: store valida que el user sea
  miembro de la org antes de anadirlo; destroy desvincula.
- `Admin/ProjectArchiveController`: archive/unarchive idempotentes.
- `Admin/ProjectProgressController`: actualiza solo el campo
  progress via PATCH.
- `Portal/ProjectController`:
  - `index`: listado paginado de proyectos visibles al cliente.
  - `show`: detalle (la policy filtra la visibilidad).
  - `showOrganization`: detalle de una organizacion con sus
    proyectos visibles.

### 7. Livewire

- `Admin/Project/ProjectProgress`: input range + barra + boton
  "Guardar". Validacion reactiva con `#[Validate]`. La accion
  `save()` persiste via update directo del modelo.
- `Admin/Project/ProjectMembers`: lista miembros actuales, selector
  con miembros de la org no asignados, botones de quitar. Tras
  cualquier cambio refresca la lista de disponibles con
  `refreshAvailableMembers()`.

### 8. Partials / UI

- `components/partials/status-badge.blade.php`: badge con clases
  del enum.
- `components/partials/progress-bar.blade.php`: barra consistente
  con el design system, con opciones de label y porcentaje.
- `admin/projects/_archive_button.blade.php`: partial con el boton
  contextual de archivar/desarchivar.

Se renombro de `resources/views/partials/` a
`resources/views/components/partials/` para que Blade los pueda
referenciar como `<x-partials.status-badge>`. La convencion de
Laravel exige que los componentes anonimos vivan bajo
`components/`.

### 9. Vistas Blade

Admin:
- `admin/projects/index.blade.php`: tabla con buscador, filtro
  por organizacion, filtro por estado, badge de estado, mini
  barra de progreso, miembros count.
- `admin/projects/create.blade.php`, `edit.blade.php`: formularios
  completos con los campos del Store/Update request.
- `admin/projects/show.blade.php`: header con nombre, organizacion,
  estado, archivado, fechas, descripcion, miembros (Livewire),
  progreso (Livewire).

Portal:
- `portal/projects/index.blade.php`: grid de cards con proyectos
  visibles.
- `portal/projects/show.blade.php`: header de solo lectura, equipo
  informativo, sin controles de admin.
- `portal/organizations/show.blade.php`: nombre y descripcion de
  la org + grid de proyectos visibles.
- `portal/dashboard.blade.php`: actualizado con proyectos recientes
  y stat "Proyectos abiertos".

### 10. Rutas (`routes/web.php`)

```
GET    /admin/projects                                admin.projects.index
POST   /admin/projects                                admin.projects.store
GET    /admin/projects/create                         admin.projects.create
GET    /admin/projects/{project}                      admin.projects.show
GET    /admin/projects/{project}/edit                 admin.projects.edit
PUT    /admin/projects/{project}                      admin.projects.update
DELETE /admin/projects/{project}                      admin.projects.destroy
POST   /admin/projects/{project}/members              admin.projects.members.store
DELETE /admin/projects/{project}/members/{user}       admin.projects.members.destroy
POST   /admin/projects/{project}/archive              admin.projects.archive
POST   /admin/projects/{project}/unarchive            admin.projects.unarchive
PATCH  /admin/projects/{project}/progress             admin.projects.progress.update

GET    /portal/projects                               portal.projects.index
GET    /portal/projects/{project}                     portal.projects.show
GET    /portal/organizations/{organization}           portal.organizations.show
```

Total acumulado: 41 rutas custom. Se anadieron 15 respecto a fase 1.

## Tests

Total: **115 tests, 277 aserciones, todos en verde**.
Distribucion nueva en fase 2:

```
tests/Unit/Models/ProjectTest.php                   12 tests
tests/Feature/Admin/ProjectManagementTest.php       10 tests
tests/Feature/Admin/ProjectMemberTest.php            5 tests
tests/Feature/Admin/ProjectArchiveTest.php           4 tests
tests/Feature/Admin/ProjectProgressTest.php          4 tests
tests/Feature/Portal/ProjectViewTest.php             7 tests
```

Cubren:

- Slug unico, sufijos incrementales.
- Scopes: active, archived, visibleToClient, forUser.
- Archive idempotente, unarchive.
- `isVisibleToClient` devuelve false en archivados.
- Casts del modelo.
- CRUD admin basico + validaciones (nombre vacio, org inexistente,
  status invalido, fechas cruzadas).
- Cliente no puede listar/crear/editar proyectos.
- Cliente no puede ver proyectos de otras orgs.
- Miembros: anadir de la org, rechazar ajeno, no duplicar, quitar.
- Archive/unarchive idempotente, cliente no archiva.
- Progress: 0-100, fuera de rango falla, cliente no actualiza.
- Portal: ve solo proyectos visibles de sus orgs, no ve ocultos,
  no ve archivados, listado solo visibles, detalle de org, no ve
  otras orgs.

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
- 115 tests pasan, 277 aserciones.
- Build Vite sin warnings.
- 41 rutas custom (15 mas que en fase 1).

## Decisiones tecnicas relevantes

1. **Visibilidad = flag + no archivado**: la combinacion se evalua
   en `isVisibleToClient()` para que un proyecto archivado no se
   muestre al cliente aunque su flag diga true. Asi un admin
   puede ocultar proyectos del portal simplemente archivandolos,
   sin necesidad de tocar el flag explicito.

2. **`forUser` mira la organizacion, no el proyecto**: la membresia
   de la org es la condicion natural de visibilidad. La
   asignacion a proyecto es informacion adicional para fases
   siguientes (kanban, chat) y se mantiene en el pivot sin
   afectar a la lectura.

3. **Archive vs status `archived`**: ambos conviven. `archived_at`
   es la fuente de verdad para el archivado; `status = archived`
   se sincroniza automaticamente al archivar y se limpia al
   desarchivar. La razon es que `archived_at` admite acciones
   idempotentes y es facil de consultar, mientras que el status
   se usa para el badge visible.

4. **Miembros se asignan desde la org, no libremente**: el
   controlador de miembros rechaza a usuarios que no pertenezcan
   a la organizacion del proyecto. Asi se evita que un admin
   "cole" usuarios de otras empresas como miembros fantasma.

5. **Livewire solo donde aporta**: la edicion de proyectos y el
   listado de miembros son los unicos componentes Livewire en
   fase 2. El resto son formularios Blade tradicionales. La idea
   es no inflar la app con interactividad que se puede conseguir
   con un redirect + flash.

6. **Eliminacion y archivado coexisten**: el boton eliminar esta
   en el formulario de edicion (con confirm JS). La recomendacion
   en la UI es archivar primero, pero un admin puede borrar
   directamente si quiere. Las FKs cascade limpian las relaciones.

7. **Cover image diferido**: la columna `cover_path` se creo en
   la migracion pero no hay upload en esta fase. Se anadira
   cuando el storage de proyectos este listo, probablemente junto
   con la gestion de archivos del proyecto (fase 4 documentos,
   fase 5 chat, etc).

## Pendiente (fuera de scope de fase 2)

- Cover image upload (storage local + servicio).
- Tabs en la vista show (Kanban, Documentos, Chat, Calendario,
  Configuracion) que se iran poblando en fases 3-8.
- Filtro "archivados" en el listado.
- Reasignar organizacion de un proyecto: actualmente el form de
  edicion lo permite pero la UI no lo expone de forma
  destacada; queda en segundo plano.
- Paginacion en el listado de miembros del proyecto (no es
  problema en MVP, las organizaciones tienen pocos miembros).

## Riesgos y notas para la fase 3

- El modelo `Project` ya tiene declaracion adelantada de
  `columns()` (HasMany de BoardColumn) y `tasks()` (HasMany de
  Task). En la fase 3 solo habra que crear las clases
  correspondientes: no sera necesario tocar `Project.php` salvo
  para anadir scopes adicionales.
- `Project::scopeForUser` y `ProjectPolicy::view` se complementan:
  cualquier nueva ruta portal debera usar ambos para garantizar
  el aislamiento.
- El progress se actualiza con un valor 0-100 libre, no se
  calcula a partir de tareas. En fase 3 (kanban) se podra
  anadir un calculo automatico opcional, pero el campo manual
  sigue siendo la fuente de verdad para el portal.
