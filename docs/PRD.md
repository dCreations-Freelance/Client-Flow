# PRD — ClientFlow MVP

## 1. Vision

ClientFlow es una web app open source para freelancers y agencias pequenas que quieran gestionar clientes, proyectos y comunicacion desde un solo lugar. El objetivo fundamental es:

- El cliente puede estar informado del estado de su proyecto en todo momento.
- Se elimina la dependencia de emails, WhatsApps y herramientas externas para mensajeria.
- El admin puede conectar sus entornos de desarrollo con los proyectos via MCP, evitando subir documentacion a los repositorios.
- Sirve como base de datos de agentes de desarrollo IA, evitando recrearlos continuamente.

## 2. Roles

### Admin
El freelancer o agencia que instala y gestiona ClientFlow. Tiene acceso total: organiza clientes, proyectos, tareas, documentacion, agentes IA y configuracion.

### Cliente
Usuario que pertenece a una Organization. Puede ver sus proyectos, tareas, documentos publicos, chat del proyecto y el asistente IA. No puede ver informacion de otras organizaciones ni documentos privados.

## 3. Modelo organizativo

```
Admin (freelancer que instala ClientFlow)
 └── Organizations (empresas/clientes)
      ├── Members (usuarios employee, ligados a la org con rol)
      └── Projects
           ├── Board Columns (kanban)
           ├── Tasks (con subtareas)
           ├── Documents (markdown private/public)
           ├── Messages (chat por proyecto)
           ├── Agent Assignments
           └── Calendar Events
```

Una Organization agrupa empleados y proyectos. Un cliente puede pertenecer a varias organizaciones. Un proyecto pertenece a una organizacion.

## 4. Funcionalidades MVP

### Fase 1: Foundation (Auth + Roles + Organizations)

- Autenticacion completa: login, registro, recuperacion de password.
- Enum `UserRole`: `admin`, `client`.
- Middleware por rol con redireccion post-login.
- Layouts: admin (sidebar), portal (sidebar), auth (centrado).
- CRUD organizaciones (admin).
- Invitacion de empleados a organizacion (email/token).
- Registro via invitacion que liga usuario a organizacion.
- Dashboard admin: organizaciones y proyectos recientes.
- Dashboard portal (cliente): proyectos de sus organizaciones.

### Fase 2: Clients + Projects

- CRUD proyectos asociados a organizaciones.
- Relacion project_user (participantes del proyecto).
- Vista detalle de proyecto (admin y portal).
- Estados de proyecto: planning, in_progress, on_hold, waiting_client, completed, archived.
- Barra de progreso manual.

### Fase 3: Kanban (vitaminado)

- Columnas por proyecto (board_columns): nombre, orden, color, personalizables.
- Tareas con: parent_id (subtareas), column_id, priority (critical/high/medium/low), type (feature/bug/improvement/task), estimated_hours, actual_hours, due_date, assignee_id.
- Drag and drop entre columnas (Livewire).
- Vista kanban y vista lista por proyecto.
- Crear, editar, mover, eliminar tareas.
- Subtareas (tareas hijas via parent_id).
- Filtros: prioridad, asignado, fecha limite.
- Asignar tareas a miembros del proyecto.

### Fase 4: Documentation

- Documentos markdown en proyecto: title, content (markdown), visibility (private/public).
- Editor markdown con preview (Livewire).
- Docs privadas: solo visibles por admin (accesibles via MCP).
- Docs publicas: visibles por clientes del proyecto.
- Listado y busqueda por proyecto.

### Fase 5: Chat por proyecto

- Mensajes por proyecto (project_messages): project_id, user_id, content, type (text/system/file).
- Interfaz de chat en tiempo real via Livewire polling.
- Mensajes de sistema automaticos (tarea creada, estado cambiado, etc.).
- Notificaciones in-app + email de mensajes nuevos.
- Indicador de mensajes no leidos por proyecto.

### Fase 6: MCP Server (solo lectura)

- MCP protocol sobre HTTP/SSE en Laravel.
- Autenticacion via API tokens.
- Tools: list_projects, get_project, list_tasks, get_task, get_documents, search_documents, get_project_status.
- Acceso a documentacion privada del proyecto.
- Sin escritura en MVP.

### Fase 7: IA para el cliente

- Admin configura provider y API key (OpenAI, Anthropic, etc.) por proyecto o global.
- Chat IA por proyecto para clientes.
- Se inyecta contexto: estado del proyecto, tareas, docs publicas.
- Migraciones: ai_configs, ai_chat_sessions, ai_chat_messages.
- Templates de system prompt por proyecto.

### Fase 8: Calendario

- Eventos: title, description, starts_at, ends_at, type (meeting/deadline/milestone), project_id.
- Vista calendario mensual/semanal.
- Crear eventos ligados a proyectos.
- Invitar asistentes.
- Notificaciones antes de eventos.

### Fase 9: PWA

- manifest.json + service worker.
- Offline: cachear vistas estaticas y datos basicos.
- Push notifications para mensajes y tareas.
- Install prompt.

### Transversal: Agentes IA (templates)

- Biblioteca de templates de agentes IA (name, description, system_prompt, tools json, model, category).
- Asignar templates a proyectos (copia del config).
- Exportar config de agente para uso en IDEs.

### Fase 10: Adjuntos en tareas y mensajes

- Archivos adjuntos en tareas (task_attachments) y mensajes del chat (message_attachments).
- Subida drag & drop o boton en creacion de tarea y en el chat.
- Archivos servidos mediante controlador con autorizacion (nunca desde public/).
- Tipos MIME y tamano maximo configurables por el admin.

### Fase 11: Registro de tiempo

- Entradas de tiempo por tarea (time_entries): descripcion, minutos, tipo (manual/timer).
- Temporizador start/stop en el detalle de la tarea.
- Dashboard de horas por proyecto, miembro y tarea.
- Flag `billed` para marcar entradas como facturables.
- Cliente puede ver resumen de horas en el portal (solo lectura).

### Fase 12: Plantillas de proyecto

- Biblioteca de plantillas reutilizables (project_templates).
- Cada plantilla contiene: columnas predefinidas, tareas tipo y documentos esqueleto.
- Al crear un proyecto, opcion "Desde plantilla" que copia todo.
- Categorias para organizar las plantillas.

### Fase 13: Feed de actividad

- Log cronologico de toda la actividad del proyecto: tareas creadas/completadas, documentos subidos, cambios de estado, mensajes.
- Visible por proyecto tanto en admin como en portal (eventos publicos).
- Implementado como servicio `ActivityLogger` que se llama desde controladores y Livewire.
- Carga infinita o paginada.

### Transversal: Notificaciones

- In-app: badge en sidebar, lista de notificaciones.
- Email: resumen diario, mensajes nuevos, deadline cercano.
- Database notifications de Laravel.

### Transversal: Visto/leido en chat

- Pivot `message_reads` que registra que usuarios han leido cada mensaje.
- Doble check visual en burbujas propias: icono de check solitario (enviado) y doble check (leido).
- Marcar como leidos automaticamente al abrir el chat via polling.

## 5. Lo que NO entra en el MVP

- Multipartes / multiempresa SaaS.
- Facturacion o pagos.
- Integraciones externas (GitHub, GitLab, etc.).
- WebSocket realtime (se usa polling Livewire).
- Redis obligatorio.
- Workers permanentes.
- Videollamadas.
- Firma digital.
- Marca blanca.

## 6. Stack

- PHP 8.3+
- Laravel 13
- Livewire 4
- Blade
- Tailwind CSS 4
- MySQL 8.4
- Docker para desarrollo local
- Sin Redis obligatorio, sin workers permanentes

## 7. Criterios de aceptacion del MVP

El MVP estara listo cuando:

1. Admin puede crear organizaciones e invitar miembros.
2. Admin puede crear proyectos y asignarlos a organizaciones.
3. Admin puede gestionar tareas en un kanban vitaminado.
4. Admin puede crear documentacion privada y publica por proyecto.
5. Cliente puede ver sus proyectos, tareas, documentos publicos.
6. Chat por proyecto funciona entre admin y clientes, con envio de adjuntos e indicador de leido.
7. MCP server permite consultar proyectos, tareas y docs privadas desde un IDE.
8. Cliente puede usar el asistente IA para consultar estado de su proyecto.
9. Calendario muestra eventos y deadlines.
10. PWA permita instalar la app en movil.
11. Admin puede adjuntar archivos a tareas y mensajes del chat.
12. Admin puede registrar tiempo en tareas (temporizador y manual) y ver dashboard de horas.
13. Admin puede crear y reutilizar plantillas de proyecto con columnas, tareas y documentos.
14. Cada proyecto muestra un feed de actividad con todos los eventos cronologicamente.

## 8. Filosofia

ClientFlow existe para reducir ansiedad, mejorar comunicacion y centralizar la gestion de proyectos. El cliente no debe gestionar el proyecto; debe tener tranquilidad. El admin no debe repetir informacion por multiples canales; todo debe estar en un solo lugar.