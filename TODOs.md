# TODOs ClientFlow

## Pre-requisito: Reset del proyecto

- [x] Ejecutar cleanup segun `docs/CLEANUP.md` para dejar Laravel vacio
- [x] Verificar que `php artisan serve` arranca sin errores
- [x] Verificar que Docker arranca con `docker compose up -d --build`
- [x] Verificar que solo existen las rutas por defecto (`/`, `/up`)

---

## Fase 1: Foundation (Auth + Roles + Organizations)

### Auth y roles

- [x] Crear enum `UserRole` con `admin` y `client`
- [x] Anadir campo `role` a migracion `users` (default `client`, indexed)
- [x] Crear middleware `EnsureUserIsAdmin`
- [x] Crear middleware `EnsureUserIsClient`
- [x] Registrar middleware aliases en `bootstrap/app.php`
- [x] Implementar login con redireccion por rol
- [x] Implementar registro (solo role `client`)
- [x] Implementar recuperacion de password
- [x] Crear layout `auth` (centrado, limpio)
- [x] Crear layout `admin` (sidebar + header)
- [x] Crear layout `portal` (sidebar + header, estilo cliente)
- [x] Crear vista `login`
- [x] Crear vista `register`
- [x] Crear vista `password.request`
- [x] Crear vista `password.reset`
- [x] Proteger rutas `/admin/*` con middleware `admin`
- [x] Proteger rutas `/portal/*` con middleware `client`

### Organizations

- [x] Crear migracion `organizations`
- [x] Crear migracion `organization_user` (pivot con role)
- [x] Crear migracion `organization_invitations`
- [x] Crear modelo `Organization` con relaciones
- [x] Crear modelo `OrganizationInvitation` con relaciones
- [x] Crear CRUD organizaciones (admin)
- [x] Crear vista listado de organizaciones (admin)
- [x] Crear vista detalle de organizacion (admin)
- [x] Implementar invitacion de miembros por email
- [x] Crear vista aceptar invitacion
- [x] Crear vista miembros de organizacion (admin)

### Dashboards

- [x] Crear `/admin/dashboard` con organizaciones y proyectos recientes
- [x] Crear `/portal/dashboard` con proyectos del cliente
- [x] Crear welcome page para usuarios no autenticados

---

## Fase 2: Projects

- [x] Crear migracion `projects`
- [x] Crear migracion `project_user` (pivot)
- [x] Crear enum `ProjectStatus` (planning, in_progress, on_hold, waiting_client, completed, archived)
- [x] Crear modelo `Project` con relaciones
- [x] Crear CRUD proyectos (admin, asociados a organizacion)
- [x] Crear vista listado de proyectos (admin)
- [x] Crear vista detalle de proyecto (admin)
- [x] Crear vista listado de proyectos (portal, solo proyectos de sus organizaciones)
- [x] Crear vista detalle de proyecto (portal)
- [x] Implementar asignacion de miembros a proyecto
- [x] ~~Implementar barra de progreso manual~~ Implementar barra de progreso (calculada desde tareas completadas vs tareas raiz) — ver `docs/tasks/fase-3.5-ajustes.md`
- [x] Implementar archivar proyecto

---

## Fase 2.5: Hub del proyecto (UI/UX)

Redisenyo de la pagina de detalle de proyecto (`/admin/projects/{id}` y
`/portal/projects/{id}`) para que funcione como hub con hero sticky,
tiles de resumen, preview del kanban, documentos recientes, proximo
evento, ultimo mensaje y grid de equipo. No introduce logica de
negocio nueva: reutiliza los datos y relaciones ya existentes.

Detalles en `docs/tasks/fase-2.5-project-hub.md`.

- [x] Crear DTO `App\DTOs\Project\ProjectSummary` + `BoardColumnPreview`
- [x] Crear `App\Services\Project\ProjectSummaryService` con `loadForAdmin` y `loadForPortal`
- [x] Crear partials `project-breadcrumbs`, `project-hero`, `project-stat-tile`, `project-previews`
- [x] Reescribir `admin/projects/show.blade.php` y `portal/projects/show.blade.php`
- [x] Refactorizar `Admin/ProjectController::show` y `Portal/ProjectController::show` para usar el servicio
- [x] Validar 117 tests verdes en el scope de Project (`php artisan test --filter="Project"`)

---

## Fase 3: Kanban (vitaminado)

### Board columns

- [x] Crear migracion `board_columns`
- [x] Crear modelo `BoardColumn` con relaciones
- [x] Crear columnas default al crear proyecto (Por hacer, En curso, En revision, Hecho)
- [x] Permitir personalizar columnas (nombre, color, orden)
- [x] Crear vista de gestion de columnas

### Tasks

- [x] Crear migracion `tasks`
- [x] Crear enum `TaskPriority` (critical, high, medium, low)
- [x] Crear enum `TaskType` (feature, bug, improvement, task)
- [x] Crear modelo `Task` con relaciones (incluyendo `parent_id` para subtareas)
- [x] Crear vista kanban por proyecto (drag & drop con Livewire)
- [ ] Crear vista lista alternativa por proyecto
- [x] Crear/eliminar tareas
- [x] Editar tarea (titulo, descripcion, prioridad, tipo, estimacion, fecha limite, asignado)
- [x] Mover tareas entre columnas (drag & drop)
- [x] Implementar subtareas (parent_id)
- [x] Filtros: prioridad, asignado, fecha limite
- [x] Vista kanban para portal cliente (solo lectura)
- [x] Crear componente Livewire para kanban board

---

## Fase 4: Documentation

- [x] Crear migracion `project_documents`
- [x] Crear enum `DocumentVisibility` (private, public)
- [x] Crear modelo `ProjectDocument` con relaciones
- [x] Crear editor markdown con preview (Livewire)
- [x] Crear CRUD documentos (admin: private y public)
- [x] Crear listado de documentos por proyecto (admin)
- [x] Crear vista documentos publicos por proyecto (portal)
- [x] Implementar busqueda de documentos
- [x] Documentos `private` solo visibles por admin
- [x] Documentos `public` visibles por clientes del proyecto

---

## Fase 5: Chat por proyecto

- [x] Crear migracion `project_messages`
- [x] Crear enum `MessageType` (text, system, file)
- [x] Crear modelo `ProjectMessage` con relaciones
- [x] Crear vista chat por proyecto (admin)
- [x] Crear vista chat por proyecto (portal)
- [x] Implementar polling Livewire para mensajes nuevos (cada 5s)
- [x] Generar mensajes de sistema automaticos (tarea creada, estado cambiado, etc.)
- [x] Implementar notificaciones in-app por mensajes nuevos
- [x] Implementar notificaciones email por mensajes nuevos
- [x] Indicador de mensajes no leidos por proyecto en sidebar
- [x] Implementar doble check de leido (visto) en mensajes
- [x] Crear migracion `message_reads` (pivot message_id, user_id, read_at)
- [x] Mostrar indicador "Visto" en burbujas propias al ser leido
- [x] Marcar mensajes como leidos al abrir el chat (polling)

---

## Fase 6: MCP Server (solo lectura)

- [x] Crear ruta `/api/mcp/sse` para endpoint SSE
- [x] Crear ruta `/api/mcp/messages` para JSON-RPC
- [x] Implementar autenticacion via API tokens (Laravel Sanctum o custom)
- [x] Implementar tool `list_projects`
- [x] Implementar tool `get_project`
- [x] Implementar tool `list_tasks`
- [x] Implementar tool `get_task`
- [x] Implementar tool `get_documents` (incluye privados)
- [x] Implementar tool `search_documents`
- [x] Implementar tool `get_project_status`
- [x] Crear documentacion de uso del MCP server
- [x] Testear conexion desde un IDE con MCP client

---

## Fase 7: IA para el cliente

- [x] Crear migracion `ai_configs`
- [x] Crear migracion `ai_chat_sessions`
- [x] Crear migracion `ai_chat_messages`
- [x] Crear modelo `AiConfig` con API key encriptada
- [x] Crear modelo `AiChatSession` y `AiChatMessage`
- [x] Crear enum `AiProvider` (openai, anthropic, Opencode)
- [x] Crear enum `AiChatRole` (user, assistant, system)
- [x] Crear vista configuracion IA (admin: provider, API key)
- [x] Implementar servicio `AiService` con soporte multi-provider
- [x] Crear vista chat IA por proyecto (portal)
- [x] Inyecto contexto del proyecto en el system prompt (estado, tareas, docs)
- [x] Implementar sesiones de chat (crear, continuar, historial)
- [x] Rate limiting para evitar abuso

---

## Fase 8: Calendario

- [x] Crear migracion `calendar_events`
- [x] Crear migracion `calendar_event_user` (asistentes)
- [x] Crear enum `CalendarEventType` (meeting, deadline, milestone)
- [x] Crear modelos `CalendarEvent` con relaciones
- [x] Crear vista calendario mensual/semanal (admin)
- [x] Crear vista calendario (portal)
- [x] Crear/editar eventos ligados a proyectos
- [x] Invitar asistentes a eventos
- [x] Implementar notificaciones antes de eventos

---

## Fase 9: PWA

- [x] Crear `manifest.json` con iconos
- [x] Crear service worker basico
- [x] Implementar cachear vistas estaticas y datos basicos
- [x] Implementar install prompt
- [x] Push notifications para mensajes y tareas

---

## Transversal: Agentes IA (templates)

- [x] Crear migracion `agent_templates`
- [x] Crear migracion `project_agents` (pivot)
- [x] Crear modelo `AgentTemplate` con relaciones
- [x] Crear modelo `ProjectAgent` (pivot con system_prompt_override)
- [x] Crear CRUD templates de agentes (admin)
- [x] Crear vista biblioteca de templates
- [x] Implementar asignar template a proyecto
- [x] Implementar exportar config de agente (JSON para uso en IDEs)

Detalles en `docs/tasks/fase-transversal-agentes-ia.md`.

---

## Transversal: Notificaciones

- [x] Configurar database notifications de Laravel
- [x] Implementar notificaciones in-app (badge en sidebar, lista)
- [x] Implementar notificaciones email (resumen diario, mensajes nuevos, deadlines)
- [x] Notificacion: nuevo mensaje en chat del proyecto
- [x] Notificacion: tarea asignada
- [x] Notificacion: tarea con deadline cercano
- [x] Notificacion: invitacion a organizacion

Detalles en `docs/tasks/fase-transversal-notificaciones.md`.

---

## Fase 10: Adjuntos en tareas y mensajes

- [x] Crear migracion `task_attachments`
- [x] Crear migracion `message_attachments`
- [x] Crear modelo `TaskAttachment` con relaciones
- [x] Crear modelo `MessageAttachment` con relaciones
- [x] Implementar subida de archivos en creacion/edicion de tarea
- [x] Implementar subida de archivos en chat (arrastrar y soltar / boton)
- [x] Mostrar lista de adjuntos en detalle de tarea
- [x] Mostrar adjuntos en burbujas de chat (icono + nombre + tamano)
- [x] Servir archivos mediante controlador con autorizacion
- [x] Validar tipos MIME y tamano maximo (configurable)
- [x] Implementar eliminacion de adjuntos

---

## Fase 11: Registro de tiempo

- [x] Crear migracion `time_entries`
- [x] Crear modelo `TimeEntry` con relaciones (task, user, project)
- [x] Crear enum `TimeEntryType` (manual, timer)
- [x] Implementar temporizador start/stop en vista detalle de tarea
- [x] Implementar entrada manual de tiempo (descripcion, minutos, fecha)
- [x] Crear vista de registro de tiempo por proyecto (admin)
- [x] Crear dashboard de horas: total por proyecto, por miembro, por tarea
- [x] Marcar entradas como facturables (billed flag)
- [x] Vista de resumen de tiempo en portal cliente (solo lectura)
- [x] Anadir columna `total_logged_minutes` a `tasks` como cache

---

## Fase 12: Plantillas de proyecto

- [x] Crear migracion `project_templates`
- [x] Crear migracion `project_template_columns`
- [x] Crear migracion `project_template_tasks`
- [x] Crear migracion `project_template_documents`
- [x] Crear modelo `ProjectTemplate` con relaciones
- [x] Crear CRUD plantillas (admin)
- [x] Crear vista listado de plantillas con filtro por categoria
- [x] Implementar asociacion de columnas default a plantilla
- [x] Implementar asociacion de tareas predefinidas a plantilla
- [x] Implementar asociacion de documentos esqueleto a plantilla
- [x] Boton "Crear proyecto desde plantilla" en listado de proyectos
- [x] Al crear desde plantilla, copiar columnas, tareas y documentos

---

## Fase 13: Feed de actividad

- [x] Crear migracion `activity_log`
- [x] Crear modelo `ActivityLog` con relaciones polimorficas
- [x] Crear enum `ActivityType` (task_created, task_completed, document_updated, status_changed, etc.)
- [x] Implementar `ActivityLogger` service para registrar acciones
- [x] Registrar actividad automatica: tareas creadas/completadas, docs creadas, estado cambiado, mensajes
- [x] Crear componente Livewire `ActivityFeed` por proyecto
- [x] Crear vista feed de actividad en detalle de proyecto (admin)
- [x] Crear vista feed de actividad en detalle de proyecto (portal, solo eventos visibles)
- [x] Paginacion o carga infinita en el feed
- [x] Anadir enlace "Ver actividad" en sidebar del proyecto

Detalles en `docs/tasks/fase-13-feed-actividad.md`.

---

## Criterios generales

- [x] Mantener compatibilidad con hosting compartido
- [x] No introducir Redis obligatorio
- [x] No introducir workers permanentes
- [x] No publicar contenido IA automaticamente (aplica desde fase 7)
- [x] Proteger todos los archivos privados mediante autorizacion (sin archivos privados aun, aplica desde fase 4)
- [x] Todos los endpoints protegidos con Policies
- [x] Tests para cada modulo critico
- [x] Docker local funcionando en todo momento
