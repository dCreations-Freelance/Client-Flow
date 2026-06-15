# Agentes IA de ClientFlow

Esta carpeta define agentes de apoyo para desarrollar ClientFlow de forma ordenada. Cada agente tiene un foco concreto, debe leer primero `docs/README.md` y los documentos relevantes dentro de `docs/`, y debe trabajar por modulo, no intentando construir todo el producto de una vez.

## Reglas comunes

- Mantener el MVP alineado con `docs/PRD.md`.
- Respetar la arquitectura de `docs/ARCHITECTURE.md`.
- Respetar el modelo de datos de `docs/DATA_MODEL.md`.
- Trabajar por modulo: no construir mas de una fase a la vez.
- Priorizar cambios pequenos, verificables y faciles de revisar.
- No introducir Redis, workers permanentes ni servicios obligatorios fuera de Docker local.
- Usar Livewire 4 para toda la UI interactiva. No usar Alpine.js para logica de negocio.
- Consultar `TODOs.md` para saber que falta por implementar.

## Agentes disponibles

- `product-architect.md`: convierte documentos de producto en decisiones tecnicas y tareas accionables.
- `laravel-backend.md`: implementa dominio, modelos, migraciones, policies, servicios y rutas Laravel.
- `livewire-frontend.md`: implementa pantallas Blade/Livewire manteniendo la experiencia premium del portal.
- `mcp-server.md`: implementa el MCP server para conexion desde IDEs.
- `qa-reviewer.md`: revisa riesgos, permisos, pruebas faltantes y regresiones.
- `devops-docker.md`: mantiene Docker local, variables de entorno y comandos de arranque.

## Flujo recomendado

1. Pedir a `product-architect` que refine el modulo a implementar.
2. Pedir a `laravel-backend` la base de datos, modelos y permisos.
3. Pedir a `livewire-frontend` la interfaz.
4. Pedir a `mcp-server` cuando se llegue a la fase 6.
5. Pedir a `qa-reviewer` revision antes de cerrar la tarea.
6. Pedir a `devops-docker` ayuda si falla el entorno local.