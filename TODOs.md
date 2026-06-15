# TODOs ClientFlow

## Pre-requisito: Reset del proyecto

- [ ] Ejecutar cleanup segun `docs/CLEANUP.md` para dejar Laravel vacio
- [ ] Verificar que `php artisan serve` arranca sin errores
- [ ] Verificar que Docker arranca con `docker compose up -d --build`
- [ ] Verificar que solo existen las rutas默认 (`/`, `/up`)

---

## Fase 1: Foundation (Auth + Roles + Organizations)

### Auth y roles

- [ ] Crear enum `UserRole` con `admin` y `client`
- [ ] Anadir campo `role` a migracion `users` (default `client`, indexed)
- [ ] Crear middleware `EnsureUserIsAdmin`
- [ ] Crear middleware `EnsureUserIsClient`
- [ ] Registrar middleware aliases en `bootstrap/app.php`
- [ ] Implementar login con redireccion por rol
- [ ] Implementar registro (solo role `client`)
- [ ] Implementar recuperacion de password
- [ ] Crear layout `auth` (centrado, limpio)
- [ ] Crear layout `admin` (sidebar + header)
- [ ] Crear layout `portal` (sidebar + header, estilo cliente)
- [ ] Crear vista `login`
- [ ] Crear vista `register`
- [ ] Crear vista `password.request`
- [ ] Crear vista `password.reset`
- [ ] Proteger rutas `/admin/*` con middleware `admin`
- [ ] Proteger rutas `/portal/*` con middleware `client`

### Organizations

- [ ] Crear migracion `organizations`
- [ ] Crear migracion `organization_user` (pivot con role)
- [ ] Crear migracion `organization_invitations`
- [ ] Crear modelo `Organization` con relaciones
- [ ] Crear modelo `OrganizationInvitation` con relaciones
- [ ] Crear CRUD organizaciones (admin)
- [ ] Crear vista listado de organizaciones (admin)
- [ ] Crear vista detalle de organizacion (admin)
- [ ] Implementar invitacion de miembros por email
- [ ] Crear vista aceptar invitacion
- [ ] Crear vista miembros de organizacion (admin)

### Dashboards

- [ ] Crear `/admin/dashboard` con organizaciones y proyectos recientes
- [ ] Crear `/portal/dashboard` con proyectos del cliente
- [ ] Crear welcome page para usuarios no autenticados

---

## Fase 2: Projects

- [ ] Crear migracion `projects`
- [ ] Crear migracion `project_user` (pivot)
- [ ] Crear enum `ProjectStatus` (planning, in_progress, on_hold, waiting_client, completed, archived)
- [ ] Crear modelo `Project` con relaciones
- [ ] Crear CRUD proyectos (admin, asociados a organizacion)
- [ ] Crear vista listado de proyectos (admin)
- [ ] Crear vista detalle de proyecto (admin)
- [ ] Crear vista listado de proyectos (portal, solo proyectos de sus organizaciones)
- [ ] Crear vista detalle de proyecto (portal)
- [ ] Implementar asignacion de miembros a proyecto
- [ ] Implementar barra de progreso manual
- [ ] Implementar archivar proyecto

---

## Fase 3: Kanban (vitaminado)

### Board columns

- [ ] Crear migracion `board_columns`
- [ ] Crear modelo `BoardColumn` con relaciones
- [ ] Crear columnas default al crear proyecto (To Do, In Progress, Review, Done)
- [ ] Permitir personalizar columnas (nombre, color, orden)
- [ ] Crear vista de gestion de columnas

### Tasks

- [ ] Crear migracion `tasks`
- [ ] Crear enum `TaskPriority` (critical, high, medium, low)
- [ ] Crear enum `TaskType` (feature, bug, improvement, task)
- [ ] Crear modelo `Task` con relaciones (incluyendo `parent_id` para subtareas)
- [ ] Crear vista kanban por proyecto (drag & drop con Livewire)
- [ ] Crear vista lista alternativa por proyecto
- [ ] Crear/eliminar tareas
- [ ] Editar tarea (titulo, descripcion, prioridad, tipo, estimacion, fecha limite, asignado)
- [ ] Mover tareas entre columnas (drag & drop)
- [ ] Implementar subtareas (parent_id)
- [ ] Filtros: prioridad, asignado, fecha limite
- [ ] Vista kanban para portal cliente (solo lectura)
- [ ] Crear componente Livewire para kanban board

---

## Fase 4: Documentation

- [ ] Crear migracion `project_documents`
- [ ] Crear enum `DocumentVisibility` (private, public)
- [ ] Crear modelo `ProjectDocument` con relaciones
- [ ] Crear editor markdown con preview (Livewire)
- [ ] Crear CRUD documentos (admin: private y public)
- [ ] Crear listado de documentos por proyecto (admin)
- [ ] Crear vista documentos publicos por proyecto (portal)
- [ ] Implementar busqueda de documentos
- [ ] Documentos `private` solo visibles por admin
- [ ] Documentos `public` visibles por clientes del proyecto

---

## Fase 5: Chat por proyecto

- [ ] Crear migracion `project_messages`
- [ ] Crear enum `MessageType` (text, system, file)
- [ ] Crear modelo `ProjectMessage` con relaciones
- [ ] Crear vista chat por proyecto (admin)
- [ ] Crear vista chat por proyecto (portal)
- [ ] Implementar polling Livewire para mensajes nuevos (cada 5s)
- [ ] Generar mensajes de sistema automaticos (tarea creada, estado cambiado, etc.)
- [ ] Implementar notificaciones in-app por mensajes nuevos
- [ ] Implementar notificaciones email por mensajes nuevos
- [ ] Indicador de mensajes no leidos por proyecto en sidebar

---

## Fase 6: MCP Server (solo lectura)

- [ ] Crear ruta `/api/mcp/sse` para endpoint SSE
- [ ] Crear ruta `/api/mcp/messages` para JSON-RPC
- [ ] Implementar autenticacion via API tokens (Laravel Sanctum o custom)
- [ ] Implementar tool `list_projects`
- [ ] Implementar tool `get_project`
- [ ] Implementar tool `list_tasks`
- [ ] Implementar tool `get_task`
- [ ] Implementar tool `get_documents` (incluye privados)
- [ ] Implementar tool `search_documents`
- [ ] Implementar tool `get_project_status`
- [ ] Crear documentacion de uso del MCP server
- [ ] Testear conexion desde un IDE con MCP client

---

## Fase 7: IA para el cliente

- [ ] Crear migracion `ai_configs`
- [ ] Crear migracion `ai_chat_sessions`
- [ ] Crear migracion `ai_chat_messages`
- [ ] Crear modelo `AiConfig` con API key encriptada
- [ ] Crear modelo `AiChatSession` y `AiChatMessage`
- [ ] Crear enum `AiProvider` (openai, anthropic)
- [ ] Crear enum `AiChatRole` (user, assistant, system)
- [ ] Crear vista configuracion IA (admin: provider, API key)
- [ ] Implementar servicio `AiService` con soporte multi-provider
- [ ] Crear vista chat IA por proyecto (portal)
- [ ] Inyecto contexto del proyecto en el system prompt (estado, tareas, docs)
- [ ] Implementar sesiones de chat (crear, continuar, historial)
- [ ] Rate limiting para evitar abuso

---

## Fase 8: Calendario

- [ ] Crear migracion `calendar_events`
- [ ] Crear migracion `calendar_event_user` (asistentes)
- [ ] Crear enum `CalendarEventType` (meeting, deadline, milestone)
- [ ] Crear modelos `CalendarEvent` con relaciones
- [ ] Crear vista calendario mensual/semanal (admin)
- [ ] Crear vista calendario (portal)
- [ ] Crear/editar eventos ligados a proyectos
- [ ] Invitar asistentes a eventos
- [ ] Implementar notificaciones antes de eventos

---

## Fase 9: PWA

- [ ] Crear `manifest.json` con iconos
- [ ] Crear service worker basico
- [ ] Implementar cachear vistas estaticas y datos basicos
- [ ] Implementar install prompt
- [ ] Push notifications para mensajes y tareas

---

## Transversal: Agentes IA (templates)

- [ ] Crear migracion `agent_templates`
- [ ] Crear migracion `project_agents` (pivot)
- [ ] Crear modelo `AgentTemplate` con relaciones
- [ ] Crear modelo `ProjectAgent` (pivot con system_prompt_override)
- [ ] Crear CRUD templates de agentes (admin)
- [ ] Crear vista biblioteca de templates
- [ ] Implementar asignar template a proyecto
- [ ] Implementar exportar config de agente (JSON para uso en IDEs)

---

## Transversal: Notificaciones

- [ ] Configurar database notifications de Laravel
- [ ] Implementar notificaciones in-app (badge en sidebar, lista)
- [ ] Implementar notificaciones email (resumen diario, mensajes nuevos, deadlines)
- [ ] Notificacion: nuevo mensaje en chat del proyecto
- [ ] Notificacion: tarea asignada
- [ ] Notificacion: tarea con deadline cercano
- [ ] Notificacion: invitacion a organizacion

---

## Criterios generales

- [ ] Mantener compatibilidad con hosting compartido
- [ ] No introducir Redis obligatorio
- [ ] No introducir workers permanentes
- [ ] No publicar contenido IA automaticamente
- [ ] Proteger todos los archivos privados mediante autorizacion
- [ ] Todos los endpoints protegidos con Policies
- [ ] Tests para cada modulo critico
- [ ] Docker local funcionando en todo momento