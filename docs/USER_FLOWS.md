# USER_FLOWS.md — Flujos de usuario de ClientFlow

## Roles y zonas

| Rol | Zona | Prefijo rutas | Sidebar |
|---|---|---|---|
| Admin | Panel administracion | `/admin/*` | Admin sidebar |
| Client | Portal cliente | `/portal/*` | Portal sidebar |
| No autenticado | Auth | `/login`, `/register`, `/invitation/*` | Sin sidebar |

Redireccion post-login:
- Admin → `/admin/dashboard`
- Client → `/portal/dashboard`

---

## Auth (ambos roles)

### Login

**Ruta**: `GET /login` → vista, `POST /login` → auth

1. Usuario introduce email y password.
2. Si credenciales validas → redirige segun rol.
3. Si credenciales invalidas → mensaje de error, mantiene email.

### Registro

**Ruta**: `GET /register` → vista, `POST /register` → crea usuario

1. Solo accesible si el admin lo habilita (o via invitacion).
2. Campos: nombre, email, password, confirmacion.
3. Crea usuario con rol `client`.
4. Redirige a `/portal/dashboard`.

### Recuperar password

**Ruta**: `GET /password/reset` → vista, `POST /password/email` → envia link, `GET /password/reset/{token}` → vista nueva password, `POST /password/reset` → cambia password

1. Usuario introduce email.
2. Se envia email con link de reset.
3. Usuario introduce nueva password.
4. Redirige a login.

### Aceptar invitacion

**Ruta**: `GET /invitation/{token}` → vista, `POST /invitation/{token}` → acepta

1. Admin invita a un email a una organizacion.
2. El invitado recibe email con link `/invitation/{token}`.
3. Si el email ya tiene cuenta → se le anade a la organizacion y se loguea.
4. Si el email no tiene cuenta → se muestra formulario de registro (nombre, password).
5. Se crea usuario con rol `client`, se liga a la organizacion.
6. Redirige a `/portal/dashboard`.

### Logout

**Ruta**: `POST /logout`

1. Cierra sesion.
2. Redirige a `/`.

---

## Admin

### Dashboard

**Ruta**: `GET /admin/dashboard`

Muestra:
- Cards: total organizaciones, proyectos activos, tareas pendientes, mensajes sin leer.
- Lista de proyectos recientes (ultimos 5) con estado y progreso.
- Lista de tareas urgentes (critical/high priority con due date cercano).
- Lista de mensajes sin leer por proyecto.

### Organizaciones

#### Listado

**Ruta**: `GET /admin/organizations`

Muestra:
- Buscador por nombre.
- Tabla: nombre, slug, miembros (count), proyectos (count), estado, acciones.
- Boton "Nueva organizacion".
- Filtro por estado (active/inactive).

#### Crear

**Ruta**: `GET /admin/organizations/create` → vista, `POST /admin/organizations` → crea

Campos:
- Nombre (required).
- Descripcion (optional).
- Logo (optional, upload).

Al crear, el admin se anade automaticamente como `owner`.

#### Detalle

**Ruta**: `GET /admin/organizations/{organization}`

Tabs:
1. **General**: nombre, descripcion, logo, estado, editar.
2. **Miembros**: tabla de miembros (nombre, email, rol, acciones). Boton "Invitar miembro".
3. **Proyectos**: lista de proyectos de la organizacion (link a cada uno).

#### Editar

**Ruta**: `GET /admin/organizations/{organization}/edit` → vista, `PUT /admin/organizations/{organization}` → actualiza

Campos editables: nombre, descripcion, logo, estado.

#### Invitar miembro

**Ruta**: `GET /admin/organizations/{organization}/invite` → vista, `POST /admin/organizations/{organization}/members` → envia invitacion

Campos:
- Email (required).
- Rol: owner/member (default: member).

Flujo: se crea invitation token, se envia email con link `/invitation/{token}`.

### Proyectos

#### Listado

**Ruta**: `GET /admin/projects`

Muestra:
- Buscador por nombre.
- Filtros: organizacion, estado.
- Tabla/cards: nombre, organizacion, estado, progreso, fecha inicio, acciones.
- Boton "Nuevo proyecto".

#### Crear

**Ruta**: `GET /admin/projects/create` → vista, `POST /admin/projects` → crea

Campos:
- Nombre (required).
- Organizacion (select, required).
- Descripcion (optional, textarea).
- Estado (default: planning).
- Fecha inicio (optional).
- Fecha estimada fin (optional).
- Visibilidad para clientes (checkbox, default: true).
- Cover image (optional, upload).

Al crear, se generan las columnas default del board: To Do, In Progress, Review, Done.

#### Detalle

**Ruta**: `GET /admin/projects/{project}`

Tabs:
1. **Kanban**: tablero de tareas con drag & drop.
2. **Lista**: vista de tareas en tabla con filtros.
3. **Documentos**: listado de documentos (private y public).
4. **Chat**: mensajes del proyecto.
5. **Calendario**: eventos del proyecto.
6. **Configuracion**: editar proyecto, gestionar miembros, agentes IA.

Header del proyecto: nombre, organizacion, badge de estado, barra de progreso.

#### Editar

**Ruta**: `GET /admin/projects/{project}/edit` → vista, `PUT /admin/projects/{project}` → actualiza

Mismos campos que crear + archivar proyecto.

### Kanban

**Ruta**: `GET /admin/projects/{project}/board`

Muestra:
- Filtros: prioridad, asignado, tipo.
- Columnas configurables (To Do, In Progress, Review, Done por default).
- Cards de tareas con drag & drop entre columnas.
- Boton "+" en cada columna para crear tarea.
- Boton de configurar columnas (nombre, color, orden).

#### Crear tarea

**Modal/Vista**: crear tarea dentro del proyecto

Campos:
- Titulo (required).
- Descripcion (optional, markdown).
- Columna (select, default: primera).
- Prioridad (select: critical, high, medium, low).
- Tipo (select: feature, bug, improvement, task).
- Horas estimadas (optional, decimal).
- Fecha limite (optional, date).
- Asignado (select miembros del proyecto).
- Tarea padre (optional, select subtarea de).

#### Editar tarea

**Modal/Vista**: editar tarea existente

Mismos campos que crear + horas reales (actual_hours).

Se puede reabrir una tarea completada.

### Documentos

#### Listado

**Ruta**: `GET /admin/projects/{project}/documents`

Muestra:
- Filtros: visibilidad (private/public), busqueda por titulo.
- Lista: titulo, visibilidad badge, fecha, acciones.
- Boton "Nuevo documento".
- Seccion "Privados" (solo admin) y "Publicos" (admin y clientes).

#### Crear documento

**Ruta**: `GET /admin/projects/{project}/documents/create` → vista, `POST /admin/projects/{project}/documents` → crea

Campos:
- Titulo (required).
- Contenido (markdown editor con preview).
- Visibilidad: private/public (radio/buttons, default: private).

#### Editar documento

**Ruta**: `GET /admin/projects/{project}/documents/{document}/edit` → vista, `PUT /admin/projects/{project}/documents/{document}` → actualiza

Mismos campos que crear.

### Chat

**Ruta**: `GET /admin/projects/{project}/chat`

Muestra:
- Lista de mensajes (scroll, loading infinito).
- Input de mensaje en la parte inferior, con boton de adjuntar archivo.
- Mensajes propios alineados a la derecha (azul), del cliente a la izquierda (blanco).
- Mensajes de sistema centrados (fondo gris).
- Indicador de "escribiendo..." del otro lado (polling).
- Doble check en burbujas propias: un check al enviar, dos checks cuando el destinatario lo ha leido.
- Mensajes con adjuntos: icono + nombre del archivo + tamano, click para descargar.
- Auto-scroll al BOTTOM on load y on new message.

#### Enviar mensaje

**Ruta**: `POST /admin/projects/{project}/messages`

Campos:
- Contenido (required, textarea con enviar en Enter).
- Adjunto (optional, file upload con drag & drop).

Se genera mensaje de sistema automatico si es la primera vez que el admin escribe en el chat del proyecto.

#### Visto/leido

Los mensajes se marcan como leidos automaticamente via polling cuando el destinatario tiene el chat abierto. La tabla `message_reads` registra que usuario leyo cada mensaje y cuando. En la UI:
- Check solitario (enviado): el mensaje se entrego al servidor.
- Doble check (leido): al menos un miembro del proyecto distinto del autor ha leido el mensaje.

### Calendario

**Ruta**: `GET /admin/projects/{project}/calendar`

Muestra:
- Vista mensual por default, switch a semanal.
- Dias con evento marcados con dot de color.
- Click en dia muestra eventos del dia.
- Boton "Nuevo evento".
- Se muestran deadlines de tareas automaticamente.

#### Crear evento

**Modal**: crear evento

Campos:
- Titulo (required).
- Descripcion (optional).
- Tipo: meeting, deadline, milestone.
- Fecha inicio (required, datetime).
- Fecha fin (optional, datetime).
- Proyecto (pre-seleccionado si se crea desde proyecto).

### Agentes IA (templates)

#### Listado

**Ruta**: `GET /admin/agent-templates`

Muestra:
- Cards de templates disponibles: nombre, categoria, descripcion breve.
- Boton "Nuevo template".
- Filtros por categoria.

#### Crear template

**Ruta**: `GET /admin/agent-templates/create` → vista, `POST /admin/agent-templates` → crea

Campos:
- Nombre (required): e.g. "Arquitecto Backend".
- Descripcion (optional).
- Categoria (select): development, architecture, design, qa, etc.
- System prompt (required, textarea largo): instrucciones base del agente.
- Herramientas (optional, JSON): lista de herramientas disponibles.
- Modelo (optional): modelo preferido para este agente.

#### Asignar a proyecto

Desde la configuracion del proyecto (tab agentes), boton "Agregar agente":
- Seleccionar template de la biblioteca.
- Opcionalmente editar el system prompt para este proyecto.
- Se crea una copia del config en `project_agents`.

#### Exportar config

**Ruta**: `GET /admin/agent-templates/{template}/export`

Devuelve JSON con: nombre, system_prompt, tools, model. Util para copiar a archivos de configuracion de IDEs.

### Configuracion IA

**Ruta**: `GET /admin/settings/ai`

Campos:
- Provider: OpenAI / Anthropic (select).
- API Key (password input, encrypted en BD).
- Modelo por defecto (select).
- Test de conexion (boton que envia un prompt de prueba).

Se puede sobrescribir por proyecto en la configuracion del proyecto.

### Registro de tiempo

**Ruta**: `GET /admin/projects/{project}/time`

Muestra:
- Dashboard con total de horas registradas por proyecto, desglosado por miembro y por tarea.
- Lista de entradas de tiempo recientes (usuario, tarea, descripcion, minutos, facturado).
- Boton "Nueva entrada manual".

#### Crear entrada manual

**Modal**: desde la vista de tiempo o desde el detalle de tarea.

Campos:
- Tarea (select, required).
- Descripcion (optional).
- Minutos (required, integer).
- Facturable (checkbox, default: false).

#### Temporizador

Desde el detalle de la tarea:
- Boton "Iniciar timer": crea una entrada de tipo `timer` con `started_at`.
- Boton "Detener timer": calcula los minutos transcurridos y cierra la entrada.
- Se muestra un contador en vivo mientras el timer esta activo.

### Plantillas de proyecto

**Ruta**: `GET /admin/project-templates`

Muestra:
- Grid de plantillas disponibles: nombre, categoria, descripcion.
- Boton "Nueva plantilla".
- Filtro por categoria.

#### Crear plantilla

**Ruta**: `GET /admin/project-templates/create` → vista, `POST /admin/project-templates` → crea

Campos:
- Nombre (required).
- Descripcion (optional).
- Categoria (optional, text): e.g. "web", "mobile", "design".
- Estado por defecto (planning/in_progress).

Despues de crear, se configuran:
- **Columnas**: anadir/editar/eliminar columnas con nombre, color y orden.
- **Tareas**: anadir tareas predefinidas asignadas a una columna (por slug).
- **Documentos**: anadir documentos esqueleto con titulo, contenido markdown y visibilidad.

#### Crear proyecto desde plantilla

En la pagina de crear proyecto, un selector de plantilla:
1. Seleccionar plantilla del dropdown.
2. Los campos se auto-rellenan (nombre, descripcion).
3. Al guardar, se crea el proyecto con las columnas, tareas y documentos de la plantilla.

### Feed de actividad

**Ruta**: `GET /admin/projects/{project}/activity`

Muestra:
- Timeline cronologico descendente con todas las acciones del proyecto.
- Cada entrada muestra: icono por tipo, descripcion legible ("Juan creo la tarea Login"), usuario, hace cuanto tiempo.
- Tipos de evento: task_created, task_completed, task_moved, task_reopened, document_created, document_updated, status_changed, project_created, message_sent.
- Los eventos del admin son visibles para el cliente solo si son de tipo publico (no cambios internos de configuracion).
- Carga infinita al scrollear hacia arriba.

### Chat

**Ruta**: `GET /portal/projects/{project}/chat`

Igual que admin. El cliente puede enviar mensajes de texto. Los mensajes de sistema son informativos.

---

## Portal (Cliente)

### Dashboard

**Ruta**: `GET /portal/dashboard`

Muestra:
- Cards: organizaciones donde es miembro, proyectos activos, tareas asignadas pendientes, mensajes sin leer.
- Lista de proyectos recientes con estado y progreso.
- Lista de tareas asignadas con prioridad y due date.
- Lista de mensajes sin leer.

### Organizacion

**Ruta**: `GET /portal/organizations/{organization}`

Muestra:
- Nombre y descripcion de la organizacion.
- Lista de proyectos de la organizacion (cards con nombre, estado, progreso).
- Links a cada proyecto.

### Proyecto

**Ruta**: `GET /portal/projects/{project}`

Igual que admin pero sin configuracion, sin documentos privados, sin gestion de miembros.

Tabs:
1. **Resumen**: estado, progreso, tareas asignadas, proximos eventos.
2. **Kanban**: vista de solo lectura (no puede mover tareas, pero puede verlas y filtrarlas).
3. **Documentos**: solo documentos publicos.
4. **Chat**: mismos mensajes, puede escribir.
5. **IA**: chat con el asistente IA del proyecto.
6. **Calendario**: eventos del proyecto.

### Kanban (solo lectura)

**Ruta**: `GET /portal/projects/{project}/board`

El cliente ve el mismo kanban que el admin pero:
- No puede crear, editar ni mover tareas.
- No puede ver subtareas privadas.
- Puede filtrar y buscar.
- Puede hacer click en una tarea para ver el detalle.

### Documentos (solo publicos)

**Ruta**: `GET /portal/projects/{project}/documents`

Solo muestra documentos con `visibility = public`. No hay seccion de privados.

### Chat

**Ruta**: `GET /portal/projects/{project}/chat`

Igual que admin. El cliente puede enviar mensajes de texto y adjuntar archivos. Los mensajes de sistema son informativos. El doble check de leido tambien aplica (el cliente ve cuando el admin ha leido sus mensajes).

### Adjuntos

**Descarga**: los archivos adjuntos se sirven mediante controlador con autorizacion en la ruta `/admin/projects/{project}/attachments/{attachment}` o `/portal/projects/{project}/attachments/{attachment}`. Solo miembros del proyecto pueden descargar adjuntos.

### IA Asistente

**Ruta**: `GET /portal/projects/{project}/ai`

Muestra:
- Interfaz de chat tipo ChatGPT.
- El contexto del proyecto (estado, tareas, documentos publicos) se inyecta en el system prompt.
- El cliente pregunta sobre el estado de su proyecto, dudas, propuestas.
- Se crean sesiones de chat (se puede continuar una conversacion o iniciar nueva).
- Historial de sesiones en la sidebar izquierda.

### Calendario

**Ruta**: `GET /portal/calendar`

Vista consolidada de todos los eventos del cliente (de todos sus proyectos). Igual UI que admin pero sin boton crear.

### Registro de tiempo

**Ruta**: `GET /portal/projects/{project}/time`

Vista de solo lectura con el resumen de horas del proyecto: total horas, horas por miembro, horas facturadas. El cliente no puede crear ni modificar entradas.

### Feed de actividad

**Ruta**: `GET /portal/projects/{project}/activity`

Misma vista timeline que admin pero filtrando solo eventos publicos: tareas creadas/completadas, documentos publicados, cambios de estado. Oculta eventos internos como cambios de configuracion.

---

## MCP Server (Admin via IDE)

### Conexion

**Ruta**: `GET /api/mcp/sse` (SSE), `POST /api/mcp/messages` (JSON-RPC)

Autenticacion via API token en header: `Authorization: Bearer {token}`.

El admin genera un API token desde su perfil: `GET /admin/settings/api-tokens`.

### Tools disponibles

| Tool | Parametros | Retorna |
|---|---|---|
| `list_projects` | `status` (optional) | Lista de proyectos con estado y progreso |
| `get_project` | `project_id` | Detalle completo: estado, miembros, progreso |
| `list_tasks` | `project_id`, `status`, `priority`, `assignee_id` (optional) | Lista de tareas filtradas |
| `get_task` | `task_id` | Detalle de tarea con subtareas |
| `get_documents` | `project_id`, `visibility` (optional, default: all) | Lista de documentos |
| `search_documents` | `project_id`, `query` | Documentos que contienen el query |
| `get_project_status` | `project_id` | Resumen: estado, progreso, tareas por columna, proximos deadlines |

Todas las tools son de solo lectura. Ninguna puede crear, modificar o eliminar datos.

---

## Notificaciones (transversal)

### In-app

Badge en sidebar con count de no leidas. Click abre panel con lista:

- Nueva tarea asignada.
- Tarea con deadline cercano (< 48h).
- Nuevo mensaje en chat del proyecto.
- Invitacion a organizacion.
- Nuevo evento en calendario.

### Email

- Resumen diario (si hay actividad).
- Nuevo mensaje en chat (si el usuario no ha visitado en 1h).
- Tarea con deadline cercano.
- Invitacion a organizacion.

---

## Errores comunes

### 403 Forbidden

- Cliente intenta acceder a proyecto de otra organizacion.
- Cliente intenta acceder a documento privado.
- Token de invitacion expirado.

### 404 Not Found

- Proyecto, organizacion, tarea no encontrada.
- Documento no existe.

### 419 CSRF Token Mismatch

- Sesion expirada. Redirigir a login.

### 422 Validation Error

- Formulario con campos invalidos. Mostrar errores inline.