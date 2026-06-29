# ClientFlow

ClientFlow es una **aplicación web open source** pensada para que **freelancers y pequeñas agencias** gestionen clientes, proyectos y comunicación desde un mismo lugar, sin tener que saltar entre emails, WhatsApp, Trello, Notion y herramientas de IA.

Su objetivo es muy concreto: que tu cliente pueda ver, en menos de diez segundos, en qué punto está su proyecto; y que tú, como admin, tengas todo lo demás (tareas, documentación, chat, IA, calendario, MCP) en una única plataforma instalable y autoalojable.

---

## Tabla de contenidos

1. [Propuesta de valor](#propuesta-de-valor)
2. [Funcionalidades principales](#funcionalidades-principales)
3. [Roles y modelo organizativo](#roles-y-modelo-organizativo)
4. [Stack tecnológico](#stack-tecnológico)
5. [Arquitectura](#arquitectura)
6. [Estructura del repositorio](#estructura-del-repositorio)
7. [Arranque rápido con Docker](#arranque-rápido-con-docker)
8. [Arranque sin Docker](#arranque-sin-docker)
9. [Primer usuario administrador](#primer-usuario-administrador)
10. [Conectar un IDE al servidor MCP](#conectar-un-ide-al-servidor-mcp)
11. [Tests](#tests)
12. [Despliegue en producción](#despliegue-en-producción)
13. [Seguridad y auditoría](#seguridad-y-auditoría)
14. [Documentación adicional](#documentación-adicional)
15. [Hoja de ruta](#hoja-de-ruta)
16. [Contribuir](#contribuir)
17. [Licencia](#licencia)

---

## Propuesta de valor

- **Transparencia para el cliente.** El cliente ve el estado de su proyecto, las tareas abiertas, la documentación, el chat y el calendario, sin tener que pedírtelo.
- **Menos canales sueltos.** Se acabaron los hilos de email, los PDFs por WhatsApp y los "ya te paso el documento". Todo el contexto del proyecto vive dentro de ClientFlow.
- **IA contextual y controlada.** El admin configura qué proveedor de IA (OpenAI, Anthropic, Opencode Zen, LM Studio, Ollama…) responde a las preguntas de cada cliente. La IA recibe como contexto el estado del proyecto, sus tareas y la documentación pública; nunca publica contenido por sí sola.
- **Conexión con tus IDEs vía MCP.** Tu editor (Claude Code, Cursor, Continue, etc.) puede consultar la documentación —incluida la privada— de un proyecto mediante el protocolo MCP, sin tener que subirla al repositorio.
- **Biblioteca de agentes IA reutilizables.** Crea plantillas de agentes (system prompt, herramientas, modelo) y asígnalas a proyectos. Exporta la configuración lista para usarla en tu IDE.
- **Software libre y self-hostable.** Laravel 13 + PHP 8.3. Sin Redis obligatorio, sin workers permanentes, sin WebSockets. Pensado para correr en un hosting compartido o en un VPS modesto.

---

## Funcionalidades principales

### Fundamentos (Fase 1)

- Autenticación completa: **inicio de sesión**, **registro de clientes** y **recuperación de contraseña**.
- Roles diferenciados: `admin` (tú) y `client` (los miembros de las organizaciones).
- Middleware por rol (`admin`, `client`) con redirección automática tras login.
- **Organizaciones** con miembros e invitaciones por correo con token.
- Vista de aceptación de invitación que liga al usuario a la organización.
- Dashboards diferenciados: `/admin/tablero` (organizaciones y proyectos recientes) y `/portal/tablero` (proyectos de tus organizaciones).
- Landing pública para usuarios no autenticados.

### Proyectos (Fases 2 y 2.5)

- **CRUD de proyectos** asociados a una organización.
- Estados: `planning`, `in_progress`, `on_hold`, `waiting_client`, `completed`, `archived`.
- Asignación de miembros del proyecto (relación `project_user`).
- Archivado y restauración de proyectos.
- **Hub de proyecto** rediseñado como panel único: hero sticky, tiles de resumen, previsualización del kanban, documentos recientes, próximo evento, último mensaje del chat y equipo.

### Kanban vitaminado (Fase 3)

- **Columnas configurables** por proyecto: nombre, color, orden. Cuatro columnas por defecto (Por hacer, En curso, En revisión, Hecho).
- **Tareas** con subtareas (`parent_id`), prioridad (`critical`, `high`, `medium`, `low`), tipo (`feature`, `bug`, `improvement`, `task`), estimación, fecha límite, asignado y descripción en markdown.
- **Drag & drop** entre columnas con Livewire.
- **Filtros** por prioridad, asignado y fecha límite.
- **Vista kanban de solo lectura** para clientes en el portal.
- Barra de progreso del proyecto calculada automáticamente a partir de las tareas raíz completadas.

### Documentación (Fase 4)

- **Editor markdown con previsualización** en vivo, implementado con Livewire.
- Visibilidad por documento: `private` (solo admin) o `public` (visible para los clientes del proyecto).
- Listado y **búsqueda** dentro de cada proyecto.
- La documentación privada es accesible también a través del servidor MCP, para que tu IDE la pueda consultar.

### Chat por proyecto (Fase 5)

- Mensajes de texto, de **sistema** (generados al crear tareas, cambiar estados, etc.) y con adjuntos.
- **Polling cada cinco segundos** para refrescar mensajes sin necesidad de WebSockets.
- **Indicador "Visto"** (doble check) en tus propios mensajes, gracias a la tabla `message_reads`.
- **Notificaciones in-app** y **email** por mensaje nuevo, con badge en la sidebar.
- Resumen diario de actividad por correo.

### Servidor MCP (Fase 6) — solo lectura

- Endpoint HTTP + SSE (`/api/mcp/sse`) y mensajes JSON-RPC (`/api/mcp/messages`).
- Autenticación mediante **API tokens** generados con `php artisan mcp:token`.
- Tools expuestas (todas de solo lectura):
  - `list_projects` — lista los proyectos del admin.
  - `get_project` — detalle y estado de un proyecto.
  - `list_tasks` — tareas de un proyecto (con filtros).
  - `get_task` — detalle de una tarea.
  - `get_documents` — documentos del proyecto, **incluidos los privados**.
  - `search_documents` — búsqueda por texto en el contenido.
  - `get_project_status` — resumen ejecutivo del proyecto.
- Limpieza automática de sesiones inactivas cada 15 minutos (auditoría M-07).

### Asistente IA para clientes (Fase 7)

- Configuración por proyecto o global con **API key cifrada en base de datos**.
- Proveedores soportados: **OpenAI**, **Anthropic** y **Opencode Zen** (API OpenAI-compatible), más cualquier endpoint compatible con OpenAI (LM Studio, Ollama, vLLM, proxy corporativo).
- **Inyección de contexto** en el system prompt: estado del proyecto, tareas y documentos públicos.
- **Historial de sesiones** por usuario y proyecto.
- **Rate limiting** configurable (mensajes por hora, sesiones por día).

### Calendario (Fase 8)

- Tipos de evento: `meeting`, `deadline`, `milestone`.
- Vistas **mensual y semanal** con FullCalendar vía Livewire.
- Eventos asociados a proyectos, con asistentes invitados.
- **Notificaciones previas** al evento vía email y notificación in-app.

### PWA (Fase 9)

- `manifest.webmanifest` con iconos en varios tamaños.
- **Service worker** con caché de vistas estáticas y datos básicos.
- **Prompt de instalación** en navegadores compatibles.
- **Push notifications** para mensajes nuevos y tareas asignadas (en navegadores compatibles).
- Endpoint de polling `/api/notifications/unread-count` para la badge de la campana.

### Plantillas de proyecto (Fase 12)

- Biblioteca de **plantillas reutilizables** (`project_templates`) con columnas, tareas predefinidas y documentos esqueleto.
- **Crear proyecto desde plantilla** copia al vuelo toda la estructura.
- Categorías para filtrar la biblioteca.

### Adjuntos (Fase 10)

- **Adjuntos en tareas y mensajes**, con subida drag & drop o por botón.
- Archivos servidos por un **controlador con autorización** (nunca desde `public/`).
- Validación de **MIME y tamaño máximo** configurable.
- Descarga y eliminación con policies de Laravel.

### Registro de tiempo (Fase 11)

- Entradas manuales o por **temporizador** start/stop en el detalle de la tarea.
- Dashboard de horas por **proyecto, miembro y tarea**.
- Flag `billed` para marcar entradas como facturables.
- **Resumen de horas en el portal** del cliente, en modo solo lectura.
- Cache de `total_logged_minutes` en `tasks` para no recalcular en cada consulta.

### Feed de actividad (Fase 13)

- Log cronológico de eventos del proyecto: tareas creadas/completadas, documentos, cambios de estado, mensajes, asignaciones…
- Servicio `ActivityLogger` inyectado en controladores y componentes Livewire.
- **Componente Livewire** con paginación o carga infinita.
- **Vista en admin** (todos los eventos) y **vista en portal** (solo eventos públicos relevantes para el cliente).
- Enlace "Ver actividad" en la sidebar del proyecto.

### Transversal: agentes IA (templates)

- Biblioteca de `agent_templates` con nombre, descripción, system prompt, herramientas (JSON), modelo y categoría.
- Asignación de un template a un proyecto (`project_agents`) con `system_prompt_override`.
- **Exportación a JSON** lista para importar en tu IDE (Claude Code, Cursor, etc.).

### Transversal: notificaciones

- **Notificaciones in-app** con badge en la campana de la sidebar y lista completa.
- **Notificaciones por email**: resumen diario a las 09:00, mensajes nuevos, tareas con deadline cercano, invitaciones a organización, invitaciones a eventos.
- Preferencias configurables por usuario (`notification_preferences`).
- Resumen diario y recordatorios de deadline **programados con el scheduler** de Laravel (cron real necesario en producción).

---

## Roles y modelo organizativo

```
Admin (tú, el freelancer que instala ClientFlow)
 └── Organizations (tus clientes / empresas)
      ├── Members (usuarios con rol employee)
      └── Projects
           ├── Board Columns (kanban configurable)
           ├── Tasks (con subtareas)
           ├── Documents (markdown private/public)
           ├── Messages (chat por proyecto)
           ├── Calendar Events (reuniones, milestones, deadlines)
           ├── Agent Assignments (templates de IA)
           ├── Templates (columnas, tareas y documentos esqueleto)
           └── Attachments (archivos en tareas y mensajes)
```

- Un **cliente** puede pertenecer a **varias organizaciones**.
- Un **proyecto** pertenece a **una sola organización**.
- Los **documentos privados** solo los ve el admin.
- Los **documentos públicos** los ven los miembros del proyecto en el portal.
- Los **adjuntos** solo los descargan los miembros del proyecto.
- El **servidor MCP** solo lee; nunca escribe.
- Toda la autorización se valida con **Policies de Laravel**.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Blade + Livewire 4 + Tailwind CSS 4 |
| Build | Vite 8 |
| Base de datos | MySQL 8.4 |
| Cache / Queue | Driver `database` (sync en MVP) |
| Storage | Local (`storage/app/clientflow/`) |
| Autenticación web | Sesiones Laravel + cookies |
| Autenticación API | Laravel Sanctum (API tokens) |
| MCP | HTTP + SSE + JSON-RPC |
| Tests | PHPUnit 12, Playwright (E2E) |
| Docker local | PHP-FPM 8.4, Nginx 1.27, MySQL 8.4, Node 22 |
| Despliegue | Docker Compose, Nginx, MySQL — compatible con hosting compartido |

Sin Redis obligatorio, sin workers permanentes, sin WebSockets.

---

## Arquitectura

El monolito Laravel expone:

- **Rutas web tradicionales** (Blade + Livewire) bajo `/admin/*` y `/portal/*`.
- **API interna** para polling (`/api/notifications/unread-count`).
- **API MCP** bajo `/api/mcp/*` para integraciones con IDEs.

Capas de la aplicación:

```
app/
├── Actions/            # Acciones dedicadas (patrón command)
├── DTOs/               # Data Transfer Objects
├── Enums/              # Enums del dominio (UserRole, ProjectStatus, TaskPriority, …)
├── Http/
│   ├── Controllers/    # Admin/, Portal/, Auth/, Api/
│   ├── Middleware/     # EnsureUserIsAdmin, EnsureUserIsClient, EnsureMcpAccess
│   └── Requests/       # Form requests con validación
├── Livewire/           # Componentes Admin/, Portal/ y Shared/
├── Models/             # 28 modelos Eloquent
├── Policies/           # 18 policies de autorización
├── Services/
│   ├── Activity/       # ActivityLogger y feed
│   ├── Ai/             # AiService, AiRateLimiter, ProjectContextBuilder
│   ├── Attachments/    # AttachmentService
│   ├── Calendar/       # CalendarQueryService
│   ├── Mcp/            # Servidor MCP, registro y tools
│   ├── Notifications/  # NotificationDispatcher
│   ├── Project/        # ProjectSummaryService
│   ├── ProjectTemplate/# ProjectTemplateService
│   └── TimeTracking/   # TimeTrackingService
├── Notifications/      # 6 notificaciones (mensajes, tareas, digest, eventos…)
├── Listeners/          # Reacción a eventos
└── Console/Commands/   # create-admin, mcp:token, digest, due-soon…
```

Todas las políticas de seguridad se verifican con **Policies de Laravel**, y los archivos privados nunca se sirven desde `public/`.

---

## Estructura del repositorio

```txt
.
├── app/                  # Aplicación Laravel (PHP, Blade, Livewire, Tailwind)
├── docs/                 # Documentación de producto, arquitectura y modelo de datos
│   ├── PRD.md
│   ├── ARCHITECTURE.md
│   ├── DATA_MODEL.md
│   ├── DESIGN.md
│   ├── USER_FLOWS.md
│   ├── IMPLEMENTATION.md
│   ├── CASE_STUDY_CLIENTFLOW.md
│   ├── CASE_STUDY_PAGE.md
│   └── tasks/            # Fichas técnicas de cada fase
├── .agents/              # Skills y conocimiento de los agentes IA de opencode
├── .opencode/            # Configuración y definiciones de agentes opencode
├── TODOs.md              # Lista de tareas del proyecto y auditoría de seguridad
└── README.md             # Este documento
```

---

## Arranque rápido con Docker

### 1. Prepara el entorno

```bash
cd app
cp .env.example .env
```

Edita `.env` si quieres cambiar el `APP_URL`, las credenciales de la base de datos o el `LOG_LEVEL` (por defecto `warning`, lo mínimo seguro para producción — auditoría M-04).

### 2. Arranca los servicios

```bash
docker compose up -d --build
```

Servicios levantados:

| Servicio | Puerto | Descripción |
|---|---|---|
| `nginx` | `http://localhost:8080` | Servidor web (PHP-FPM detrás) |
| `node` | `http://localhost:5173` | Vite en modo desarrollo |
| `mysql` | _no expuesto al host_ | Solo accesible desde la red Docker interna (auditoría M-03) |

> **Cambio importante:** MySQL **ya no se publica en `127.0.0.1:3307`** por motivos de seguridad. Si necesitas un cliente externo (TablePlus, Sequel Ace, CLI), usa:
> ```bash
> # 1) Consola mysql dentro del contenedor
> docker compose exec mysql mysql -uclientflow -pclientflow clientflow
>
> # 2) Túnel al puerto local solo cuando lo necesites
> docker compose exec mysql bash -c "apt-get update && apt-get install -y socat && socat TCP-LISTEN:3307,fork,reuseaddr TCP:mysql:3306"
> ```

### 3. Instala dependencias y migra

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 4. Crea tu usuario administrador

```bash
docker compose exec app php artisan clientflow:create-admin
```

El comando es interactivo: te pedirá nombre, email y contraseña. Si el email ya existe, promueve al usuario existente a `admin`.

### 5. Abre la app

Visita `http://localhost:8080`. Inicia sesión y empieza a crear tu primera organización, invitar a tu cliente y abrir un proyecto.

---

## Arranque sin Docker

```bash
cd app
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build            # o `npm run dev` en otra terminal durante el desarrollo
php artisan serve
```

Requisitos:

- PHP 8.3 o superior con extensiones `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `bcmath`, `fileinfo`.
- MySQL 8.0 o superior.
- Node 20 o superior y npm 10 o superior.

---

## Primer usuario administrador

El registro público en `/registro` **solo crea cuentas con rol `client`**, por diseño. Para crear el primer admin (o promover a uno existente) usa el comando artesanal:

```bash
php artisan clientflow:create-admin
```

Luego podrás invitar a tus clientes a sus respectivas organizaciones desde el panel admin, en **Organizaciones → Miembros → Invitar**.

---

## Conectar un IDE al servidor MCP

1. Genera un API token para tu usuario admin:
   ```bash
   php artisan mcp:token
   ```
   Se mostrará el token en pantalla; guárdalo, no se vuelve a mostrar.

2. En la configuración MCP de tu IDE (Claude Code, Cursor, Continue, etc.):
   ```json
   {
     "mcpServers": {
       "clientflow": {
         "type": "sse",
         "url": "http://localhost:8080/api/mcp/sse",
         "headers": {
           "Authorization": "Bearer <TU_TOKEN>",
           "Accept": "text/event-stream"
         }
       }
     }
   }
   ```

3. Tu IDE podrá usar las tools de solo lectura: `list_projects`, `get_project`, `list_tasks`, `get_task`, `get_documents` (incluye privados), `search_documents`, `get_project_status`.

Más detalles en `docs/tasks/fase-6-mcp-server.md`.

---

## Tests

La suite combina **PHPUnit** (unit + feature) y **Playwright** (E2E).

### PHPUnit

```bash
# Sin Docker
cd app
php vendor/bin/phpunit

# Con Docker
docker compose exec app php vendor/bin/phpunit

# Solo un módulo concreto
docker compose exec app php artisan test --filter=Project
```

### Playwright (E2E)

Los tests E2E viven en `app/tests/e2e/`. Desde Docker:

```bash
docker compose run --rm playwright npx playwright test
```

Dentro de la red Docker, la aplicación se accede como `http://nginx:80`. La configuración ya está preparada en `playwright.config.ts`.

Para grabar nuevas interacciones en local (fuera de Docker):

```bash
npx playwright codegen http://localhost:8080
```

---

## Despliegue en producción

ClientFlow está pensado para correr de forma estable en un VPS modesto o en un hosting compartido:

- **Docker Compose** (recomendado): `app/docker-compose.prod.yml` levanta PHP-FPM, Nginx y MySQL con `restart: always`. Construye la imagen de producción con `docker/php/Dockerfile.prod`.
- **Sin Docker**: la app funciona en cualquier hosting con PHP 8.3+, MySQL 8 y un cron real. Apunta el document root a `app/public`.

### Tareas programadas (cron)

En producción añade una única entrada cron para que Laravel ejecute el scheduler:

```cron
* * * * * cd /ruta/a/app && php artisan schedule:run >> /dev/null 2>&1
```

Tareas registradas:

- `notifications-daily-digest` — resumen diario a las 09:00.
- `notifications-task-due-soon` — recordatorios de deadline a las 08:00.
- `mcp-sessions-cleanup` — limpieza de sesiones MCP inactivas cada 15 minutos.

### Build de assets

```bash
npm run build
```

Los assets se generan en `public/build/`.

### Seguridad en Nginx

El `docker/nginx/default.conf` incluye cabeceras de seguridad (X-Frame-Options, X-Content-Type-Options, HSTS, Referrer-Policy, Permissions-Policy y CSP), compresión gzip y caché para los assets estáticos.

---

## Seguridad y auditoría

ClientFlow se ha sometido a una **auditoría interna** cuyo informe completo se encuentra en `.opencode/agents/security-auditor.md` y los hallazgos se listan en `TODOs.md`. Estado actual de los hallazgos:

### Alta prioridad

- **H-01** — Validar pertenencia en sesiones MCP (pendiente).
- **H-02** — Rate limiting en `POST /iniciar-sesion` (5/min) y `POST /registro` (3/hora) — **aplicado**.
- **H-03** — Expiración de tokens Sanctum a 30 días — **aplicado**.

### Media prioridad

- **M-01** — Cabeceras de seguridad HTTP en Nginx — **aplicado**.
- **M-02** — Usuario no root en el contenedor PHP — **aplicado**.
- **M-03** — MySQL no expuesto al host (`expose` en lugar de `ports`) — **aplicado**.
- **M-04** — `LOG_LEVEL=warning` en `.env.example` por defecto — **aplicado**.
- **M-05** — Cabecera `Referrer-Policy: no-referrer` en la vista de invitación — pendiente.
- **M-07** — Cleanup automático de sesiones MCP en el scheduler — **aplicado**.

### Baja prioridad

- **L-02** — `APP_DEBUG=false` en `.env.example` por defecto — **aplicado**.
- **L-04** — `password` fuera de `$fillable` en `User` — **aplicado**.
- Sanitizar excepción en log MCP — pendiente.

Refuerzos transversales ya implementados:

- **Policies de Laravel** en los 18 recursos del dominio.
- **Rate limiting** en login y registro.
- **CSRF** en todas las rutas web (las rutas `/api/mcp/*` usan bearer tokens y viven en `web.php` con la cookie de sesión para servir el SSE).
- **Archivos privados** servidos por controladores con autorización, nunca desde `public/`.
- **API keys de IA** cifradas en la base de datos con los mecanismos de Laravel.
- **Cabeceras de seguridad** en Nginx (X-Frame-Options, HSTS, CSP, Permissions-Policy…).

Si encuentras una vulnerabilidad, por favor abre un issue etiquetado como `security` o envía un correo a la dirección de seguridad indicada en el repositorio.

---

## Documentación adicional

Toda la documentación de producto, arquitectura, modelo de datos y diseño vive en `docs/`:

```txt
docs/
├── PRD.md                       # Product Requirements Document
├── ARCHITECTURE.md              # Arquitectura técnica
├── DATA_MODEL.md                # Modelo de datos
├── DESIGN.md                    # Design system y guías visuales
├── USER_FLOWS.md                # Flujos de usuario y pantallas
├── IMPLEMENTATION.md            # Convenciones y patrones de código
├── CASE_STUDY_CLIENTFLOW.md     # Caso de estudio del proyecto
├── CASE_STUDY_PAGE.md           # Página pública del caso de estudio
└── tasks/                       # Fichas técnicas por fase
    ├── fase-1-foundation.md
    ├── fase-2-projects.md
    ├── fase-2.5-project-hub.md
    ├── fase-3-kanban.md
    ├── fase-3.5-ajustes.md
    ├── fase-4-documentation.md
    ├── fase-5-chat.md
    ├── fase-5-doble-check.md
    ├── fase-6-mcp-server.md
    ├── fase-7-ia-cliente.md
    ├── fase-8-calendario.md
    ├── fase-9-pwa.md
    ├── fase-10-adjuntos.md
    ├── fase-11-registro-tiempo.md
    ├── fase-12-plantillas.md
    ├── fase-13-feed-actividad.md
    ├── fase-transversal-agentes-ia.md
    ├── fase-transversal-notificaciones.md
    ├── fix-tests-24-errores.md
    └── landing-page.md
```

El sistema también incluye **agentes de opencode** especializados (`.opencode/agents/`) que conocen el proyecto y aceleran el desarrollo:

- `product-architect` — convierte requisitos en decisiones técnicas.
- `laravel-backend` — migraciones, modelos, policies, servicios, controladores.
- `livewire-frontend` — pantallas Blade/Livewire y estilos Tailwind.
- `mcp-server` — herramientas del endpoint MCP.
- `devops-docker` — entorno local Docker.
- `qa-reviewer` — auditoría de permisos, tests y regresiones.
- `security-auditor` — auditoría de seguridad.
- `hyperframes-creator` — vídeos promocionales HyperFrames.

---

## Hoja de ruta

El estado detallado de cada fase, con su checklist, está en `TODOs.md`. Resumen del MVP actual:

| Fase | Estado | Funcionalidad |
|---|---|---|
| 1. Foundation | ✅ Completa | Auth, roles, organizaciones, invitaciones |
| 2. Projects | ✅ Completa | CRUD, estados, archivado, miembros |
| 2.5. Hub de proyecto | ✅ Completa | Hero, tiles, previews, equipo |
| 3. Kanban vitaminado | ✅ Completa | Columnas, tareas, drag & drop, filtros, subtareas |
| 4. Documentación | ✅ Completa | Markdown private/public, búsqueda |
| 5. Chat por proyecto | ✅ Completa | Polling, doble check, sistema, adjuntos |
| 6. Servidor MCP | ✅ Completa | Solo lectura, SSE, 7 tools |
| 7. IA para el cliente | ✅ Completa | Multi-provider, contexto, rate limit |
| 8. Calendario | ✅ Completa | Mensual/semanal, eventos, asistentes |
| 9. PWA | ✅ Completa | Manifest, service worker, push, install |
| 10. Adjuntos | ✅ Completa | Tareas y mensajes, MIME, autorización |
| 11. Registro de tiempo | ✅ Completa | Timer, manual, dashboard, facturable |
| 12. Plantillas de proyecto | ✅ Completa | Columnas, tareas, documentos esqueleto |
| 13. Feed de actividad | ✅ Completa | Livewire, paginación, portal filtrado |
| Transversal: agentes IA | ✅ Completa | Biblioteca, asignación, export JSON |
| Transversal: notificaciones | ✅ Completa | In-app, email, digest, preferencias |
| Pendiente vista lista de tareas | ⏳ | Alternativa al kanban |
| Auditoría de seguridad | ⏳ 3 hallazgos | H-01, M-05, log MCP |

---

## Contribuir

¡Las contribuciones son bienvenidas! Pasos recomendados:

1. Lee `docs/IMPLEMENTATION.md` para conocer las convenciones de código y el estilo PHPDoc.
2. Revisa `docs/ARCHITECTURE.md` y `docs/DATA_MODEL.md` antes de tocar el modelo.
3. Crea una rama a partir de `main`: `git checkout -b feat/mi-mejora`.
4. Añade tests para cualquier cambio de comportamiento.
5. Asegúrate de que la suite pasa: `php vendor/bin/phpunit` y, si procede, los E2E con Playwright.
6. Abre un Pull Request describiendo el problema, la solución y los pasos para probarlo.

Si tu cambio toca permisos, autorización o expone nuevos endpoints, etiqueta a `qa-reviewer` y a `security-auditor` en la revisión.

---

## Licencia

MIT. Puedes usar, modificar y distribuir ClientFlow libremente, incluso con fines comerciales. Consulta el archivo `LICENSE` para el texto completo.
