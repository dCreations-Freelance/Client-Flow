# Fase 12 — Plantillas de proyecto (biblioteca reutilizable)

Documento tecnico de lo implementado en la fase 12 del MVP de
ClientFlow. Cubre la biblioteca de plantillas de proyecto:
CRUD de plantillas, gestion anidada de columnas / tareas /
documentos predefinidos, filtro por categoria y flujo de
"crear proyecto desde plantilla" que copia todos los
elementos al nuevo proyecto.

## Alcance

Segun `TODOs.md`, la fase 12 incluye:

- Migracion `project_templates`.
- Migracion `project_template_columns`.
- Migracion `project_template_tasks`.
- Migracion `project_template_documents`.
- Modelo `ProjectTemplate` con relaciones.
- CRUD plantillas (admin).
- Vista listado de plantillas con filtro por categoria.
- Asociacion de columnas default a plantilla.
- Asociacion de tareas predefinidas a plantilla.
- Asociacion de documentos esqueleto a plantilla.
- Boton "Crear proyecto desde plantilla" en listado de proyectos.
- Al crear desde plantilla, copiar columnas, tareas y documentos.

Anadido en esta fase (decisiones del producto):

- **Copia completa al aplicar**: las plantillas copian
  columnas, tareas (con descripcion, tipo, prioridad y
  estimacion) y documentos (con titulo y contenido
  markdown). El admin edita / borra despues lo que no
  encaje.
- **Tags libres con chips**: `category` es texto libre
  (no FK a una tabla de categorias). El filtro del
  listado se renderiza como chips clickeables.
  Datalist en el form de edicion para sugerir
  categorias existentes.
- **Mapeo por `position` en vez de por `slug`**: las
  tareas de la plantilla se ligan a la columna por su
  `position` (no por id), para que la plantilla sea
  resistente a renombrados. Si el admin renombra la
  columna, las tareas siguen apuntando al mismo
  sitio.
- **Columnas copiadas con `is_default = false`**: asi
  se distinguen de las 4 columnas canonicas de
  `DefaultBoardColumnsService`. Si en una fase futura
  se quiere ofrecer un "reset a las columnas por
  defecto", se sabe cuales son las del sistema y
  cuales las del template.
- **Banner contextual en el listado de proyectos**: si
  hay al menos una plantilla, se muestra un card
  azul en la parte superior del listado invitando a
  usarla. Asi el admin no se olvida de que existe
  la opcion.
- **Sidebar admin con enlace "Plantillas"**: entrada
  propia en el menu lateral del panel, junto a
  "Templates IA".
- **Vista de edicion por pestanas inline (sin
  Livewire)**: la vista `edit` renderiza 3 secciones
  (metadatos, columnas, tareas, documentos) con
  formularios HTML estaticos. Decidimos NO usar un
  componente Livewire porque la interaccion es
  pequena (un form por seccion) y mantenerlo
  estatico simplifica los tests y el debug.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_25_080000_create_project_templates_table.php` | Crea `project_templates` con `name`, `slug` (unique, auto-generado), `description` (text, nullable), `category` (string, nullable, indexed), `created_by` (FK users, restrict). |
| `database/migrations/2026_06_25_080001_create_project_template_columns_table.php` | Crea `project_template_columns` con `template_id` (cascade), `name`, `color` (nullable), `position`. Indice compuesto `(template_id, position)`. |
| `database/migrations/2026_06_25_080002_create_project_template_tasks_table.php` | Crea `project_template_tasks` con `template_id` (cascade), `column_position` (no id, para resistencia a renombrados), `title`, `description`, `type` (enum), `priority` (enum), `estimated_hours` (decimal), `position`. Indice `(template_id, column_position, position)`. |
| `database/migrations/2026_06_25_080003_create_project_template_documents_table.php` | Crea `project_template_documents` con `template_id` (cascade), `title`, `content` (longtext), `visibility` (enum), `position`. Indice `(template_id, position)`. |

Decisiones:

- `template_id` con `cascadeOnDelete` en las 3 tablas
  anidadas: borrar una plantilla borra sus columnas,
  tareas y documentos en BD sin dejar filas
  huerfanas.
- `created_by` con `restrictOnDelete` en
  `project_templates`: autoria historica, mismo
  patron que `project_documents.created_by`. Si un
  admin intenta borrar a un usuario que ha creado
  plantillas, MySQL rechaza la operacion.
- `column_position` (no FK a columnas) en
  `project_template_tasks`: la plantilla es
  resistente a renombrados de columna. La position
  es estable mientras no se reordenen las columnas
  en la plantilla.
- `type` y `priority` como strings con valores de
  enums: validacion en Form Request, no en BD, para
  que anadir un tipo futuro sea solo codigo.
- `visibility` con default `private`: el caso
  habitual es documentacion interna del equipo.
  El admin pone `public` si la plantilla incluye
  docs para clientes.

### 2. Modelos

#### `ProjectTemplate` (nuevo)

- `$fillable`: `name`, `slug`, `description`,
  `category`, `created_by`.
- `booted()` con observer: genera `slug`
  automaticamente a partir del nombre al crear.
  Patron identico a `Project::generateUniqueSlug`.
- `generateUniqueSlug(string)`: slug base + sufijo
  numerico si hay colision.
- Relaciones: `creator()`, `columns()`, `tasks()`,
  `documents()`.
- Accesors: `columnCount`, `taskCount`,
  `documentCount` (via `loadCount` o query
  agregada), `categoryLabel` ("web" / "Sin
  categoria").
- Scopes: `inCategory(string)`, `search(string)`.

#### `ProjectTemplateColumn` (nuevo)

- `$fillable`: `template_id`, `name`, `color`,
  `position`.
- `casts`: `position` a integer.
- Relacion: `template()`.
- Scope: `ordered()` (position + id).

#### `ProjectTemplateTask` (nuevo)

- `$fillable`: `template_id`, `column_position`,
  `title`, `description`, `type`, `priority`,
  `estimated_hours`, `position`.
- `casts`: `type` y `priority` a enums,
  `column_position` y `position` a integer,
  `estimated_hours` a decimal.
- Relacion: `template()`.
- Scope: `ordered()` (column_position + position).

#### `ProjectTemplateDocument` (nuevo)

- `$fillable`: `template_id`, `title`, `content`,
  `visibility`, `position`.
- `casts`: `visibility` al enum, `position` a
  integer.
- Relacion: `template()`.
- Helper: `isPublic()` (atajo para
  `instanceof DocumentVisibility` en la vista).
- Scope: `ordered()`.

### 3. Service

#### `ProjectTemplateService` (`app/Services/ProjectTemplate/ProjectTemplateService.php`)

API centralizada. Encapsula la logica de copia
(orden, mapeo, generacion de slugs en columnas)
para que no se duplique en cada call site.

- `applyToProject(ProjectTemplate, Project, User)`:
  copia columnas, tareas y documentos al proyecto
  destino en el orden correcto (columnas primero
  para resolver el mapeo, despues tareas, despues
  documentos). Devuelve un array
  `['columns' => int, 'tasks' => int, 'documents' =>
  int]` con los conteos de la copia.
  - `copyColumns()`: crea `BoardColumn` por cada
    `ProjectTemplateColumn`, con `slug` generado
    via `BoardColumn::generateUniqueSlug` y
    `is_default = false`.
  - `copyTasks()`: crea `Task` por cada
    `ProjectTemplateTask`, resolviendo la columna
    destino por `column_position` (mapa
    `int => BoardColumn`). Si la tarea referencia
    una position que no existe, se salta (defensa
    contra plantillas mal formadas). `created_by =
    $actor->id`.
  - `copyDocuments()`: crea `ProjectDocument` por
    cada `ProjectTemplateDocument`, preservando
    `visibility` y `position`. `created_by =
    $actor->id`.
- `categories()`: lista de categorias distintas
  para alimentar los chips del filtro. Excluye
  nulos y vacios. Ordenada alfabeticamente.
- `queryWithFilters(?string $search, ?string
  $category)`: query base reutilizable con
  `withCount` para alimentar los badges del card.
  Encapsula la logica de `search` y `inCategory`
  para que el controlador y los tests compartan la
  misma semantica.
- `nextColumnPosition(ProjectTemplate)`: siguiente
  `position` libre para anadir columna al final.
  Pensado para que el form solo requiera `name` y
  `color` (el admin no calcula la position a
  mano).
- `nextTaskPosition(ProjectTemplate, int
  $columnPosition)`: igual para tareas en una
  columna concreta.
- `nextDocumentPosition(ProjectTemplate)`: igual
  para documentos.

### 4. Policy

#### `ProjectTemplatePolicy` (nuevo)

Todas las acciones (viewAny, view, create, update,
delete, apply) son exclusivas del admin. La
biblioteca de plantillas es una herramienta
interna del freelancer / agencia; el cliente no la
ve ni la usa directamente.

- `viewAny(User)`: `$user->isAdmin()`.
- `view(User, ProjectTemplate)`: idem.
- `create(User)`: idem.
- `update(User, ProjectTemplate)`: idem.
- `delete(User, ProjectTemplate)`: idem.
- `apply(User, ProjectTemplate)`: idem. Se evalua
  contra la plantilla (no contra un proyecto)
  porque la aplicacion es lo que crea el proyecto.

### 5. Form Requests

- `Admin/StoreProjectTemplateRequest`: valida
  `name` (required, unique, max 100), `description`
  (nullable, max 5000), `category` (nullable,
  max 50). `prepareForValidation` normaliza
  `category` vacia a `null` para que no se guarde
  como string vacio. Solo admin.
- `Admin/UpdateProjectTemplateRequest`: mismo que
  Store, con `unique` ignorando la fila actual.
- `Admin/StoreProjectTemplateColumnRequest`:
  `name` (required, max 50), `color` (nullable,
  regex `#[0-9A-Fa-f]{6}`).
- `Admin/StoreProjectTemplateTaskRequest`:
  `column_position` (required, integer), `title`
  (required, max 255), `description` (nullable),
  `type` y `priority` validados contra los enums
  del proyecto, `estimated_hours` (nullable,
  numeric).
- `Admin/StoreProjectTemplateDocumentRequest`:
  `title` (required), `content` (nullable, max
  200000), `visibility` (in: private, public).
  `prepareForValidation` normaliza `content`
  vacio a `null`.

### 6. Controladores

#### `Admin/ProjectTemplateController` (nuevo)

Un solo controlador para todas las acciones del
modulo (CRUD + 9 acciones anidadas). Mantener todo
junto simplifica las pruebas y reduce el numero
de archivos en `app/Http/Controllers/Admin/`.

- `index(Request)`: listado paginado con
  busqueda y chips de categoria.
- `create()`: form de metadatos.
- `store(StoreProjectTemplateRequest)`: crea
  plantilla y redirige a `edit` para anadir
  contenido. Decision de UX: crear primero y
  editar despues, en vez de un wizard multi-paso.
- `show(ProjectTemplate)`: preview readonly con
  las 3 secciones (columnas, tareas, documentos).
- `edit(ProjectTemplate)`: editor completo con
  form de metadatos + secciones de gestion
  inline.
- `update(UpdateProjectTemplateRequest,
  ProjectTemplate)`: actualiza solo metadatos.
- `destroy(ProjectTemplate)`: elimina (cascade).
- `storeColumn / updateColumn / destroyColumn`:
  CRUD de columnas anidadas.
- `storeTask / updateTask / destroyTask`: CRUD de
  tareas predefinidas. `updateTask` recalcula
  `position` al final de la nueva columna si
  cambia `column_position`.
- `storeDocument / updateDocument / destroyDocument`:
  CRUD de documentos esqueleto.
- `ensureXBelongsToTemplate()`: helpers privados
  que verifican que el item pertenece a la
  plantilla de la URL (defensa contra
  manipulacion de IDs).

#### `Admin/ProjectController` (extendido)

Dos metodos nuevos para el flujo "crear desde
plantilla":

- `createFromTemplate(ProjectTemplate)`: form de
  creacion pre-rellenado con el nombre sugerido
  `"{nombre plantilla} (copia)"`.
- `storeFromTemplate(Request, ProjectTemplate)`:
  crea el proyecto, le aplica la plantilla via
  `ProjectTemplateService::applyToProject`, y
  redirige al hub del nuevo proyecto con un
  mensaje flash que resume la copia (`"Proyecto
  creado desde la plantilla X: 3 columnas, 12
  tareas y 4 documentos"`).

### 7. Rutas (`routes/web.php`)

```
GET    /admin/project-templates                                                    admin.project-templates.index
GET    /admin/project-templates/create                                            admin.project-templates.create
POST   /admin/project-templates                                                    admin.project-templates.store
GET    /admin/project-templates/{project_template}                                admin.project-templates.show
GET    /admin/project-templates/{project_template}/edit                           admin.project-templates.edit
PUT    /admin/project-templates/{project_template}                                admin.project-templates.update
DELETE /admin/project-templates/{project_template}                                admin.project-templates.destroy

POST   /admin/project-templates/{project_template}/columns                         admin.project-templates.columns.store
PUT    /admin/project-templates/{project_template}/columns/{column}                admin.project-templates.columns.update
DELETE /admin/project-templates/{project_template}/columns/{column}                admin.project-templates.columns.destroy

POST   /admin/project-templates/{project_template}/tasks                            admin.project-templates.tasks.store
PUT    /admin/project-templates/{project_template}/tasks/{task}                    admin.project-templates.tasks.update
DELETE /admin/project-templates/{project_template}/tasks/{task}                    admin.project-templates.tasks.destroy

POST   /admin/project-templates/{project_template}/documents                        admin.project-templates.documents.store
PUT    /admin/project-templates/{project_template}/documents/{document}            admin.project-templates.documents.update
DELETE /admin/project-templates/{project_template}/documents/{document}            admin.project-templates.documents.destroy

GET    /admin/projects/create-from-template/{project_template}                     admin.projects.create-from-template
POST   /admin/projects/from-template/{project_template}                            admin.projects.store-from-template
```

Total: **18 rutas nuevas** (16 admin templates +
2 admin projects-apply). No hay equivalente en
portal: la biblioteca de plantillas es una
herramienta interna del admin.

### 8. Vistas Blade

Nuevas:

- `resources/views/admin/project-templates/index.blade.php`:
  listado con busqueda + chips de categoria + 4
  tarjetas KPI (columnas, tareas, docs, link
  rapido a "Crear proyecto desde plantilla").
- `resources/views/admin/project-templates/create.blade.php`:
  form de metadatos (nombre, categoria con
  datalist de sugerencias, descripcion).
- `resources/views/admin/project-templates/show.blade.php`:
  preview readonly con 3 columnas (columnas /
  tareas / documentos) y CTA de "Crear proyecto
  desde esta plantilla".
- `resources/views/admin/project-templates/edit.blade.php`:
  editor completo con 4 secciones: (1) form de
  metadatos, (2) lista editable de columnas con
  alta inline, (3) lista editable de tareas con
  form de alta (selector de columna destino, tipo,
  prioridad, titulo, descripcion, estimacion),
  (4) lista editable de documentos con form de
  alta (titulo, visibilidad, contenido).
- `resources/views/admin/projects/create-from-template.blade.php`:
  form de creacion pre-rellenado con el nombre
  sugerido, selector de organizacion, estado
  inicial y checkbox de visibilidad. Ademas
  incluye una tarjeta resumen que muestra
  cuantos elementos se copiaran.

Actualizadas:

- `resources/views/admin/projects/index.blade.php`:
  banner contextual azul invitando a usar
  plantillas (solo visible si hay al menos una
  plantilla creada). Link al listado de
  plantillas.
- `resources/views/admin/projects/create.blade.php`:
  tip en la parte superior sugiriendo la opcion
  de crear desde plantilla (solo visible si hay
  al menos una plantilla).
- `resources/views/partials/admin-sidebar.blade.php`:
  nueva entrada "Plantillas" en el sidebar,
  entre "Templates IA" y "Notificaciones".

## Tests

Total: **48 tests nuevos pasan en verde**.

Distribucion:

```
tests/Unit/Models/ProjectTemplateTest.php                                   14 tests
tests/Unit/Services/ProjectTemplate/ProjectTemplateServiceTest.php         11 tests
tests/Feature/Admin/ProjectTemplateManagementTest.php                      23 tests
```

Cubren:

- **Modelo**: fillable, generacion automatica de
  slug (con sufijo numerico si hay colision),
  accesors de conteo, `categoryLabel` (valor /
  "Sin categoria"), scopes `inCategory` y
  `search`, relaciones (creator, columns, tasks,
  documents), orden de las relaciones
  (`columns` por `position`, `tasks` por
  `column_position` + `position`), casts
  (enums `TaskType` y `TaskPriority`), helper
  `isPublic()` en documentos.
- **Service**: `applyToProject` copia columnas
  con color y posicion, copia tareas en la
  columna correcta (verificando el mapeo),
  copia documentos con `visibility` correcta,
  copia `estimated_hours` y `type` de las
  tareas, salta tareas que referencian columnas
  inexistentes (defensa), proyecto vacio no crea
  nada, `categories()` devuelve lista unica
  ordenada excluyendo nulos y vacios,
  `queryWithFilters` aplica search y categoria,
  helpers de `nextXPosition` calculan la
  siguiente posicion libre.
- **Feature**: rutas y autorizacion (admin ve
  listado, cliente redirigido al portal), CRUD
  completo de plantillas (crear / ver / editar /
  eliminar), validacion de form (nombre vacio,
  duplicado), filtros (search y categoria),
  CRUD anidado de columnas (incluyendo asignacion
  automatica de `position` al final), CRUD
  anidado de tareas, CRUD anidado de documentos
  (incluyendo `public` / `private`), aislamiento
  cross-template (404 al manipular items de otra
  plantilla), creacion de proyecto desde plantilla
  (aplica todos los elementos, redirige al hub,
  muestra mensaje de resumen), rechazo a clientes
  en todo el flujo.

## Verificacion final

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test --filter="ProjectTemplate"
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor | grep -E "(project-template|projects.*template)"
```

Resultado: 48 tests nuevos pasan. Las 18 rutas
nuevas estan registradas. El build de Vite no
tiene errores.

## Decisiones tecnicas relevantes

1. **Mapeo de tareas por `position`, no por id**:
   si el admin renombra una columna en la plantilla,
   las tareas siguen apuntando a la misma `position`
   en el proyecto destino. Esto es robusto a
   renombrados sin necesidad de mantenimiento
   adicional.

2. **`columnas copiadas con `is_default = false`**:
   asi se distinguen de las 4 columnas canonicas de
   `DefaultBoardColumnsService`. Si en una fase futura
   se quiere ofrecer un "reset a las columnas por
   defecto", el admin sabe cuales son las del
   sistema y cuales las de la plantilla.

3. **Sin componente Livewire para el editor**: la
   vista `edit` se renderiza con 4 secciones
   (metadatos, columnas, tareas, documentos) y un
   form HTML estatico por seccion. Decidimos no usar
   un Livewire porque la interaccion es pequena (un
   form por seccion) y mantenerlo estatico
   simplifica los tests y el debug. Si en una fase
   futura se quiere anadir drag&drop para reordenar
   columnas / tareas, ese sera el momento de
   migrar a Livewire.

4. **Tags libres con chips**: `category` es texto
   libre. El filtro del listado se renderiza como
   chips clickeables (uno por categoria distinta) +
   un chip "Todas" para limpiar. En el form de
   edicion / creacion, un `<datalist>` sugiere las
   categorias existentes para evitar typos. Asi el
   admin puede agrupar a su manera sin que tengamos
   que mantener un catalogo cerrado.

5. **Copia completa al aplicar**: las plantillas
   copian titulo, descripcion, tipo, prioridad,
   estimacion y visibilidad de cada elemento. El
   admin edita / borra despues lo que no encaje. Es
   la opcion que ahorra mas tiempo y se alinea con
   "reutilizar para no repetir".

6. **`created_by = $actor->id` al aplicar**: las
   tareas y documentos del proyecto recien creado
   aparecen como creados por el admin que aplico la
   plantilla, no por un "sistema" generico. Asi el
   audit trail del proyecto queda consistente.

7. **Mensaje de resumen con conteos**: al crear un
   proyecto desde plantilla, el mensaje flash
   resume la copia (`"Proyecto creado desde la
   plantilla X: 3 columnas, 12 tareas y 4
   documentos"`). Asi el admin ve de un vistazo
   que la copia se completo correctamente.

8. **Banner contextual en el listado de proyectos**:
   si hay al menos una plantilla, se muestra un
   card azul en la parte superior del listado
   invitando a usarla. Asi el admin no se olvida de
   que existe la opcion. El banner se oculta
   automaticamente si no hay plantillas.

9. **Sidebar admin con enlace "Plantillas"**:
   entrada propia en el menu lateral, junto a
   "Templates IA". Es el sitio natural para una
   herramienta interna del admin.

10. **Cascade completo via FKs**: borrar una
    plantilla borra sus columnas, tareas y
    documentos en BD por `cascadeOnDelete`. El
    test `test_admin_puede_eliminar_plantilla_y_cascade_borra_elementos`
    verifica que la cascada funciona
    correctamente.

## Pendiente (fuera de scope de fase 12)

- **Drag & drop para reordenar**: si en una fase
  futura se quiere permitir al admin reordenar
  columnas / tareas con drag&drop, ese sera el
  momento de migrar la vista `edit` a un
  componente Livewire. La migracion es
  factible porque la logica esta en
  `ProjectTemplateService::nextXPosition`.
- **Subtareas predefinidas**: las tareas de la
  plantilla no tienen `parent_id` (seria
  demasiado granular para el MVP). Si en una
  fase futura se quiere anadir, se anade un campo
  `parent_position` (mismo patron que
  `column_position`) y se aplica despues de crear
  todas las tareas raiz.
- **Plantillas por organizacion**: las plantillas
  son globales (de la biblioteca del admin). Si en
  una fase futura se quiere multi-tenant, se
  anade `organization_id` a `project_templates` y
  se filtra en los scopes.
- **Importar / exportar plantilla como JSON**:
  para compartir plantillas entre instalaciones.
  La estructura ya esta serializada en los modelos,
  asi que un `ProjectTemplateResource` en JSON
  seria directo. Mismo patron que la exportacion
  de `agent-templates` (fase transversal).
- **Vista de "preview" del proyecto antes de
  aplicar**: ahora el admin ve los conteos
  resumidos. Si quiere un preview mas rico, se
  anade una vista `show` extendida (la que ya
  tenemos, pero con un boton "Vista previa
  detallada" que abre un modal con todas las
  tareas y documentos).
- **Versionado de plantillas**: si una plantilla
  cambia despues de que un proyecto la ha aplicado,
  no hay forma de "sincronizar" los cambios. En una
  fase futura, se podria anadir un `version` a
  `ProjectTemplate` y guardar el `template_version`
  en `Project` al aplicar. Asi, el admin podria
  "re-aplicar" la plantilla para obtener los
  cambios.

## Riesgos y notas para la fase 13 (feed de actividad)

- Las plantillas pueden crear un pico de actividad
  en el chat del proyecto (por ejemplo, si en una
  fase futura se anade un mensaje automatico por
  cada tarea copiada). Por ahora
  `ProjectTemplateService::applyToProject` NO
  emite mensajes de sistema: solo crea filas
  silenciosamente. Si en una fase futura se
  quiere notificar, el sitio para anadirlo es
  dentro de `applyToProject` con un solo mensaje
  resumen (no N mensajes).
- La cache `tasks.total_logged_minutes` se
  actualiza automaticamente por el observer de
  `TimeEntry` (fase 11). Las tareas recien creadas
  desde una plantilla tienen `total_logged_minutes
  = 0`, lo cual es coherente.
- Las columnas copiadas con `is_default = false`
  ya estan marcadas en el factory de `TaskFactory`
  (no leen esta columna, asi que no hay impacto).
- El campo `BoardColumn.slug` se genera con
  `BoardColumn::generateUniqueSlug($projectId,
  $name)`. Si dos columnas de la plantilla tienen el
  mismo nombre, la segunda lleva sufijo numerico
  para garantizar unicidad. Esto evita colisiones
  en la BD.
- En una fase futura, si se quiere permitir
  "duplicar una plantilla existente", basta con
  copiar sus elementos a una nueva plantilla con
  nombre sugerido `"{nombre} (copia)"`. Mismo
  patron que `ProjectTemplateService::applyToProject`.
