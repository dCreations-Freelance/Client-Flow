# Case Study — ClientFlow

> Análisis del proyecto ClientFlow generado aplicando el prompt de la sección 4 del documento `CASE_STUDY_PAGE.md`. Pensado para alimentar la página de caso de estudio con la estructura de la sección 2.

---

## 1. Visión general

**Qué es.** ClientFlow es una web app open source para freelancers y agencias pequeñas que centraliza la gestión de clientes, proyectos, comunicación, documentación y agentes de IA en un único producto. Reune en un mismo lugar lo que normalmente se reparte entre un CRM ligero, un Trello, un Drive, un chat y un asistente conversacional.

**Para quién es.** Dos perfiles diferenciados con espacios de uso independientes:

- **Admin / freelance / agencia**: instala la app, da de alta organizaciones, invita a clientes, crea proyectos, gestiona tareas, redacta documentación, conecta su IDE vía MCP.
- **Cliente final**: pertenece a una o varias organizaciones, consulta el estado de sus proyectos, lee documentación pública, chatee con el admin y con la IA del proyecto.

**Qué problema resuelve.** La gestión de proyectos entre freelance y cliente vive dispersa en email, WhatsApp, Notion, Trello, Drive y distintas herramientas de IA. Eso genera tres problemas recurrentes:

1. El cliente nunca sabe en qué estado está su proyecto y pregunta de forma repetida.
2. El admin repite la misma información en múltiples canales y pierde contexto.
3. La documentación sensible (decisiones técnicas, contexto para IAs) termina duplicada en repositorios, Notion o archivos sueltos, sin control de acceso.

**Propuesta de valor.** "Un solo lugar para todo el proyecto". Para el cliente es tranquilidad y visibilidad; para el admin es menos trabajo repetitivo y un repositorio estructurado conectado con su IDE y con IA.

---

## 2. Resumen ejecutivo

- Monolito Laravel 13 + Livewire 4 + Tailwind 4, optimizado para hosting compartido, sin Redis ni workers obligatorios.
- Modelo multi-organización con dos roles: `admin` y `client`, separados físicamente en `/admin/*` y `/portal/*`.
- Kanban vitaminado con columnas configurables, subtareas, prioridad, tipo, estimación y horas registradas.
- Chat por proyecto con adjuntos, mensajes de sistema automáticos, indicador de leído (doble check) y polling Livewire.
- Documentación markdown con visibilidad `private`/`public`. Las privadas solo las ve el admin y son accesibles vía MCP.
- Servidor MCP sobre HTTP/SSE dentro del propio monolito, con API tokens y tools de solo lectura para integrarse con IDEs.
- Asistente IA por proyecto con contexto inyectado (estado, tareas, documentos públicos), provider configurable (OpenAI, Anthropic).
- Calendario, registro de tiempo con timer, plantillas de proyecto reutilizables, feed de actividad y PWA instalable.
- Diseño visual propio: paleta warm `#FAFAF7` / `#FFFFFF`, tipografía Instrument Sans, layouts admin/portal/auth diferenciados.
- 100 % código y comentarios en castellano, PHPDoc obligatorio en métodos públicos, convenciones PHP 8/Laravel 13 modernas.

---

## 3. Contexto de negocio

**Situación inicial.** El equipo de ClientFlow detectó, desde su experiencia como freelancers, que la mayoría de herramientas de gestión de proyectos están pensadas para equipos internos de empresa (Jira, Asana, Linear, Monday). Cuando el cliente externo interviene, la fricción sube: tiene que aprender la herramienta, tener cuenta propia, o ser informado a mano por email/WhatsApp. Al mismo tiempo, el auge de IDEs con agentes IA (Claude Code, Cursor, Copilot) ha generado una segunda necesidad: mantener contexto de proyecto accesible desde el IDE, sin reescribir prompts cada vez.

**Necesidad principal.** Una pieza única que:

- Permita al admin organizar clientes, proyectos, tareas, docs y comunicación.
- Dé al cliente un portal de solo lectura y chat, sin obligarle a aprender una herramienta nueva.
- Sirva como fuente de verdad para agentes IA, tanto para el cliente (chat del proyecto) como para el admin (MCP server que conecta con su IDE).

**Objetivo del proyecto.** Construir un MVP open source, instalable, mantenible por una sola persona, sin infraestructura cara, que cubra el 80 % de los casos de un freelance o micro-agencia sin obligar a montar un SaaS multi-tenant.

---

## 4. Público objetivo

**Usuarios principales**

- **Freelance / micro-agencia (admin).** 1 a 5 personas. Necesita ver el estado de varios proyectos en paralelo, registrar horas, escribir propuestas, mantener documentación técnica accesible desde el IDE. Es el usuario que más horas pasa dentro de la app.
- **Cliente externo (client).** Dueño de pyme, responsable de marketing, fundador de startup. Necesita ver en menos de 10 segundos cómo va su proyecto, enviar un mensaje al admin, descargar una factura pendiente, consultar una duda con la IA del proyecto.

**Necesidades y expectativas**

- Cero onboarding para el cliente. Si tiene que aprender algo, se va.
- Carga rápida y predictibilidad incluso en hosting compartido.
- Privacidad clara: el cliente solo ve lo público, el admin ve y controla todo.
- IA útil, no decorativa. Las respuestas tienen que estar ancladas al proyecto real del cliente.
- Flujo continuo entre el IDE (admin) y la app (cliente) sin tener que copiar contexto a mano.

---

## 5. Funcionalidades clave

Agrupadas por fase del PRD. Cada una resuelve un problema concreto del flujo freelance ⇄ cliente.

**Foundation (Auth y organizaciones)**

- `Autenticación completa`. Login, registro, recuperación de password, invitación por token. Resuelve el alta y baja de usuarios sin soporte manual.
- `Roles admin / client`. Middleware por rol y redirección post-login diferenciada. Resuelve la separación de zonas y permisos.
- `CRUD de organizaciones e invitaciones`. El admin da de alta clientes y los suma por email con un token. Resuelve el onboarding de clientes sin sesiones de Zoom.

**Proyectos**

- `Proyectos asociados a organizaciones` con estado, progreso, fechas y visibilidad. Resuelve la consulta inmediata del cliente.
- `Plantillas de proyecto reutilizables` con columnas, tareas y documentos. Resuelve la creación repetitiva de proyectos del mismo tipo (web, app, diseño).

**Kanban**

- `Tablero vitaminado por proyecto` con columnas configurables (nombre, color, orden). Resuelve la personalización sin código.
- `Tareas con subtareas, prioridad, tipo, horas, fecha límite y asignado`. Resuelve la granularidad que necesita el admin sin abrumar al cliente.
- `Drag & drop entre columnas` con reordenación persistente. Resuelve la re-planificación continua.

**Documentación**

- `Documentos markdown con visibilidad private / public`. Resuelve la doble audiencia: interna (admin + IA) y externa (cliente).
- `Editor markdown con preview` vía Livewire. Resuelve la edición rápida sin salir de la app.

**Comunicación**

- `Chat por proyecto` con mensajes de sistema, adjuntos e indicador de leído. Resuelve la centralización de la comunicación frente a WhatsApp/email.
- `Notificaciones in-app y email` para mensajes, tareas asignadas y deadlines. Resuelve la reactividad sin saturar.

**IA y MCP**

- `Asistente IA por proyecto` con provider configurable y contexto inyectado. Resuelve las preguntas repetitivas del cliente sin intervención humana.
- `MCP server read-only` accesible desde el IDE del admin. Resuelve el trasiego de contexto entre repo, Notion y agentes.

**Productividad y运营**

- `Calendario mensual/semanal` con eventos, deadlines y milestones.
- `Registro de tiempo` con timer y dashboard de horas facturables.
- `Feed de actividad cronológico` por proyecto.
- `Adjuntos en tareas y mensajes` con drag & drop y control de MIME/tamaño.
- `PWA instalable` con cache offline de vistas y push notifications.

---

## 6. Flujo de usuario

**Admin (freelance) — flujo principal**

1. Se registra e instala la app en su hosting.
2. Crea una organización por cliente y la rellena con nombre, descripción y logo.
3. Invita a los empleados del cliente por email (cada invitación genera un token y un mail).
4. Crea un proyecto dentro de la organización, eligiendo plantilla o columnas por defecto.
5. Crea tareas, las reparte entre miembros, marca prioridades y fechas.
6. Escribe documentación: las sensibles quedan `private`, las de cliente `public`.
7. Configura el provider de IA y la API key en `Ajustes / IA`.
8. Chatea con el cliente dentro del proyecto. Sube adjuntos cuando hace falta.
9. Registra tiempo con timer o entrada manual, marca facturables.
10. Desde su IDE consulta el proyecto vía MCP para que el agente IA tenga contexto real.
11. Ajusta el estado del proyecto (planning → in_progress → completed) y mantiene el feed de actividad.

**Cliente (portal) — flujo principal**

1. Recibe invitación, se registra, aterriza en `/portal/dashboard`.
2. Ve sus proyectos activos, tareas asignadas y mensajes sin leer.
3. Abre un proyecto: ve resumen, kanban de solo lectura, documentos públicos, calendario.
4. Chatea con el admin. Sube archivos adjuntos.
5. Pregunta al asistente IA del proyecto qué tareas están pendientes o cuál es el estado.
6. Recibe notificaciones in-app y email cuando hay novedades.
7. Al cerrar, puede ver el resumen de horas invertidas (solo lectura).

**Casos de uso destacados**

- Onboarding de un nuevo cliente en menos de 5 minutos.
- Repetición de un proyecto del mismo tipo desde plantilla.
- Consulta de contexto desde el IDE del admin sin abrir la app.
- Preguntas repetitivas del cliente respondidas por la IA del proyecto.

---

## 7. Arquitectura técnica

**Tipo de sistema.** Monolito Laravel 13 con UI server-rendered vía Blade + Livewire 4. No es SPA. La interacción se hace con componentes Livewire (polling) y Vite para el bundle de assets. Pensado para correr en hosting compartido con PHP 8.3+, MySQL 8.4 y Docker solo en desarrollo.

**Capas**

- **Presentación.** Blade con layouts diferenciados (`admin`, `portal`, `auth`), sidebar fija en desktop, colapsable en mobile, header sticky.
- **Interacción.** Livewire 4 para Kanban con drag & drop, chat con polling, formularios, modales, calendario y feed de actividad.
- **Aplicación.** Controladores `Admin/`, `Portal/`, `Auth/`, middleware por rol (`EnsureUserIsAdmin`, `EnsureUserIsClient`), `FormRequests` para validación, `Actions` para casos de uso, `Services` para lógica reutilizable (`ActivityLogger`, `TimeTrackingService`, `AiService`, `McpServer`, `NotificationService`).
- **Dominio.** Modelos Eloquent, `Enums` para estados y prioridades, `DTOs` para transferencias, `Policies` para autorización, `ViewModels` opcionales para vistas complejas.
- **Persistencia.** MySQL 8.4 con migraciones versionadas, indices en columnas de búsqueda/enumeración, `storage/app/clientflow/` para adjuntos y media privados servidos por controlador con autorización.
- **Integración.** Endpoint MCP sobre HTTP/SSE en `/api/mcp/sse` con autenticación por API token (`personal_access_tokens`).

**Flujo de datos (resumen)**

```
[Browser] ⇄ Blade/Livewire ⇄ Controllers / Middleware ⇄ Services / Models ⇄ MySQL 8.4
                                          ⇣
                                       MCP (HTTP/SSE) ⇄ [IDE / agente IA del admin]
                                          ⇣
                                       AI provider (OpenAI / Anthropic) ⇄ Portal cliente
```

**Autenticación**

- Sesión Laravel con cookies. Doble zona por rol.
- API tokens personales para MCP server, en tabla `personal_access_tokens`.
- Invitaciones por token firmado con expiración (`organization_invitations`).

**Decisiones técnicas relevantes**

- `QUEUE_CONNECTION=sync` y `CACHE_STORE=database` en MVP para evitar workers y Redis.
- Adjuntos nunca servidos desde `public/`: pasan por controlador con `authorize()`.
- Documentos privados solo accesibles por admin (web) y por MCP (IDE).
- MCP server es solo lectura; cualquier write pasa por la app.
- IA nunca escribe directamente en la app: solo genera respuestas para el usuario.

---

## 8. Tecnologías usadas

| Tecnología | Versión | Qué hace en el proyecto | Por qué se eligió | Qué parte cubre |
|---|---|---|---|---|
| PHP | 8.3+ | Lenguaje del backend | Tipos estrictos, enums, attributes, mejor performance | Todo el monolito |
| Laravel | 13 | Framework principal | Eloquent, policies, queues, notifications, sanctum | Backend, routing, auth, mail, jobs |
| Livewire | 4 | UI interactiva server-rendered | Permite interacción rica sin SPA, sin API separada | Kanban, chat, modales, formularios, calendar, feed |
| Blade | Laravel 13 | Motor de plantillas | Integración nativa con Livewire y componentes | Vistas, layouts, partials, componentes reusables |
| Tailwind CSS | 4 | Estilos utility-first | Velocidad de iteración visual y coherencia con design system | Todas las vistas, layouts, componentes UI |
| Vite | latest | Build de assets | Integración oficial con Laravel | Compilación de JS, HMR, assets |
| MySQL | 8.4 | Persistencia | Estable, ampliamente soportado en hosting compartido | Datos de negocio |
| Docker | latest | Entorno de desarrollo | Reproducibilidad del entorno local | App, Nginx, PHP-FPM, MySQL, Node |
| Nginx | 1.27 | Servidor web en Docker | Estándar de producción | Sirve la app y assets |
| Node | 22 | Build de assets y tooling | Necesario para Vite y dependencias JS | Build pipeline |
| Bunny Fonts | – | Hospedaje de Instrument Sans | Alternativa europea a Google Fonts, sin tracking | Tipografía |
| OpenAI / Anthropic API | – | Proveedores de IA | Soporte multi-provider, modelos de calidad | Chat IA del proyecto |

> *Nota.* No hay Redis, no hay WebSocket en MVP, no hay workers permanentes. Se usa polling Livewire para chat y feed. Esto es deliberado: la app debe correr en hosting compartido.

---

## 9. UI/UX y presentación visual

**Filosofía visual.** Portal premium, no Jira ni CRM. La interfaz debe transmitir calma, claridad y confianza. El cliente entiende el estado del proyecto en menos de 10 segundos; el admin publica contenido en menos de 2 minutos.

**Paleta warm**

- Fondos: `#FAFAF7` (background), `#FFFFFF` (surface), `#F4F1EA` (hover), `#EDE9DE` (active).
- Bordes: `#E7E2D8` (border), `#D8D0C3` (border strong).
- Texto: `#111827` (primary), `#6B7280` (secondary), `#9CA3AF` (muted).
- Semánticos: Primary `#2563EB`, Success `#16A34A`, Warning `#D97706`, Danger `#DC2626`, Info `#8B5CF6`.

**Tipografía.** Instrument Sans (400, 500, 600) vía Bunny Fonts, fallback a `ui-sans-serif`. Escala con `text-3xl` (H1, 30px, 600), `text-2xl` (H2, 24px, 600), `text-xl` (H3, 20px, 500), `text-sm` (body, 14px), `text-xs` (small, 12px).

**Layouts diferenciados**

- `admin.blade.php` con sidebar 240 px, header sticky, content `p-6 lg:p-8`, fondo `#FAFAF7`.
- `portal.blade.php` con sidebar 220 px más suave, header con nombre de organización.
- `auth.blade.php` con card centrada `max-w-md`, `rounded-[28px]`, `shadow-sm`.

**Patrones de interfaz clave**

- Cards con `rounded-xl`, borde `#E7E2D8`, hover `border-[#D8D0C3]`.
- Botones Primary, Secondary, Ghost, Danger con estados hover.
- Badges de estado por color semántico.
- Kanban con drag & drop y cards de tarea compactas.
- Chat con burbujas diferenciadas (admin a la derecha azul, cliente a la izquierda blanco, sistema centrado gris) y doble check de leído.
- Markdown editor con preview side-by-side.
- Calendario mensual/semanal con dots de color por evento.
- Feed de actividad tipo timeline con icono por tipo de evento.

**Componentes UI reusables (Blade components).** `badge`, `button`, `card`, `input`, `modal`, `select`, `textarea`, `project-status-badge`.

---

## 10. Contenido visual necesario

**Capturas de pantalla recomendadas**

- Hero: dashboard admin con cards de resumen y proyectos recientes.
- Hero alt: portal cliente con sus proyectos a la vista.
- Kanban con drag & drop (estado intermedio, tarea a medio mover).
- Detalle de tarea con subtareas, adjuntos y tiempo registrado.
- Chat con burbujas admin/cliente/sistema, adjuntos, doble check.
- Documentación: editor markdown con preview side-by-side.
- Documentación: vista pública de un documento para el cliente.
- Calendario mensual con eventos y deadlines.
- Registro de tiempo: dashboard de horas con timer activo.
- Feed de actividad con eventos cronológicos e iconos.
- Asistente IA en acción dentro de un proyecto.
- PWA: prompt de instalación y vista offline.
- CRUD de organizaciones con tabla y buscador.
- Settings IA con provider y API key.
- MCP server listado de tools en un IDE.

**Videos necesarios**

- `hero-demo.mp4` (60-90 s). Recorrido del login admin → organización → proyecto → kanban → chat.
- `portal-walkthrough.mp4` (45-60 s). Experiencia cliente: invitación, dashboard, proyecto, chat con admin, pregunta a la IA.
- `mcp-ide-demo.mp4` (45-60 s). Conexión de un IDE al MCP server, listado de tools, consulta de proyecto y documentos.
- `kanban-drag-drop.mp4` (15-20 s). Loop corto mostrando drag & drop entre columnas con actualización de posición.
- `ai-assistant.mp4` (20-30 s). Cliente preguntando estado del proyecto, IA respondiendo con contexto real.

**Diagramas**

- `arquitectura-general.png`. Diagrama de capas: Browser → Livewire/Blade → Controllers → Services → MySQL + flechas a MCP y AI providers.
- `modelo-datos.png`. ER simplificado con User, Organization, Project, BoardColumn, Task, Document, Message, CalendarEvent, AIConfig, AgentTemplate.
- `flujo-roles.png`. Swimlanes Admin / Cliente / IDE con interacciones sobre la app.
- `mcp-flow.png`. Secuencia: IDE → SSE → Laravel → DB → respuesta.
- `flujo-chat.png`. Secuencia: usuario envía → Livewire polling → persist → broadcast UI + sistema de leído.

**Mockups**

- Mockup del hero con título, subtítulo, dos CTAs y un mockup del dashboard flotando.
- Mockup del bloque "Ficha rápida" con datos clave.
- Mockup de las cards de features con icono, título y copy.
- Mockup del diagrama de arquitectura interactivo (imagen + explicación al lado).
- Mockup de la sección de cierre con CTA final.

---

## 11. Retos y decisiones

**Problema 1 — Comunicación fragmentada con clientes.** Email y WhatsApp dispersan contexto y se pierde histórico.

- `Solución`. Chat por proyecto con mensajes persistentes, mensajes de sistema, adjuntos e indicador de leído. Toda la comunicación de un proyecto en un único timeline.
- `Tradeoff`. Sin WebSocket en MVP: se usa polling Livewire. Aceptable para volúmenes pequeños/medios; si crece, se puede migrar a WebSocket sin romper la API.

**Problema 2 — Documentación sensible duplicada en repos y Notion.** Las decisiones técnicas y contexto para IAs terminan dispersos.

- `Solución`. Documentos markdown con visibilidad `private` (solo admin) y `public` (cliente). Los privados son accesibles vía MCP desde el IDE.
- `Tradeoff`. La IA del cliente solo ve docs `public`. Es deliberado: nunca debe filtrar contexto interno.

**Problema 3 — Costes de infraestructura para un freelance.** Redis, workers, WebSockets disparan el coste de hosting.

- `Solución`. Monolito en hosting compartido con `QUEUE_CONNECTION=sync` y `CACHE_STORE=database`. Polling en lugar de WebSocket. MCP server dentro del propio monolito.
- `Tradeoff`. Menor rendimiento bajo carga extrema, pero aceptable para 1-5 admins y decenas de clientes. Diseñado para escalar a Redis sin romper contratos.

**Problema 4 — Multi-tenancy complejo en SaaS tradicional.** El modelo SaaS multi-tenant introduce costes de aislamiento, billing y soporte.

- `Solución`. No es SaaS multi-tenant. Es una instalación por freelance/agencia con sus propios clientes. Los datos se aíslan por `organization_id` con policies de Laravel.
- `Tradeoff`. El freelance tiene que mantener su instancia. A cambio, no hay vendor lock-in y el código es open source.

**Problema 5 — Configuración de IA heterogénea.** Cada proyecto puede necesitar un provider o modelo distinto.

- `Solución`. `ai_configs` con `provider` (openai, anthropic), `api_key` encriptada en BD y `is_active`. Configurable global o por proyecto. System prompt por proyecto con override posible.
- `Tradeoff`. La API key vive en BD; se asume que el admin protege su instalación (lo razonable para un open source self-hosted).

**Problema 6 — Reutilización de configuración de agentes IA.** Recrear system prompts y tools en cada proyecto consume tiempo.

- `Solución`. `agent_templates` con `system_prompt`, `tools` (JSON), `model` y `category`. Al asignar a un proyecto, se crea copia editable en `project_agents` con `system_prompt_override` opcional. Exportable a JSON para uso directo en IDEs.
- `Tradeoff`. Sin versioning de templates; un cambio posterior en el template base no se propaga automáticamente a proyectos ya creados (intencional, para no romper setups existentes).

**Problema 7 — Visibilidad de lo que pasa en el proyecto.** El cliente quiere saber qué ha pasado sin tener que preguntar.

- `Solución`. Feed de actividad cronológico por proyecto (`activity_log`) con tipos `task_created`, `task_completed`, `document_created`, `status_changed`, etc. El portal cliente solo ve eventos públicos.
- `Tradeoff`. Hay que loguear manualmente desde los puntos críticos; un olvido significa evento perdido. Se mitiga centralizando en el servicio `ActivityLogger`.

---

## 12. Resultados e impacto

> *Supuesto.* El proyecto está en desarrollo (MVP) en el momento de este case study, por lo que las métricas de producción no existen todavía. Se listan las métricas objetivo y los resultados esperados.

**Métricas objetivo a medir tras release**

- Tiempo medio de respuesta a una duda del cliente (target: < 1 h, parte derivada a IA).
- % de proyectos donde el cliente consulta estado por chat en lugar de email/WhatsApp.
- Nº de proyectos activos simultáneos que un admin puede gestionar sin saturarse.
- Tiempo medio de creación de un nuevo proyecto desde plantilla (target: < 2 min).
- Tasa de adopción del MCP server entre admins que usan IDEs con IA.
- Mensajes de chat respondidos automáticamente por la IA del proyecto (target: > 40 %).

**Impacto esperado**

- Reducción drástica de preguntas repetitivas del cliente gracias a la IA del proyecto.
- Visibilidad continua: el cliente entra al portal y ve el estado sin preguntar.
- Ahorro de tiempo del admin al reutilizar plantillas y al tener el contexto en un solo sitio.
- Mejora de UX frente a la fragmentación email/WhatsApp/Trello/Notion.
- Onboarding de clientes en minutos vía invitación con token.

**Estado actual.** MVP en desarrollo, sin release público. Las decisiones de arquitectura están validadas con documentación técnica completa (PRD, arquitectura, modelo de datos, flujos, design system, implementación).

---

## 13. Aprendizajes

**Qué funcionó bien**

- Apostar por un monolito Laravel + Livewire frente a separar backend/frontend reduce complejidad operativa y permite mantenerlo solo.
- Diseño deliberado de un "portal premium" con paleta warm y tipografía cuidada diferencia el producto de la estética Jira/CRM.
- Modelo `private`/`public` para documentos unifica el doble destino de la información (admin + IA vs cliente).
- MCP server dentro del propio monolito elimina la necesidad de infraestructura adicional.
- Templates de proyecto y de agentes IA atacan los dos grandes focos de fricción repetitiva del freelance.
- Convención dura de código y comentarios en castellano simplifica el mantenimiento por parte del equipo.

**Qué mejoraría**

- Validar empíricamente los límites del polling Livewire con cargas reales antes de decidir la migración a WebSocket.
- Internacionalización (i18n) ya desde MVP, aunque la app arranque solo en castellano.
- Versionado y diff de documentos markdown, especialmente de los privados (contexto crítico para IA).
- Versionado de `agent_templates` con posibilidad de mergear cambios base a proyectos ya existentes.
- Pruebas de carga y stress antes del primer release público.
- Telemetría mínima de uso para validar las hipótesis de las métricas objetivo.

**Qué se aprendió**

- Un proyecto con audiencia clara (freelance → cliente) puede diferenciarse mucho simplemente atacando los problemas de comunicación y visibilidad.
- La IA es más útil cuando el contexto está estructurado y limitado (docs públicas del proyecto) que cuando se le da acceso a todo.
- "Self-hosted open source" sigue siendo una propuesta de valor real para profesionales técnicos que no quieren ceder el control de sus datos.

---

## 14. Dudas y datos faltantes

Pendiente de confirmar o aportar para cerrar la página de caso de estudio:

- **Año del proyecto / fecha de release.** No figura explícitamente; el repo se trabaja en 2025-2026.
- **Duración total estimada del MVP.** No definida en docs.
- **Estado actual del desarrollo.** No hay un README de release; se sabe por estructura que está en desarrollo.
- **Rol exacto del autor en el case study** (fundador, único dev, equipo, etc.).
- **Capturas reales de la app.** No existen todavía al ser MVP en desarrollo; habrá que generar mockups de alta fidelidad o capturas cuando se libere una primera versión navegable.
- **Videos reales de producto.** No existen todavía; los videos listados en la sección 10 son guiones pendientes de grabar.
- **Métricas reales de impacto.** No aplican hasta release; los targets son hipótesis.
- **Nombre/logo definitivo y assets de marca** para la sección hero.
- **Testimonios de cliente.** No hay base de usuarios todavía.
- **URL pública de demo o repo.** Pendiente de release.
- **Pricing si se ofreciera versión hosted** (no es objetivo actual, pero podría reforzar la narrativa).

---

## 15. Material listo para la página

**Hero copy**

- **Título.** `ClientFlow`.
- **Subtítulo.** `Un solo lugar para clientes, proyectos, comunicación y agentes de IA.`
- **CTA principal.** `Ver demo`.
- **CTA secundario.** `Ver código`.

**Texto de contexto (2.4).**

> La gestión entre freelance y cliente suele vivir en email, WhatsApp, Notion y Trello al mismo tiempo. El cliente no sabe en qué punto está su proyecto, el admin repite contexto en cada canal y la documentación sensible termina duplicada en repositorios sin control de acceso.

**Texto de solución (2.5).**

> ClientFlow centraliza clientes, proyectos, tareas, chat, documentación y agentes IA en una sola web app self-hosted. El cliente tiene un portal de solo lectura con chat y asistente IA. El admin gestiona todo desde un panel único, conecta su IDE vía MCP y mantiene un repositorio de contexto para IAs dentro del propio proyecto.

**Títulos de sección**

- `2.4 Contexto` → `El caos de gestionar un proyecto freelance en 6 herramientas`.
- `2.5 Solución` → `Una sola app, dos espacios: panel admin y portal cliente`.
- `2.6 Features principales` → `Todo lo que necesitas para entregar proyectos sin perder contexto`.
- `2.9 Tecnologías` → `Un monolito Laravel pensado para correr en cualquier hosting`.
- `2.10 Arquitectura` → `Cómo encajan las piezas: de Livewire al MCP server`.
- `2.12 Retos y decisiones` → `Por qué no es un SaaS multi-tenant y por qué eso es una ventaja`.
- `2.13 Resultados` → `Qué métricas queremos mover cuando esté en producción`.

**Copy breve para features**

- `Kanban vitaminado`. Columnas configurables, subtareas, prioridades, tiempo estimado y real.
- `Chat por proyecto`. Mensajes, adjuntos, mensajes de sistema y doble check de leído.
- `Documentación con doble visibilidad`. Markdown con control `private` / `public` por documento.
- `Asistente IA del proyecto`. Contexto real inyectado, provider configurable (OpenAI, Anthropic).
- `MCP server para tu IDE`. Solo lectura, API tokens, contexto accesible desde Claude Code o Cursor.
- `Registro de tiempo`. Timer y entrada manual, dashboard por proyecto, marcado facturable.
- `Plantillas de proyecto`. Columnas, tareas y documentos esqueleto reutilizables.
- `Feed de actividad`. Timeline cronológico de todo lo que pasa en el proyecto.
- `Calendario y deadlines`. Vista mensual/semanal, eventos, milestones y fechas límite.
- `PWA instalable`. Funciona offline y envía push notifications.
- `Biblioteca de agentes IA`. Templates con system prompt, tools y modelo; exportables a JSON.
- `Adjuntos en tareas y mensajes`. Drag & drop, control de MIME y tamaño, siempre autorizados.

**Copy para tecnología y arquitectura**

- `Stack`. `PHP 8.3+ · Laravel 13 · Livewire 4 · Blade · Tailwind 4 · MySQL 8.4 · Vite · Docker`.
- `Arquitectura`. `Monolito Laravel server-rendered, sin Redis ni workers, con un endpoint MCP sobre HTTP/SSE dentro del mismo proceso. La interacción se hace con Livewire (polling para chat y feed) y los adjuntos nunca se sirven desde public/, siempre pasan por un controlador con authorize().`
- `Decisiones clave`. `Self-hosted por diseño, no SaaS. Multi-organization, no multi-tenant. Documentos private solo visibles por admin (web) y por MCP (IDE). IA sin capacidad de escritura.`

**Cierre (2.15)**

- **Resumen final.** `ClientFlow es la pieza que faltaba entre tu IDE, tu cliente y tus agentes IA: un único lugar donde el proyecto vive, se documenta, se comunica y se automatiza.`
- **CTA final.** `Ver demo` · `Ver código` · `Contactar`.

---

## Anexo — Campos estructurados (YAML)

```yaml
project:
  name: ClientFlow
  slug: clientflow
  tagline: Un solo lugar para clientes, proyectos, comunicación y agentes de IA.
  role: Product architect & full-stack developer
  year: 2025-2026
  type: Web app open source self-hosted
  status: En desarrollo (MVP)
  duration: Pendiente
  stack:
    - PHP 8.3+
    - Laravel 13
    - Livewire 4
    - Blade
    - Tailwind CSS 4
    - MySQL 8.4
    - Vite
    - Docker
  audience:
    primary: Freelance / micro-agencia
    secondary: Cliente externo (pyme, startup, responsable de área)
  cta:
    primary: Ver demo
    secondary: Ver código
  features:
    - name: Kanban vitaminado
      value: Columnas configurables, subtareas, prioridades, tiempo estimado y real.
    - name: Chat por proyecto
      value: Mensajes, adjuntos, mensajes de sistema y doble check de leído.
    - name: Documentación con doble visibilidad
      value: Markdown con control private/public por documento.
    - name: Asistente IA del proyecto
      value: Contexto real inyectado, provider configurable (OpenAI, Anthropic).
    - name: MCP server para tu IDE
      value: Solo lectura, API tokens, contexto accesible desde Claude Code o Cursor.
    - name: Registro de tiempo
      value: Timer y entrada manual, dashboard por proyecto, marcado facturable.
    - name: Plantillas de proyecto
      value: Columnas, tareas y documentos esqueleto reutilizables.
    - name: Feed de actividad
      value: Timeline cronológico de todo lo que pasa en el proyecto.
    - name: Calendario y deadlines
      value: Vista mensual/semanal, eventos, milestones y fechas límite.
    - name: PWA instalable
      value: Funciona offline y envía push notifications.
    - name: Biblioteca de agentes IA
      value: Templates con system prompt, tools y modelo; exportables a JSON.
    - name: Adjuntos en tareas y mensajes
      value: Drag & drop, control de MIME y tamaño, siempre autorizados.
  architecture:
    pattern: Monolito Laravel
    frontend: Blade + Livewire 4 + Tailwind 4
    backend: Laravel 13 + PHP 8.3+
    database: MySQL 8.4
    realtime: Polling Livewire (sin WebSocket en MVP)
    queue: sync (database driver)
    cache: database
    storage: storage/app/clientflow/ (servido por controlador con authorize)
    integrations:
      - OpenAI API
      - Anthropic API
      - MCP server (HTTP/SSE)
  links:
    repo: Pendiente
    demo: Pendiente
    docs: /docs
```
