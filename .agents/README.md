# ClientFlow - Agentes opencode Go

Este directorio contiene las instrucciones compartidas de los agentes.
Las definiciones formales de cada agente (con frontmatter YAML para opencode Go) están en `.opencode/agents/`.

## Reglas comunes

- Mantener el MVP alineado con `docs/PRD.md`.
- Respetar la arquitectura de `docs/ARCHITECTURE.md`.
- Respetar el modelo de datos de `docs/DATA_MODEL.md`.
- Trabajar por modulo: no construir mas de una fase a la vez.
- Priorizar cambios pequenos, verificables y faciles de revisar.
- No introducir Redis, workers permanentes ni servicios obligatorios fuera de Docker local.
- Usar Livewire 4 para toda la UI interactiva. No usar Alpine.js para logica de negocio.
- Consultar `TODOs.md` para saber que falta por implementar.
- Escribir el codigo y los comentarios en castellano. Los comentarios deben ser legibles y explicar el "por que", no el "que" (el codigo ya dice el que).
- Anadir PHPDoc en todo metodo publico: descripcion breve, `@param` y `@return`.
- Documentar en `docs/tasks/<fase>.md` todo cambio implementado (nuevas migraciones, modelos, rutas, vistas, componentes), siguiendo el formato existente.

## Agentes disponibles (en `.opencode/agents/`)

| Agente | Modo | Descripcion |
|---|---|---|
| `product-architect` | subagent | Convierte documentos de producto en decisiones tecnicas y tareas accionables |
| `laravel-backend` | subagent | Implementa dominio, modelos, migraciones, policies, servicios y rutas Laravel |
| `livewire-frontend` | subagent | Implementa pantallas Blade/Livewire manteniendo la experiencia premium del portal |
| `mcp-server` | subagent | Implementa el MCP server para conexion desde IDEs |
| `security-auditor` | subagent | Audita seguridad de la app y del stack: policies, fugas, hardening, dependencias |
| `qa-reviewer` | subagent | Revisa riesgos, permisos, pruebas faltantes y regresiones |
| `devops-docker` | subagent | Mantiene Docker local, variables de entorno y comandos de arranque |

## Flujo recomendado

1. `@product-architect` - refina el modulo a implementar.
2. `@laravel-backend` - implementa base de datos, modelos y permisos.
3. `@livewire-frontend` - construye la interfaz.
4. `@mcp-server` - cuando se llegue a la fase 6 del PRD.
5. `@security-auditor` - auditoria de seguridad antes del merge.
6. `@qa-reviewer` - revision funcional antes de cerrar la tarea.
7. `@devops-docker` - ayuda si falla el entorno local.

## Uso en opencode Go

Los agentes se invocan con `@<nombre>` en el chat de opencode.
Ejemplo: `@product-architect analiza la fase 2 y generame las tareas`.