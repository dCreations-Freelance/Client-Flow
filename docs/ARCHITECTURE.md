# ARCHITECTURE.md — Arquitectura tecnica de ClientFlow

## Principios

- Monolito Laravel optimizado para hosting compartido.
- Sin Redis obligatorio, sin workers permanentes, sin WebSocket en MVP.
- Livewire 4 para toda la UI interactiva (no SPA).
- Tailwind CSS 4 para estilos, manteniendo la paleta warm actual.
- Docker solo para desarrollo local.
- MCP server como endpoint HTTP/SSE dentro del mismo monolito.
- II configurable por el admin (API key por provider).

## Stack

| Capa | Tecnologia |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Blade + Livewire 4 + Tailwind CSS 4 |
| Build | Vite |
| Base de datos | MySQL 8.4 |
| Cache/Queue | `database` driver (sync en MVP) |
| Storage | Local (`storage/app/clientflow/`) |
| Docker | PHP-FPM 8.4 + Nginx + MySQL 8.4 + Node 22 |

## Estructura de directorios

```txt
app/
├── Actions/            # Acciones dedicadas (patrón command)
├── DTOs/               # Data Transfer Objects
├── Enums/              # Enums del dominio
│   ├── UserRole.php
│   ├── ProjectStatus.php
│   ├── TaskPriority.php
│   ├── TaskType.php
│   ├── DocumentVisibility.php
│   └── MessageType.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/      # Panel administrador
│   │   ├── Portal/     # Portal cliente
│   │   └── Auth/       # Login, registro, invitacion
│   ├── Middleware/
│   │   ├── EnsureUserIsAdmin.php
│   │   └── EnsureUserIsClient.php
│   └── Requests/       # Form requests con validacion
├── Livewire/
│   ├── Admin/          # Componentes Livewire admin
│   ├── Portal/         # Componentes Livewire portal cliente
│   └── Shared/         # Componentes compartidos
├── Models/             # Modelos Eloquent
├── Policies/           # Policies de autorizacion
├── Services/           # Logica de negocio reutilizable
│   ├── Ai/             # Servicio de IA configurable
│   ├── Mcp/            # MCP server logic
│   └── Notifications/  # Servicios de notificacion
└── ViewModels/         # ViewModels para vistas complejas
```

## Rutas

```txt
/                       → Redirect por rol o landing

# Auth
/login
/register
/invitation/{token}
/password/reset

# Admin (/admin/*)
/admin/dashboard
/admin/organizations             → CRUD organizaciones
/admin/organizations/{org}/members  → Gestion miembros
/admin/projects                  → CRUD proyectos
/admin/projects/{project}        → Detalle proyecto
/admin/projects/{project}/board → Kanban
/admin/projects/{project}/documents → Documentos
/admin/projects/{project}/chat  → Chat admin
/admin/projects/{project}/calendar → Calendario
/admin/agent-templates           → Templates agentes IA
/admin/settings/ai              → Config IA

# Portal (/portal/*)
/portal/dashboard
/portal/organizations/{org}     → Vista organizacion
/portal/projects/{project}      → Detalle proyecto
/portal/projects/{project}/board → Kanban cliente (solo lectura)
/portal/projects/{project}/documents → Docs publicas
/portal/projects/{project}/chat → Chat cliente
/portal/projects/{project}/ai   → Chat IA
/portal/calendar                 → Calendario cliente

# MCP (/api/mcp/*)
/api/mcp/sse                     → SSE endpoint
/api/mcp/messages                 → JSON-RPC messages
```

## Permisos

- **Admin**: acceso total a todo.
- **Client**: solo puede ver organizaciones donde es miembro, proyectos de esas organizaciones, documentos publicos, tareas asignadas, mensajes de sus proyectos.
- Los documentos privados solo son visibles por admin.
- El MCP solo lee, nunca escribe.
- Toda autorizacion se verifica con Policies de Laravel.

## Storage

```txt
storage/app/clientflow/
├── organizations/{org_id}/
│   └── avatars/
├── projects/{project_id}/
│   ├── documents/
│   └── media/
└── avatars/
```

Archivos privados servidos mediante controlador con autorizacion.

## Docker local

Puertos:
- App: `http://localhost:8080`
- Vite: `http://localhost:5173`
- MySQL: `127.0.0.1:3307`

Servicios: PHP-FPM 8.4, Nginx 1.27, MySQL 8.4, Node 22.

## Colas y cache

- MVP usa `QUEUE_CONNECTION=sync` (sin workers).
- MVP usa `CACHE_STORE=database`.
- Si en el futuro se necesita rendimiento, se puede introducir Redis sin romper nada.

## II Service

- Provider configurable: OpenAI, Anthropic, etc.
- API key almacenada encriptada en BD (`ai_configs`).
- Admin configura provider global o por proyecto.
- Se inyecta contexto del proyecto (estado, tareas, docs) en el system prompt.
- La IA nunca escribe directamente en la app; solo genera respuestas para el usuario.

## MCP Server

- Implementado como ruta API dentro del monolito Laravel.
- Protocolo MCP sobre HTTP + SSE.
- Autenticacion via API token personal en tabla `personal_access_tokens` (Laravel Sanctum o custom).
- Tools expuestas (solo lectura):
  - `list_projects` → Lista proyectos del admin
  - `get_project` → Detalle de proyecto con estado
  - `list_tasks` → Tareas de un proyecto
  - `get_task` → Detalle de tarea
  - `get_documents` → Documentos de un proyecto (incluye privados)
  - `search_documents` → Buscar en documentos
  - `get_project_status` → Resumen de estado del proyecto
- Sin capacidad de escritura en MVP.

## Codigo

### Comentarios

- Todo el codigo y los comentarios se escriben en **castellano**.
- Los comentarios explican el **"por que"** (motivacion, contexto), no el "que" (el codigo ya dice lo que hace).
- No dejar codigo comentado sin eliminar.

### PHPDoc

- Todo metodo **publico** debe llevar PHPDoc con:
  - Descripcion breve de una linea.
  - `@param` para cada parametro con tipo y descripcion.
  - `@return` con tipo y descripcion.
  - `@throws` solo si es relevante para quien llama.
- Metodos `protected` y `private` pueden omitir PHPDoc si el nombre es suficientemente claro.
- Las propiedades con atributos nativos de PHP 8 (`#[Fillable]`, `#[Hidden]`, etc.) no necesitan PHPDoc redundante.

### Nombre de clases, metodos y variables

- Clases y traits: `PascalCase` (ej. `EnsureUserIsAdmin`).
- Metodos y funciones: `camelCase` (ej. `isAdmin()`).
- Variables y propiedades: `camelCase`.
- Constantes de clase: `UPPER_SNAKE_CASE`.
- Tablas y columnas en BD: `snake_case`.
- Rutas: `kebab-case` (ej. `/admin/project-tasks`).

### Estructura de archivos

- Un archivo por clase.
- Namespace alineado con la ruta del directorio.
- Importaciones ordenadas: primero Laravel core, luego paquetes propios, luego modelos, etc.