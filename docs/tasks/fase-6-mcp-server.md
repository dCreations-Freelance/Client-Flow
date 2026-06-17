# Fase 6 — MCP Server (solo lectura)

Documento técnico de la implementación del MCP (Model Context Protocol)
server de ClientFlow, pensado para que IDEs y herramientas de desarrollo
puedan consultar proyectos, tareas y documentos en solo lectura.

## Alcance

Según `TODOs.md`, la fase 6 incluye:

- Endpoint SSE `/api/mcp/sse`.
- Endpoint JSON-RPC `/api/mcp/messages`.
- Autenticación via API tokens.
- Tools de solo lectura: `list_projects`, `get_project`, `list_tasks`,
  `get_task`, `get_documents`, `search_documents`, `get_project_status`.
- Documentación de uso.
- Tests de conexión y funcionamiento.

## Decisiones técnicas

### Autenticación con Laravel Sanctum

Se instaló `laravel/sanctum` y se publicaron su configuración y migración
`personal_access_tokens`. El modelo `User` ahora usa `HasApiTokens`.

La ruta MCP usa el middleware `auth:sanctum` y, además, un middleware
propio `EnsureMcpAccess` que restringe el acceso a usuarios con rol
`admin`. Esto simplifica la autorización y permite exponer documentos
privados a través del MCP, ya que solo el administrador configura el
acceso desde su IDE.

**Nota importante:** se evitó la recursión infinita que puede ocurrir si
el guard `api` de `config/auth.php` usa driver `sanctum` mientras que
`config/sanctum.php` apunta a `['api']`. La configuración final es:

- `config/auth.php`: solo guard `web` (session).
- `config/sanctum.php`: `'guard' => ['web']`.
- Rutas MCP: `auth:sanctum`.

Sanctum autentica primero por bearer token; si no existe, recurre al
session guard `web`, que en una petición API sin cookie no autenticará.

### Transporte HTTP + SSE sin Redis

El protocolo MCP sobre HTTP requiere:

1. Conexión SSE donde el servidor anuncia el endpoint de mensajes.
2. POST JSON-RPC al endpoint anunciado.
3. Respuesta del servidor enviada por el canal SSE.

Para implementar el paso 3 sin Redis ni WebSockets, se usan dos tablas:

- `mcp_sessions`: sesiones activas con `session_id` público.
- `mcp_messages`: mensajes encolados para cada sesión, con `sent_at`
  nullable.

Flujo:

1. El cliente abre `/api/mcp/sse`.
2. Se crea una `McpSession` y se devuelve un evento `endpoint` con la
   URL `/api/mcp/messages?session_id=xxx`.
3. El cliente envía JSON-RPC por POST.
4. El controlador `McpController::messages()` procesa el mensaje y
   encola la respuesta en `mcp_messages`.
5. El loop SSE consulta mensajes pendientes cada segundo y los emite.

El loop SSE dura 60 s en producción y 0 s en tests para no bloquear la
suite. Envía heartbeats cada 15 s para mantener la conexión viva.

## Cambios por capa

### 1. Dependencias y configuración

| Archivo | Propósito |
|---|---|
| `composer.json` | Añadido `laravel/sanctum`. |
| `config/sanctum.php` | Guard fallback `['web']` para evitar recursión. |
| `config/auth.php` | Se mantuvo solo el guard `web`; no se definió guard `api` con driver `sanctum`. |
| `app/Models/User.php` | Añadido trait `HasApiTokens`. |

### 2. Migraciones

| Archivo | Propósito |
|---|---|
| `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php` | Tabla de tokens de Sanctum (publicada por el paquete). |
| `database/migrations/2026_06_17_061146_create_mcp_sessions_table.php` | Sesiones SSE: `user_id`, `session_id`, `last_activity_at`. |
| `database/migrations/2026_06_17_061147_create_mcp_messages_table.php` | Mensajes encolados: `mcp_session_id`, `payload`, `sent_at`. |

### 3. Modelos

- `App\Models\McpSession`: relaciones `user()` y `messages()`.
- `App\Models\McpMessage`: relación `session()`, cast `payload` a array.

### 4. Middleware

`App\Http\Middleware\EnsureMcpAccess`:

- Rechaza con JSON-RPC error 403 si el usuario no es admin.
- Se aplica a ambas rutas MCP junto con `auth:sanctum`.

### 5. Rutas

`routes/api.php`:

```txt
GET  /api/mcp/sse      → Api\McpController@sse
POST /api/mcp/messages → Api\McpController@messages
```

Registrado en `bootstrap/app.php` mediante `api: __DIR__.'/../routes/api.php'`.

### 6. Controlador

`App\Http\Controllers\Api\McpController`:

- `sse(Request)`: StreamedResponse con handshake, heartbeats y emisión de
  mensajes encolados.
- `messages(Request)`: JsonResponse que acepta el payload, lo procesa y
  encola la respuesta.

### 7. Servicios MCP

`App\Services\Mcp\McpServer`:

- `handshake()`: responde con `protocolVersion`, `capabilities`,
  `serverInfo` y `_meta.messagesEndpoint`.
- `handleMessage()`: despacha `initialize`, `tools/list` y `tools/call`.
- Respuestas de error JSON-RPC conforme a los códigos estándar.

`App\Services\Mcp\McpToolRegistry`:

- Registra las 7 tools.
- `definitions()`: devuelve el catálogo con esquemas.
- `execute()`: invoca una tool por nombre.

`App\Services\Mcp\McpSessionStore`:

- `create()`, `find()`, `push()`, `pendingMessages()`, `touch()`,
  `cleanup()`.

`App\Services\Mcp\Contracts\McpTool`:

- Interfaz común: `name()`, `description()`, `schema()`, `execute()`.

### 8. Tools

| Tool | Entrada | Salida |
|---|---|---|
| `list_projects` | `limit`, `offset`, `status` | Lista paginada de proyectos. |
| `get_project` | `project_id` | Detalle completo del proyecto. |
| `list_tasks` | `project_id`, `status`, `priority`, `assignee_id` | Tareas con filtros. |
| `get_task` | `task_id` | Detalle de tarea con subtareas. |
| `get_documents` | `project_id` | Documentos (incluye privados). |
| `search_documents` | `project_id`, `query` | Búsqueda en título y contenido. |
| `get_project_status` | `project_id` | Resumen de estado del proyecto. |

Todas las tools verifican permisos mediante las policies existentes de
Laravel (`$user->can('view', $project)` / `can('view', $task)`).

### 9. Comando artisan

`App\Console\Commands\McpTokenCommand`:

- `php artisan mcp:token {user} {--name=}`.
- Genera un token de Sanctum con habilidad `mcp:read` para un admin.
- Muestra el token en pantalla una sola vez.

## Tests

Nuevos tests en `tests/Feature/Api/`:

| Archivo | Tests | Cobertura |
|---|---|---|
| `McpAuthenticationTest.php` | 4 | Token inválido → 401, cliente → 403, admin → acceso. |
| `McpSseTest.php` | 1 | Handshake SSE anuncia endpoint de mensajes. |
| `McpToolsTest.php` | 8 | Cada tool devuelve datos correctos; `get_documents` incluye privados; `search_documents` filtra por contenido. |

Total añadido: **13 tests**.

## Verificación final

```bash
cd app
php artisan test
```

Resultado:

- **265 tests pasan, 661 aserciones** (+13 tests y +59 aserciones
  respecto a la fase 5).
- Build Vite sin warnings.

## Uso básico

1. Generar token para un admin:

   ```bash
   php artisan mcp:token 1 --name=cursor
   ```

2. Conectar un cliente MCP genérico:

   - **SSE URL**: `https://tu-dominio.com/api/mcp/sse`
   - **Headers**: `Authorization: Bearer <token>`
   - El servidor responde con un evento `endpoint` que indica dónde
     enviar los POST JSON-RPC.

3. Enviar mensajes JSON-RPC al endpoint recibido.

## Pendiente y notas para futuras fases

- Soporte a clientes MCP específicos: la implementación es genérica
  (HTTP+SSE). A futuro se podría añadir configuración concreta para
  clientes populares (Cursor, Claude Desktop, Windsurf, OpenCode, etc.)
  si detectan diferencias en el handshake o en los nombres de eventos.
- Capacidad de escritura: no está implementada. Si en el futuro se
  añaden tools de escritura, se recomienda separarlas en habilidades
  distintas (`mcp:write`) y reevaluar permisos.
- Rate limiting: actualmente no hay límites por usuario. Se puede
  añadir middleware de throttle en `routes/api.php`.
- Limpieza de sesiones: `McpSessionStore::cleanup()` puede invocarse
  desde un comando programado o al iniciar cada conexión SSE.
