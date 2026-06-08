# Agentes IA de ClientFlow

Esta carpeta define agentes de apoyo para desarrollar ClientFlow de forma ordenada. Cada agente tiene un foco concreto, debe leer primero `docs/README.md` y los documentos relevantes dentro de `docs/docs/`, y debe trabajar por modulo, no intentando construir todo el producto de una vez.

## Reglas comunes

- Mantener el MVP alineado con `docs/docs/PRD_V2.md`.
- Respetar la arquitectura de `docs/docs/ARCHITECTURE_V2.md`.
- Consultar `docs/docs/DESIGN.md` y `docs/docs/WIREFRAMES_DESKTOP.md` antes de crear pantallas.
- Seguir el orden de `docs/docs/IMPLEMENTATION_STARTER.md`.
- Priorizar cambios pequenos, verificables y faciles de revisar.
- No implementar IA, multiempresa, API publica, facturacion ni integraciones externas hasta que el MVP base este estable.
- No introducir Redis, workers permanentes ni servicios obligatorios fuera de Docker local.

## Agentes disponibles

- `product-architect.md`: convierte documentos de producto en decisiones tecnicas y tareas accionables.
- `laravel-backend.md`: implementa dominio, modelos, migraciones, policies, servicios y rutas Laravel.
- `livewire-frontend.md`: implementa pantallas Blade/Livewire manteniendo la experiencia premium del portal.
- `qa-reviewer.md`: revisa riesgos, permisos, pruebas faltantes y regresiones.
- `devops-docker.md`: mantiene Docker local, variables de entorno y comandos de arranque.

## Flujo recomendado

1. Pedir a `product-architect` que refine el modulo a implementar.
2. Pedir a `laravel-backend` la base de datos, reglas y permisos.
3. Pedir a `livewire-frontend` la interfaz.
4. Pedir a `qa-reviewer` revision antes de cerrar la tarea.
5. Pedir a `devops-docker` ayuda si falla el entorno local.
