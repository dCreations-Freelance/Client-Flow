# Fase transversal — Templates de agentes IA

Documento tecnico de lo implementado en la fase transversal de
agentes IA del MVP de ClientFlow. Anade la biblioteca de
templates que el admin puede exportar a sus IDEs (Cursor,
Claude Code, etc.) y asignar a proyectos concretos con un
override opcional del system prompt.

## Alcance

Segun `TODOs.md`, esta fase transversal incluye:

- Migraciones `agent_templates` y `project_agents`.
- Modelos `AgentTemplate` y `ProjectAgent`.
- Policies admin-only para ambos.
- Form Requests de creacion/edicion/asignacion.
- CRUD de templates en el panel admin.
- Gestion de asignaciones a proyectos (alta, edicion de
  override, baja).
- Exportacion a JSON de templates y asignaciones.
- Sidebar admin con acceso directo a la biblioteca.
- Boton "Agentes" en la vista de detalle de cada proyecto.
- Seeder con cuatro templates utiles para empezar.

Decisiones:

- La biblioteca es admin-only. Los clientes del portal no
  ven ni gestionan agentes: la policy lo bloquea y el
  modelo de negocio asume que los templates son
  configuracion interna del admin para sus IDEs.
- `project_agents` se modela como tabla propia (no pivot
  plano) para poder editar `system_prompt_override` por
  asignacion. Un mismo template puede tener system prompts
  distintos segun el proyecto.
- El prompt efectivo se calcula en
  `ProjectAgent::effectiveSystemPrompt()`: si hay override
  no vacio, manda; si no, el del template. Asi el IDE
  destino siempre recibe un prompt claro.
- El formato de `tools` es JSON libre: la validacion de
  esquema concreto (formato MCP, function calling) vive
  en el Form Request, no en BD. Esto permite que el admin
  use el formato que pida su IDE sin atar el modelo a
  un estandar.
- La validacion de duplicados en la asignacion se hace
  con `Rule::unique` con `where project_id = ...` para
  devolver un 422 limpio, no un 500 por violacion de
  constraint de BD.
- El export JSON es admin-only y devuelve la configuracion
  lista para guardarse en el IDE. La cabecera
  `Content-Disposition: attachment` fuerza la descarga.

## Cambios por capa

### 1. Migraciones

- `2026_06_19_090000_create_agent_templates_table.php`:
  biblioteca de templates. Columnas: `id`, `name` (120),
  `description` (text, nullable), `system_prompt`
  (longtext), `tools` (json, nullable), `model` (120,
  nullable), `category` (60, nullable, indexada), `created_by`
  (FK a `users` con cascade). Indices: simple sobre
  `category` y compuesto `(category, name)`.
- `2026_06_19_090001_create_project_agents_table.php`:
  pivot modelado como tabla propia. Columnas: `id`,
  `project_id` (FK a `projects` con cascade),
  `agent_template_id` (FK a `agent_templates` con cascade),
  `system_prompt_override` (longtext nullable), timestamps.
  Unique `(project_id, agent_template_id)` para impedir
  asignar el mismo template dos veces al mismo proyecto.

### 2. Modelos

- `App\Models\AgentTemplate`: cast `tools` a `array`,
  `created_by` a `integer`. Relaciones: `creator()`
  (`BelongsTo<User, AgentTemplate>`),
  `projects()` (`BelongsToMany<Project>` con
  `withPivot('system_prompt_override')` y timestamps).
  Scopes: `byCategory(?string)` (filtra exacto o no filtra
  si vacio), `search(?string)` (busca en `name` y
  `description` con LIKE). Helper: `toExportArray()`.
- `App\Models\ProjectAgent`: relaciones `project()` y
  `template()`. Helpers: `effectiveSystemPrompt()` (override
  si no vacio, si no el del template; `null` si tampoco
  hay template tras un borrado en cascada) y
  `toExportArray()` (usa el prompt efectivo).
- `App\Models\User`: nueva relacion `agentTemplates()`
  (`HasMany<AgentTemplate>` por `created_by`). Permite
  saber que admin creo cada template.

### 3. Policies

- `App\Policies\AgentTemplatePolicy`: todos los metodos
  (`viewAny`, `view`, `create`, `update`, `delete`)
  reciben solo `User` y devuelven `$user->isAdmin()`.
  La biblioteca es operativa del admin.
- `App\Policies\ProjectAgentPolicy`: `viewAny` y `create`
  reciben `Project` ademas de `User`; `view`, `update` y
  `delete` reciben `ProjectAgent`. Todos devuelven
  `$user->isAdmin()`. En MVP la gestion es 100% admin,
  aunque el resto del proyecto (kanban, docs) lo vean
  los clientes. Los templates son internos para
  configurar los IDEs del equipo.

### 4. Form Requests

- `App\Http\Requests\Admin\StoreAgentTemplateRequest`:
  crea template. Mensajes en castellano para todos los
  campos. `name` 2-120, `system_prompt` 10-20000,
  `description` hasta 1000, `category` hasta 60, `tools`
  opcional (array basico, validamos solo `name` por
  elemento), `model` hasta 120.
- `App\Http\Requests\Admin\UpdateAgentTemplateRequest`:
  edita template. Mismas reglas pero `sometimes` para
  soportar PATCH/PUT parciales. `system_prompt` mantiene
  `required` cuando viene en la peticion.
- `App\Http\Requests\Admin\AssignAgentTemplateRequest`:
  asigna template a proyecto. `agent_template_id` valida
  `exists:agent_templates,id` y un `Rule::unique` con
  `where project_id = ...` para impedir duplicados con
  422 limpio. `system_prompt_override` hasta 20000.
- `App\Http\Requests\Admin\UpdateProjectAgentRequest`:
  edita el override de una asignacion existente. Solo
  expone `system_prompt_override` (nullable, max 20000).

### 5. Controladores

- `App\Http\Controllers\Admin\AgentTemplateController`:
  CRUD de templates con `index`, `create`, `store`, `show`,
  `edit`, `update`, `destroy` y `export`. El listado
  pagina con 15, soporta filtros `search` y `category`
  via query string, y conserva los filtros con
  `withQueryString()`. `export` devuelve JSON con
  `Content-Disposition: attachment`. Los parametros de
  Route Model Binding se nombran `$agentTemplate` para
  coincidir con `{agent_template}` del resource.
- `App\Http\Controllers\Admin\ProjectAgentController`:
  gestion de asignaciones con `index`, `store`, `update`,
  `destroy` y `export`. `update` y `destroy` validan
  `$agent->project_id === $project->id` con `abort_unless`
  como defensa en profundidad contra ids cruzados.
  `index` usa `ProjectAgent` (no `belongsToMany`) para
  poder llamar a `effectiveSystemPrompt()` desde la vista
  sin logica adicional.

### 6. Vistas Blade

- `admin/agent-templates/index.blade.php`: barra de
  busqueda + filtro por categoria + boton "Nuevo template",
  tabla con nombre, categoria (badge), modelo, preview del
  prompt, proyectos asignados, creador y acciones.
  Paginacion con `withQueryString()`.
- `admin/agent-templates/create.blade.php` y
  `admin/agent-templates/edit.blade.php`: formulario con
  name, category (input con datalist de categorias
  existentes), description, system_prompt (textarea mono,
  12 filas, requerido), tools (textarea mono, 5 filas,
  placeholder con ejemplo), model.
- `admin/agent-templates/show.blade.php`: header con
  nombre + categoria + modelo + autor, descripcion, system
  prompt en `<pre>` mono, tools en JSON pretty-printed,
  proyectos asignados en tabla, botones Editar / Exportar
  JSON / Volver / Eliminar (con confirm).
- `admin/projects/agents/index.blade.php`: layout admin
  con breadcrumb al proyecto, formulario de alta (template
  + override opcional), tabla de asignaciones con edicion
  inline via `?edit={id}` en la URL (sin Livewire, sin
  Alpine). El form de edicion aparece en la fila
  correspondiente y el submit hace PUT a
  `admin.projects.agents.update`. Columna "Prompt efectivo"
  para ver de un vistazo que prompt usara el IDE destino.
- `partials/admin-sidebar.blade.php` (edit): el item
  "Plantillas IA" (placeholder, gris) se ha movido de
  Pronto a la lista principal, justo encima de
  "Configuracion IA", como "Templates IA" y con su
  estado activo cuando la ruta empieza por
  `admin.agent-templates`.
- `admin/projects/show.blade.php` (edit): nuevo boton
  "Agentes" en la barra de acciones del header del
  proyecto, justo antes de "Editar". Envuelto en
  `@if (Route::has('admin.projects.agents.index'))`.

### 7. Rutas

- `app/routes/web.php` (edit): dentro del grupo admin
  (`auth`+`admin`, prefijo `admin.`, nombre `admin.`) se
  registran:
  - `Route::resource('agent-templates',
    AdminAgentTemplateController::class)->names('agent-templates')`
    que genera las 7 acciones canonicas (incluidas
    `agent-templates.create` sin parametro y
    `agent-templates.edit` con `{agent_template}`).
  - `Route::get('agent-templates/{agent_template}/export',
    ...)` para la descarga JSON.
  - 5 rutas planas para asignaciones:
    `projects.agents.{index,store,update,destroy,export}`.
- En total se anaden 13 rutas nuevas (8 del resource de
  templates + 5 de asignaciones). Nota: el plan estimo 14
  pero el conteo real es 13: `Route::resource` aporta 7
  acciones y se suma 1 export = 8 para templates. Con
  5 para assignments = 13.

### 8. Factories

- `Database\Factories\AgentTemplateFactory`:
  `definition()` genera nombre de 3 palabras, descripcion,
  system prompt multi-parrafo, y `created_by` via
  `User::factory()->admin()`. Estados: `forCreator(User)`,
  `inCategory(string)`, `withTools()`.
- `Database\Factories\ProjectAgentFactory`:
  `definition()` crea asignacion sin override. Estado:
  `withOverride(?string)` para fijar o generar uno.

### 9. Seeders

- `Database\Seeders\AgentTemplateSeeder`: crea cuatro
  templates utiles para empezar el primer arranque:
  "Arquitecto Backend" (`architecture`), "Frontend
  Developer" (`frontend`), "Tech Lead" (`tech-lead`),
  "Code Reviewer" (`review`). Cada uno con un system
  prompt realista de 4-8 parrafos en castellano. Seeder
  idempotente (`firstOrCreate` por nombre) para que
  `migrate:fresh --seed` lo pueda correr varias veces
  sin duplicar filas.
- `Database\Seeders\DatabaseSeeder` (edit): anade llamada
  a `AgentTemplateSeeder` al final del `run()`.

### 10. Tests

- `tests/Unit/Models/AgentTemplateTest.php` (10 tests):
  cast `tools` a array, `created_by` a entero, relaciones
  `creator` y `projects`, scopes `byCategory` y `search`
  (incluyendo casos con `null` o vacio), `toExportArray`.
- `tests/Unit/Models/ProjectAgentTest.php` (8 tests):
  relaciones, `effectiveSystemPrompt` en sus cuatro
  variantes (override no vacio, null, vacio, sin
  template), `toExportArray` con override y sin el, y
  manejo de template nulo via `setRelation`.
- `tests/Unit/Policies/AgentTemplatePolicyTest.php`
  (2 tests): admin puede todo, cliente no puede nada.
- `tests/Feature/Admin/AgentTemplateManagementTest.php`
  (16 tests): admin ve los listados y formularios, cliente
  es redirigido al portal desde index y recibe 403
  implicito en create/edit/destroy, admin crea/valida/
  edita/elimina, filtros `search` y `category`, export
  JSON con cabecera `attachment`, sidebar admin incluye
  el link a templates.
- `tests/Feature/Admin/ProjectAgentManagementTest.php`
  (12 tests): admin ve/list/asigna, validacion de
  duplicados via `Rule::unique` (422 limpio), actualizacion
  de override, desasignacion, cliente bloqueado en todas
  las acciones, export JSON con prompt efectivo, vista
  index muestra el prompt efectivo.

## Comandos utiles

- `php artisan migrate:fresh --seed` — recrea la base de
  datos y carga los 4 templates por defecto.
- `php artisan test --filter="AgentTemplate|ProjectAgent"`
  — corre solo los 48 tests de esta fase.
- `php artisan route:list --except-vendor` — 95 rutas
  totales, 13 nuevas en esta fase.

## Decisiones y desviaciones del plan

- Conteo de rutas: el plan estimo 14 (9 + 5) pero el
  conteo real es 13 (8 + 5). `Route::resource` con
  `->names('agent-templates')` aporta 7 acciones
  canonicas; sumando 1 export son 8 para templates.
  El plan indicaba 9 probablemente por un off-by-one al
  contar las acciones de `create` y `edit` por separado
  despues de un `->except(['create', 'edit'])`; al final
  se opto por el resource completo (sin `except`) por
  simplicidad y porque los nombres encajan mejor con la
  convencion de Laravel.
- Parametros del Route Model Binding: en el controlador
  se usa `$agentTemplate` (no `$template`) para que el
  nombre coincida con `{agent_template}` del resource.
  Sin esta coincidencia Laravel no resuelve el model
  (queda como instancia vacia). Esto se detecto en
  debugging tras los primeros tests fallidos.
- Vista index de agentes: se cambio `$project->agents()`
  (BelongsToMany con pivot plano) por `ProjectAgent::query`
  con `where('project_id', ...)` para poder llamar a
  `effectiveSystemPrompt()` desde la vista. El accessor
  `pivot` de la relacion many-to-many no expone los
  helpers del modelo `ProjectAgent`.
- Pre-existing en working tree: el archivo `routes/web.php`
  tenia en el working tree un cambio local
  `view('welcome')` → `view('landing')` que rompe
  `HomeTest` (la landing no contiene el texto exacto que
  el test busca). Este cambio no es parte de esta fase
  y queda como tarea pendiente.
