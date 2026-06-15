# MCP Server Agent

## Mision

Implementar el MCP (Model Context Protocol) server de ClientFlow como endpoint HTTP/SSE dentro del monolito Laravel, permitiendo acceso de solo lectura desde IDEs y herramientas de desarrollo.

## Documentos que debe leer

- `docs/ARCHITECTURE.md` (seccion MCP Server)
- `docs/DATA_MODEL.md`
- `docs/PRD.md` (Fase 6)
- `TODOs.md`

## Protocolo MCP

El MCP protocol funciona sobre HTTP + SSE:
- Endpoint SSE: `/api/mcp/sse` para establecer la conexiones streaming.
- Endpoint messages: `/api/mcp/messages` para JSON-RPC request/response.

Autenticacion via API token almacenado en `personal_access_tokens` (Laravel Sanctum o implementacion custom).

## Tools a implementar (solo lectura)

| Tool | Descripcion |
|---|---|
| `list_projects` | Lista todos los proyectos del admin |
| `get_project` | Detalle de un proyecto con estado actual |
| `list_tasks` | Tareas de un proyecto, filtrables por estado/prioridad |
| `get_task` | Detalle de una tarea especifica |
| `get_documents` | Documentos de un proyecto (incluye privados) |
| `search_documents` | Buscar en documentos por contenido |
| `get_project_status` | Resumen de estado del proyecto (progreso, tareas, fechas) |

## Reglas

- Solo lectura: ninguna tool puede crear, modificar o eliminar datos.
- Autenticacion obligatoria: todas las requests necesitan un API token valido.
- Los documentos privados son accesibles via MCP (el admin es el que configura el acceso desde su IDE).
- Seguir la especificacion MCP para HTTP/SSE transport.
- No implementar capacidad de escritura en MVP.

## Verificacion minima

- Testear conexion desde un IDE con un MCP client (Claude Desktop, Cursor, etc.).
- Verificar que las tools devuelven datos correctos.
- Verificar que un token invalido devuelve 401.
- Verificar que un token valido sin permisos devuelve 403.