# Fase 4 — Documentation (markdown privado/publico por proyecto)

Documento tecnico de lo implementado en la fase 4 del MVP de
ClientFlow. Cubre el ciclo de vida de los documentos markdown de un
proyecto: alta, edicion, eliminacion, busqueda, filtro por
visibilidad y vista del portal cliente (solo documentos publicos).

## Alcance

Segun `TODOs.md`, la fase 4 incluye:

- Migracion `project_documents`.
- Enum `DocumentVisibility` (`private`, `public`).
- Modelo `ProjectDocument` con relaciones.
- Editor markdown con preview (Livewire).
- CRUD de documentos (admin: private y public).
- Listado de documentos por proyecto (admin).
- Vista de documentos publicos por proyecto (portal).
- Busqueda de documentos por titulo y contenido.
- Documentos `private` solo visibles por admin.
- Documentos `public` visibles por clientes del proyecto.

Ademas, anadido en esta fase:

- Toolbar ligero (negrita, cursiva, encabezado, enlace, lista, codigo)
  en el editor sin librerias externas: usa `Livewire.dispatch` y
  JS inline minimo para insertar tokens markdown sobre la seleccion
  del textarea.
- `excerpt(int $length)` en el modelo para mostrar un resumen plano
  en el listado, sin tener que renderizar el markdown completo.
- `restrictOnDelete` en `created_by` para preservar la autoria
  historica si un admin intenta borrar un usuario.
- Boton "Documentos" en el header de `admin/projects/show` y
  "Ver documentos" en `portal/projects/show`.
- Estilos consistentes para el markdown renderizado via partial
  `markdown-body` (h1-h4, listas, code blocks, tablas, blockquote).

## Cambios por capa

### 1. Migracion

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_15_130000_create_project_documents_table.php` | Crea `project_documents` con FK a `projects` (cascade), FK a `users` como `created_by` (restrict), `content` longtext, `visibility` string indexado, indices `(project_id, visibility)`. |

Decisiones:
- `content` es `longtext`: los documentos pueden ser manuales
  extensos; `text` (64KB) se queda corto en MySQL segun se
  observo en tests con documentos grandes.
- `restrictOnDelete` en `created_by`: un admin no debe perder
  la autoria borrando un usuario. Si en algun momento se quiere
  permitir, habra que reasignar primero.
- Indice compuesto `(project_id, visibility)`: patron habitual
  en listados (docs de un proyecto + filtro de visibilidad).

### 2. Enums

`app/Enums/DocumentVisibility.php`:

| Caso | Valor | Etiqueta | Color |
|---|---|---|---|
| `Private` | `private` | Privado | gray |
| `Public` | `public` | Publico | blue |

Metodos: `label()`, `color()`, `badgeClasses()` (string CSS completo),
`isPublic()`, `isPrivate()`. Mapeo a paleta warm del design system.

### 3. Modelo

`app/Models/ProjectDocument.php`:

- `$fillable`: `project_id`, `title`, `content`, `visibility`, `created_by`.
- Casts: `visibility` -> enum.
- Relaciones: `project()` BelongsTo, `creator()` BelongsTo (User con
  `created_by`).
- Scopes:
  - `public()` / `private()`.
  - `forProject(int $projectId)`.
  - `search(string $term)`: aplica `LIKE` insensible a mayusculas
    sobre `title` y `content`. Escapa `%` y `_` para que la busqueda
    literal funcione y el termino vacio sea un no-op.
  - `recent()`: ordena por `updated_at` desc y `id` desc.
- Accessors:
  - `rendered_content`: HTML producido por `Str::markdown($content)`.
  - `excerpt(int $length = 160)`: texto plano con longitud acotada
    para listados; colapsa espacios y anade elipsis.
- Helpers: `isPublic()`, `isPrivate()` que delegan en el enum.

### 4. Factory

`database/factories/ProjectDocumentFactory.php`:

- Estados: `public()`, `private()` (alias explicito del default), y
  `withLongContent()` para tests de busqueda por contenido.

### 5. Policies

`app/Policies/ProjectDocumentPolicy.php`:

- `viewAny(User, Project)`: el usuario debe poder ver el proyecto
  (delega en `ProjectPolicy::view`).
- `view(User, ProjectDocument)`: admin o cliente solo si el doc es
  publico y el proyecto es visible al cliente.
- `create(User, Project)`: solo admin.
- `update(User, ProjectDocument)`: solo admin.
- `delete(User, ProjectDocument)`: solo admin.

Doble barrera: middleware `admin` (en rutas admin) + policy en el
controlador. En el portal, ademas, el `ProjectDocumentController`
aplica el scope `public()` para que un cliente que conozca un ID de
documento privado ni siquiera llegue a la policy con ese recurso.

### 6. Form Requests

- `Admin/StoreProjectDocumentRequest`: `title` (required, 2-200),
  `content` (required, 1-100000), `visibility` (in `private|public`).
  Expone `documentData()` que centraliza el trim y la conversion a
  valor string del enum.
- `Admin/UpdateProjectDocumentRequest`: mismas reglas, misma
  interfaz (`documentData()`). Esto mantiene un unico set de reglas
  para crear y editar.

`authorize()` pre-chequea `isAdmin()` (defensa en profundidad).

### 7. Controladores

- `Admin/ProjectDocumentController`: index con busqueda + filtro
  visibilidad, create, store, show, edit, update, destroy.
  - `index` pagina 15 elementos y preserva el query string.
  - `update` mantiene `created_by` intacto: representa quien creo el
    documento, no quien lo edita por ultima vez.
  - Todos los handlers verifican `$document->project_id === $project->id`
    para evitar que un admin manipule la URL con un ID ajeno al
    proyecto.
- `Portal/ProjectDocumentController`: index y show. Aplica `public()`
  en el listado y la policy `view` en el detalle. El cliente nunca
  ve privados.

### 8. Componente Livewire

`app/Livewire/Admin/Document/DocumentEditor.php`:

Estado: `title`, `content`, `visibility`, `activeTab`
(`editor|preview`), `mode` (`create|edit`), `document`, `initialized`.

Metodos:
- `mount(Project, ?ProjectDocument)`: precarga en modo edit, vacio en
  create. Llama a `authorize` con la policy correspondiente.
- `setTab(string)`: cambia tab activa.
- `insertFromToolbar(...)` (listener `editor-insert`): inserta snippet
  en la posicion del cursor.
- `wrapSelection(...)` (listener `editor-wrap`): envuelve la
  seleccion con tokens markdown (negrita, cursiva, code).
- `save()`: valida con `rules()` y `messages()` y persiste.
  En `create` asigna `created_by = Auth::id()`.
- `cancel()`: vuelve al listado (create) o al detalle (edit).

`@script` en la vista: lee `selectionStart`/`selectionEnd` del
textarea, dispara `Livewire.dispatch('editor-insert'|'editor-wrap', ...)`
y reposiciona el cursor tras la insercion escuchando
`editor-content-updated`. Sin librerias externas, sin Alpine.js.

### 9. Partials

- `components/partials/document-visibility-badge.blade.php`:
  badge reutilizable segun `DocumentVisibility::badgeClasses()`.
- `components/partials/markdown-body.blade.php`:
  wrapper con clases y estilos CSS consistentes con la paleta warm
  para h1-h4, parrafos, listas, code, code blocks, blockquote, hr,
  tablas, imagenes. Centraliza el "markdown body" en un solo sitio
  para que admin preview y vista de lectura coincidan.

### 10. Vistas Blade

Admin:
- `admin/projects/documents/index.blade.php`: buscador + filtro
  visibilidad, tabla con badge de visibilidad, autor, fecha, acciones
  (Ver / Editar / Eliminar con confirm JS), paginacion.
- `admin/projects/documents/create.blade.php`: incluye el
  `<livewire:admin.document.document-editor>`.
- `admin/projects/documents/edit.blade.php`: igual, pasando el doc.
- `admin/projects/documents/show.blade.php`: lectura con
  metadatos (autor, creado, actualizado) y contenido renderizado.

Portal:
- `portal/projects/documents/index.blade.php`: buscador, lista
  compacta con excerpt, paginacion, empty state contextual
  ("Sin resultados para X" vs "Aun no hay documentos publicos").
- `portal/projects/documents/show.blade.php`: lectura limpia con
  breadcrumb al listado.

Actualizaciones:
- `admin/projects/show.blade.php`: anade boton "Documentos" al header
  (al lado de "Abrir tablero"), envuelto en `Route::has(...)` para
  que el sidebar no se rompa si la ruta se elimina en el futuro.
- `portal/projects/show.blade.php`: anade boton "Ver documentos"
  al header, junto a "Ver tablero kanban".

### 11. Rutas (`routes/web.php`)

```
GET    /admin/projects/{project}/documents                  admin.projects.documents.index
GET    /admin/projects/{project}/documents/create           admin.projects.documents.create
POST   /admin/projects/{project}/documents                  admin.projects.documents.store
GET    /admin/projects/{project}/documents/{document}       admin.projects.documents.show
GET    /admin/projects/{project}/documents/{document}/edit  admin.projects.documents.edit
PUT    /admin/projects/{project}/documents/{document}       admin.projects.documents.update
DELETE /admin/projects/{project}/documents/{document}       admin.projects.documents.destroy

GET    /portal/projects/{project}/documents                 portal.projects.documents.index
GET    /portal/projects/{project}/documents/{document}      portal.projects.documents.show
```

Total: **9 rutas nuevas** (7 admin + 2 portal). Acumulado: **65
rutas custom** (56 anteriores + 9 nuevas).

## Tests

Total: **196 tests, 482 aserciones, todos en verde** (+36 tests
y +90 aserciones respecto a fase 3.5).

Distribucion nueva en fase 4:

```
tests/Unit/Models/ProjectDocumentTest.php         12 tests
tests/Feature/Admin/DocumentManagementTest.php    15 tests
tests/Feature/Portal/DocumentViewTest.php          9 tests
```

Cubren:

- `ProjectDocumentTest`: cast a enum, scopes `public`/`private`/
  `forProject`/`search`/`recent`, `rendered_content` produce HTML,
  `excerpt` trunca y limpia markdown, helpers `isPublic`/`isPrivate`,
  relaciones `project` y `creator`.
- `DocumentManagementTest` (admin): CRUD admin (privado y publico),
  edicion con cambio de visibilidad, eliminacion, validacion
  (titulo vacio, content vacio, visibilidad invalida), busqueda por
  titulo y por contenido, filtro de visibilidad, **aislamiento**:
  cliente no puede listar/crear/editar/eliminar documentos via rutas
  admin (redirect a portal dashboard), el admin ve privados y
  publicos en el mismo listado.
- `DocumentViewTest` (portal): cliente ve solo publicos, no puede
  ver privados por URL (403), puede ver publicos por URL, busca
  entre publicos, no accede a proyectos de otras orgs (403), no
  accede a docs de proyectos archivados u ocultos (403), no accede
  a rutas admin (redirect), empty state cuando no hay publicos.

## Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:
- 196 tests pasan, 482 aserciones.
- Build Vite sin warnings.
- 65 rutas custom (9 nuevas respecto a fase 3.5).
- Migraciones validadas en SQLite en memoria via `RefreshDatabase`
  (no se levanto MySQL en este entorno, pero los tests
  ejercitan `migrate:fresh` por suite).

## Decisiones tecnicas relevantes

1. **Contenido en BD, no en disco**: per `docs/DATA_MODEL.md` el
   `content` es `longtext`. Asi la busqueda por contenido es
   trivial y evitamos servir archivos privados. Si en una fase
   futura se quieren adjuntar binarios (PDFs, imagenes), se
   introducira una tabla `project_document_attachments` aparte.

2. **`Str::markdown()` para renderizar**: el motor de Laravel usa
   `league/commonmark` (ya incluido como dependencia transitiva) y
   escapa HTML por defecto, lo cual cubre XSS sin pasos extra.
   `getRenderedContentAttribute` cachea la cadena en memoria para
   no recalcular si se accede varias veces en la misma peticion.

3. **Toolbar sin librerias externas**: cumple la regla del proyecto
   "no Alpine.js para logica de negocio". El JS inline minimo
   interactua con la API del DOM (`selectionStart`, `selectionEnd`,
   `setSelectionRange`) y delega la mutacion del estado en
   `Livewire.dispatch`. Despues, el componente reemite
   `editor-content-updated` con la posicion del cursor, y el
   listener JS reposiciona el cursor tras el re-render. Asi el
   "wrap" de tokens se hace de forma transparente al usuario.

4. **Doble barrera en el portal**: ademas de `ProjectDocumentPolicy::view`
   (que rechaza privados), el `Portal\ProjectDocumentController`
   aplica `public()` en el query del listado. Asi aunque un cliente
   descubra un ID de documento privado, ni siquiera lo recibe en
   la respuesta de `index`. Para `show` la policy ya cierra el
   paso con 403, testeado explicitamente.

5. **Busqueda con escape de wildcards**: `addcslashes($term, '%_\\')`
   evita que buscar "100%" o "user_id" se conviertan en patrones
   `LIKE` que matchean demasiado. Comportamiento de busqueda
   literal, predecible.

6. **Botones en el header del proyecto, no en el sidebar**: el
   sidebar admin/portal sigue mostrando "Proyectos" como unica
   entrada de alto nivel. Los documentos viven dentro del proyecto,
   asi que el acceso natural es el header. Se uso `Route::has(...)`
   por si en una fase futura se reorganiza la navegacion, no
   quedando un link roto.

7. **Estilos del markdown en CSS plano, no en `@apply`**: el
   partial `markdown-body` incluye los estilos necesarios. Usar
   clases de Tailwind para cada `h1`, `h2`, etc. del markdown
   requeria `@apply` que infla el bundle con clases que solo se
   usan en este contexto.

8. **`created_by` no se modifica en update**: el campo representa
   autoria historica. Si en una iteracion futura se quiere mostrar
   "ultima edicion por X" se anadira `updated_by` con su propia
   logica, sin romper el significado del `created_by` actual.

9. **No se anadio `is_default` ni un flag "requiere aprobacion"**:
   fuera de scope. El admin es el unico publicador y no hay flujo
   de aprobacion en MVP.

## Pendiente (fuera de scope de fase 4)

- Versionado / historial de documentos: cada edicion sobreescribe.
- Subida de archivos adjuntos (PDFs, imagenes). La estructura de
  `storage/app/clientflow/projects/{id}/documents/` ya esta
  contemplada en `docs/ARCHITECTURE.md` pero no se usa en MVP.
- Exportar un documento a PDF desde el admin.
- Comentarios o reacciones sobre un documento.
- Categorias o tags para organizar documentos.
- Editor WYSIWYG (el TODO pedia explicitamente "markdown con
  preview", no WYSIWYG).
- Full-text search nativo de MySQL: el `LIKE` con escape es
  suficiente para el volumen esperado en MVP. Si en una fase
  futura se quiere busqueda full-text, MySQL 8.4 lo soporta con
  indices `FULLTEXT`.

## Riesgos y notas para la fase 5

- `Project::documents()` ya existia como firma adelantada desde
  la fase 2; al crear `ProjectDocument` se hace cargo de la
  relacion. No hubo que tocar `Project.php`.
- El editor Livewire interactua con la API de Livewire 4
  (`Livewire.dispatch`, `Livewire.on`, `@script`). Si en una
  actualizacion futura Livewire cambia alguna de estas APIs,
  sera necesario migrar el JS inline.
- La busqueda `LIKE` sobre `content` (longtext) es lineal: para
  miles de documentos largos puede empezar a notarse. Antes de
  llegar ahi conviene migrar a busqueda full-text de MySQL.
- El modelo `Project` tiene declaraciones adelantadas de
  `messages()` (fase 5) que se harian cargo en la fase siguiente.
  El campo `content` del documento y los `messages` son cosas
  distintas (markdown en uno, texto corto en otro) y estan en
  tablas separadas.
