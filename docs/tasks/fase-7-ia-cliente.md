# Fase 7 — IA para el cliente

Documento tecnico de lo implementado en la fase 7 del MVP de
ClientFlow. Anade un asistente IA por proyecto al que el
cliente puede preguntar sobre el estado de su trabajo. El
admin configura el provider y la API key desde
`/admin/settings/ai`.

## Alcance

Segun `TODOs.md`, la fase 7 incluye:

- Migraciones `ai_configs`, `ai_chat_sessions`,
  `ai_chat_messages`.
- Modelo `AiConfig` con API key cifrada.
- Modelos `AiChatSession` y `AiChatMessage`.
- Enum `AiProvider` con los casos `openai`, `anthropic` y
  `opencode` (provider OpenAI-compatible parametrizable).
- Enum `AiChatRole` (`user`, `assistant`, `system`).
- Vista de configuracion IA en el panel admin.
- Servicio `AiService` con soporte multi-provider.
- Chat IA por proyecto para clientes del portal.
- Inyeccion de contexto del proyecto en el system prompt.
- Sesiones de chat (crear, continuar, borrar).
- Rate limiting para evitar abuso.

Ademas, en esta fase:

- Acceso del admin al mismo chat IA por proyecto para
  poder probar la configuracion sin tener que delegar en
  un cliente.
- Persistencia del campo `tokens_used` por mensaje
  (preparado para un futuro dashboard de consumo, no se
  muestra aun en la UI).
- Boton "Probar conexion" en el formulario de settings
  que lanza una peticion de prueba al provider y muestra
  un flash con el resultado.
- Polimorfismo sobre la `AiConfig`: una fila global con
  `project_id = null` sirve como fallback para cualquier
  proyecto que no tenga la suya propia.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_19_080000_create_ai_configs_table.php` | `ai_configs` con `project_id` (nullable=global), `provider`, `api_key` (cast `encrypted` en el modelo), `model`, `system_prompt`, `is_active`, `max_messages_per_hour`, `max_sessions_per_day`. Unique sobre `project_id` para garantizar una sola fila por proyecto y una sola global. |
| `database/migrations/2026_06_19_080001_create_ai_chat_sessions_table.php` | `ai_chat_sessions` con FK a `projects` y `users` (cascade). Indice `(user_id, project_id)` para el sidebar de sesiones. |
| `database/migrations/2026_06_19_080002_create_ai_chat_messages_table.php` | `ai_chat_messages` con FK a la sesion (cascade), `role` (enum), `content` (text), `tokens_used` (int nullable), `created_at` (sin `updated_at`: los mensajes son inmutables). Indice compuesto `(ai_chat_session_id, id)` para el orden cronologico. |

### 2. Enums

`app/Enums/AiProvider.php`:

| Caso | Valor | Modelo por defecto | Base URL | Compatible OpenAI |
|---|---|---|---|---|
| `Openai` | `openai` | `gpt-4o-mini` | `https://api.openai.com` | Si |
| `Anthropic` | `anthropic` | `claude-3-5-haiku-latest` | `https://api.anthropic.com` | No |
| `Opencode` | `opencode` | `gpt-4o-mini` | configurable via `ai.opencode.base_url` | Si |

Metodos: `label()`, `color()`, `defaultModel()`, `baseUrl()`,
`isOpenaiCompatible()`. El enum se mantiene puro (no usa
`config()` ni `app()`) para que los tests unitarios no
requieran bootstrap de Laravel.

`app/Enums/AiChatRole.php`:

| Caso | Valor | Etiqueta |
|---|---|---|
| `User` | `user` | Usuario |
| `Assistant` | `assistant` | Asistente |
| `System` | `system` | Sistema |

Metodos: `label()`, `color()`, `isUser()`, `isAssistant()`,
`isSystem()`.

### 3. Modelos

#### `App\Models\AiConfig`

- Casts: `provider => AiProvider`, `api_key => encrypted`,
  `is_active => bool`, `*_per_hour/day => int`.
- Atributo oculto: `api_key`. Asi nunca se serializa al
  frontend.
- Relaciones: `project()` (BelongsTo).
- Helpers: `isGlobal()`, `effectiveModel()` (fallback al
  default del provider), `effectiveSystemPrompt()` (null
  si vacio).
- Scopes: `active()`, `global()`, `forProject($id)`.

#### `App\Models\AiChatSession`

- Relaciones: `project()`, `user()`, `messages()`
  (ordenadas por id), `lastMessage()`.
- Helper: `displayTitle()` que devuelve el titulo
  puesto por el usuario, o un preview autogenerado del
  primer mensaje del usuario (60 chars), o el texto
  "Nueva conversacion" si la sesion esta vacia.
- Scopes: `forUser($id)`, `forProject($id)`,
  `forUserInProject($userId, $projectId)`.

#### `App\Models\AiChatMessage`

- Solo `created_at` (no `updated_at`): los mensajes son
  inmutables.
- Casts: `role => AiChatRole`, `tokens_used => int`.
- Relacion: `session()`.
- Helpers: `isUser()`, `isAssistant()`, `isSystem()`.

`App\Models\Project` ya tenia las firmas adelantadas
`aiConfig()` (HasOne) y `aiChatSessions()` (HasMany)
de fases anteriores. `App\Models\User` recien
materializa `aiChatSessions()` (HasMany).

### 4. Servicios

Estructura en `app/Services/Ai/`:

```
Ai/
├── Contracts/
│   ├── AiMessage.php            # DTO inmutable (role, content)
│   ├── AiProvider.php           # Interfaz del provider
│   ├── AiProviderException.php  # Excepcion estandar
│   └── AiResponse.php           # DTO de respuesta
├── Providers/
│   ├── OpenAiProvider.php       # Chat Completions de OpenAI
│   ├── AnthropicProvider.php    # Messages de Anthropic
│   └── OpencodeProvider.php     # OpenAI-compatible con base_url configurable
├── ProjectContextBuilder.php    # System prompt minimo en castellano
├── AiRateLimiter.php            # Wrapper del RateLimiter facade
└── AiService.php                # Orquestador
```

#### Contratos

- `AiMessage`: DTO inmutable con `role` y `content`. Se
  construye desde `AiChatMessage` con un cast explicito
  del enum a string.
- `AiProvider`: interfaz con `name()`, `defaultModel()` y
  `send(AiConfig, AiMessage[], ?modelOverride)`.
- `AiResponse`: DTO con `content`, `model`, `tokensUsed`.
- `AiProviderException`: extiende `RuntimeException`. La
  UI la captura para mostrar mensajes amables.

#### `ProjectContextBuilder`

Genera un system prompt en castellano con:

- Nombre del proyecto, organizacion, estado y progreso
  calculado (`Project::tasks_progress_percent`).
- Las 10 ultimas tareas con titulo, prioridad, asignado
  y estado (pendiente/completada).
- Titulos de los documentos publicos (no privados, por
  seguridad: la IA no debe ver docs internos).

Si el admin define un `system_prompt` en la `AiConfig`,
este se usa en vez del autogenerado. Asi un admin puede
darle al bot una personalidad concreta sin tocar codigo.

#### `AiRateLimiter`

Encapsula el facade `RateLimiter` de Laravel con dos cubos:

- `ai-chat:messages:{userId}:{projectId}`: max N por
  hora (`max_messages_per_hour`).
- `ai-chat:sessions:{userId}:{projectId}`: max M por dia
  (`max_sessions_per_day`).

Las claves incluyen el par `(user, project)` para que un
usuario con varias sesiones en proyectos distintos no
comparta cuota.

Si la `AiConfig` tiene `max_messages_per_hour = 0` (o no
lo define), cae al default de `config('ai.default_max_messages_per_hour')`
(20). Asi un admin que no rellene los campos en el
formulario sigue teniendo limites razonables.

#### `OpenAiProvider`

- POST a `{base}/v1/chat/completions`.
- Auth: `Authorization: Bearer {api_key}`.
- Body: `{model, messages: [...]}`. El listado de mensajes
  se pasa tal cual: el contrato `AiMessage` ya tiene la
  forma `{role, content}` que espera OpenAI.
- Response: extrae `choices[0].message.content` y
  `usage.total_tokens`.
- Errores: cualquier HTTP no-2xx o respuesta sin
  `choices[0].message.content` se convierte en
  `AiProviderException` con el codigo HTTP y el body.

#### `AnthropicProvider`

- POST a `{base}/v1/messages`.
- Auth: cabeceras `x-api-key` y `anthropic-version`
  (`2023-06-01`).
- **Diferencia clave**: Anthropic no admite `role: system`
  en la lista de mensajes. El provider separa los mensajes
  `system` de los `user`/`assistant` y los pasa en el
  campo top-level `system`. Si hay varios mensajes
  `system`, se concatenan separados por doble salto de
  linea.
- Response: extrae `content[0].text` y suma
  `usage.input_tokens + usage.output_tokens` para
  reportar el total.

#### `OpencodeProvider`

Extiende `OpenAiProvider` y reutiliza la logica HTTP
(POST a `/v1/chat/completions`). Lo unico que anade es:

- `defaultModel()`: lee de `config('ai.models.opencode')`
  si esta definido, si no usa el default del enum.
- `resolveBaseUrl(AiConfig)`: lee
  `config('ai.opencode.base_url')` si esta definido, si no
  cae al endpoint publico de OpenAI. Esto permite apuntar
  a un proxy local (LM Studio, Ollama con su adaptador
  OpenAI, vLLM, etc.) sin tocar codigo.

El patron `resolveBaseUrl` esta definido en `OpenAiProvider`
como metodo protegido para que `OpencodeProvider` lo
sobreescriba sin duplicar el resto de la logica.

#### `AiService`

Orquestador. Punto de entrada para el resto de la app.

- `sendMessage(Project, User, AiChatSession, string $userMessage): AiChatMessage`:
  1. Resuelve la `AiConfig` del proyecto (fallback a global).
  2. Verifica el rate limit horario. Si lo supera, lanza
     `RuntimeException` con un mensaje amigable.
  3. Construye el system prompt (custom o autogenerado).
  4. Compone el historial de mensajes: system + mensajes
     previos de la sesion (excluyendo `system`) + nuevo
     mensaje del usuario.
  5. Despacha al provider concreto.
  6. Persiste el mensaje del usuario y la respuesta del
     asistente.
  7. Incrementa el rate limit horario.
  8. Devuelve el `AiChatMessage` del asistente.

- `createSession(Project, User, ?string $title): AiChatSession`:
  verifica el rate limit diario y crea la fila.

- `testConnection(AiConfig): array{ok, message}`: lanza
  una peticion corta al provider. Lo usa el boton "Probar
  conexion" del admin.

- `resolveConfig(Project): AiConfig`: prefiere la config
  del proyecto; si no existe, cae a la global; si tampoco,
  lanza `RuntimeException` (la UI muestra "No hay ninguna
  configuracion de IA activa").

#### Binding en el contenedor

`AppServiceProvider::register()` registra todos los
servicios como singletons y construye `AiService` con el
mapa `provider => instancia` ya resuelto:

```php
$this->app->singleton(AiService::class, function ($app): AiService {
    return new AiService(
        rateLimiter: $app->make(AiRateLimiter::class),
        contextBuilder: $app->make(ProjectContextBuilder::class),
        providers: AiService::defaultProviders(
            openai: $app->make(OpenAiProvider::class),
            anthropic: $app->make(AnthropicProvider::class),
            opencode: $app->make(OpencodeProvider::class),
        ),
    );
});
```

Anadir un provider nuevo en el futuro se reduce a:

1. Crear la clase que implemente `AiProvider`.
2. Anadir el binding en el service provider.
3. Anadir el caso en el enum `AiProvider` (y sus
   metodos `defaultModel()` / `baseUrl()` si aplica).

### 5. Policies

#### `App\Policies\AiConfigPolicy`

Todas las acciones (view, create, update, delete, test)
devuelven `$user->isAdmin()`. La configuracion IA es
operativa del admin: los clientes no deberian saber ni
que provider esta configurado.

Los metodos reciben solo `User` (no `AiConfig`) porque
los controladores invocan `authorize('update', AiConfig::class)`.
La policy discrimina solo por rol, no por fila.

#### `App\Policies\AiChatSessionPolicy`

| Metodo | Admin | Cliente |
|---|---|---|
| `viewAny($user, $project)` | siempre | si puede ver el proyecto |
| `view($user, $session)` | siempre | si es dueno y puede ver el proyecto |
| `create($user, $project)` | siempre | si puede ver el proyecto |
| `update` | false (inmutable) | false (inmutable) |
| `delete($user, $session)` | siempre | si es dueno |

La doble pista `[AiChatSession::class, $project]` en
`authorize('create', ...)` es importante: si usara
`authorize('create', $project)`, Laravel inferiria
`ProjectPolicy::create` (admin-only) y los clientes no
podrian iniciar sesiones.

### 6. Form Requests

- `Admin/UpdateAiConfigRequest`: provider, api_key (opcional,
  se conserva la anterior si vacia), model, system_prompt,
  is_active, max_messages_per_hour, max_sessions_per_day,
  project_id (nullable para global).
- `Portal/CreateAiChatSessionRequest`: title opcional.

### 7. Controladores

#### Admin

- `Admin/AiConfigController`:
  - `edit(Request, AiService)`: muestra el formulario.
    Resuelve la `AiConfig` aplicable (proyecto o global)
    o devuelve una instancia vacia con valores por
    defecto si no existe (asi el primer guardado la crea).
  - `update(UpdateAiConfigRequest, AiService)`: persiste.
    Si el admin deja `api_key` vacia al guardar, se
    conserva la clave anterior para no romper la
    configuracion sin querer.
  - `test(Request, AiService)`: lanza
    `AiService::testConnection()` y devuelve un flash
    (`status` si OK, `ai_test_error` si falla).
- `Admin/AiChatController`:
  - `index(Request, Project)`: redirige a la sesion mas
    reciente del admin en el proyecto. Si no hay
    ninguna, muestra `admin.projects.ai-empty`.
  - `show(Request, Project, AiChatSession)`: muestra la
    conversacion.
  - `destroy(...)`: borra la sesion (admin: cualquier
    sesion del proyecto).

#### Portal

- `Portal/AiChatController`: mismo patron que el admin
  pero filtrado por `user_id = $user->id`. Anade
  `store(CreateAiChatSessionRequest, Project, AiService)`
  para crear nuevas sesiones desde el sidebar.

### 8. Componentes Livewire

- `Admin/AiConfig/SettingsForm`: formulario con
  validacion reactiva y boton "Probar conexion". Al
  guardar, delega en `Admin/AiConfigController::update`
  para reusar la logica de "conservar api_key si viene
  vacia".
- `Shared/AiChat/ChatWindow`: renderiza la conversacion
  y gestiona el envio. Mismo componente para admin y
  portal (la autorizacion la hace la policy del proyecto
  y de la sesion). Captura `RuntimeException` del
  `AiService` y la muestra como `$error` para que la
  vista la renderice inline.
- `Portal/AiChat/SessionList`: sidebar de sesiones del
  cliente. Boton "Nueva conversacion" (con rate limit)
  y boton de borrar por sesion.
- `Admin/AiChat/SessionList`: mirror del anterior para
  el panel admin.

### 9. Vistas Blade

- `admin/settings/ai.blade.php`: layout admin + componente
  `SettingsForm` + flashes de `status` / `ai_test_error`.
- `admin/projects/ai.blade.php` y `ai-empty.blade.php`:
  layout admin con sidebar de sesiones + componente
  `ChatWindow`.
- `portal/projects/ai.blade.php` y `ai-empty.blade.php`:
  layout portal con sidebar de sesiones + componente
  `ChatWindow`.
- `livewire/admin/ai-config/settings-form.blade.php`:
  formulario con campos para provider, API key, modelo,
  system prompt, limites, toggle de activo, y botones
  "Probar conexion" + "Guardar".
- `livewire/admin/ai-chat/session-list.blade.php` y
  `livewire/portal/ai-chat/session-list.blade.php`:
  sidebar con lista de sesiones y boton de nueva
  conversacion.
- `livewire/shared/ai-chat/chat-window.blade.php`:
  conversacion con burbujas diferenciadas para
  user/assistant, empty state con icono, formulario
  inferior con envio en Enter (via Alpine x-data, sin
  logica de negocio), y auto-scroll al fondo via
  `$nextTick`.

### 10. Rutas

`routes/web.php` (admin, dentro del grupo `auth`+`admin`):

```txt
GET    admin/settings/ai              admin.ai.config.edit
PUT    admin/settings/ai              admin.ai.config.update
POST   admin/settings/ai/test         admin.ai.config.test
GET    admin/projects/{project}/ai               admin.projects.ai
GET    admin/projects/{project}/ai/sessions/{session}  admin.projects.ai.show
DELETE admin/projects/{project}/ai/sessions/{session}  admin.projects.ai.destroy
```

`routes/web.php` (portal, dentro del grupo `auth`+`client`):

```txt
GET    portal/projects/{project}/ai               portal.projects.ai
POST   portal/projects/{project}/ai/sessions      portal.projects.ai.sessions.store
GET    portal/projects/{project}/ai/sessions/{session}  portal.projects.ai.show
DELETE portal/projects/{project}/ai/sessions/{session}  portal.projects.ai.destroy
```

Total: 10 rutas nuevas (5 admin + 4 portal + 1 store).

### 11. Enlace en la UI existente

- `partials/admin-sidebar.blade.php`: nuevo item
  "Configuracion IA" debajo de "Proyectos".
- `admin/projects/show.blade.php`: nuevo boton
  "Asistente IA" en la barra de acciones.
- `portal/projects/show.blade.php`: mismo boton en
  la barra de acciones del portal.

### 12. Configuracion

`config/ai.php` centraliza:

- `default_max_messages_per_hour` (default 20).
- `default_max_sessions_per_day` (default 10).
- `opencode.base_url` (default `https://api.openai.com`,
  override via `OPENCODE_BASE_URL`).
- `models.openai` / `models.anthropic` / `models.opencode`
  (defaults por provider, overrideables via env).
- `http_timeout` (30s) y `http_connect_timeout` (10s).

`.env.example` anade las variables con comentarios.

### 13. Tests

Total anadido: **45 tests, 127 aserciones**.

```
tests/Unit/Enums/AiProviderTest.php                        3 tests
tests/Unit/Enums/AiChatRoleTest.php                        2 tests
tests/Unit/Models/AiConfigTest.php                         6 tests
tests/Unit/Models/AiChatSessionTest.php                    7 tests
tests/Unit/Models/AiChatMessageTest.php                    3 tests
tests/Unit/Services/Ai/ProvidersTest.php                   6 tests
tests/Unit/Services/Ai/ProjectContextBuilderTest.php       4 tests
tests/Unit/Services/Ai/AiServiceTest.php                  12 tests
tests/Unit/Services/Ai/AiRateLimiterTest.php               4 tests
tests/Feature/Admin/AiSettingsTest.php                     6 tests
tests/Feature/Admin/AiChatTest.php                         4 tests
tests/Feature/Portal/AiChatTest.php                        9 tests
```

Cubren:

- Encriptacion de `api_key` en reposo (BD) y descifrado
  en memoria.
- Casts a enums (`provider`, `role`).
- Resolucion de la `AiConfig`: proyecto > global > error.
- Scopes (`active`, `global`, `forProject`, `forUser`,
  `forProject`, `forUserInProject`).
- `AiConfig::effectiveModel()` y `effectiveSystemPrompt()`.
- `AiChatSession::displayTitle()` con y sin titulo, con
  mensaje largo (truncado a 60 chars) y sin mensajes.
- `AiChatMessage::isUser()` / `isAssistant()` / `isSystem()`.
- Peticiones HTTP correctas a OpenAI, Anthropic y Opencode
  con `Http::fake()`. Verificacion de cabeceras, body y
  parseo de respuesta.
- Errores HTTP y respuestas malformadas → `AiProviderException`.
- Anthropic: separacion de system prompt, concatenacion
  de multiples system messages.
- Opencode: URL configurable, fallback a OpenAI publico.
- `ProjectContextBuilder`: incluye nombre, estado,
  progreso, ultimas 10 tareas, titulos de docs publicos
  (no privados). Manejo de proyectos vacios.
- `AiService::sendMessage`: persiste user + assistant,
  inyecta system prompt, persiste turnos multiples sin
  duplicar el system, falla sin config, fallback a global,
  rechaza mensajes vacios, bloquea por rate limit.
- `AiService::createSession`: bloquea por rate limit diario.
- `AiService::testConnection`: ok en exito, error en
  401/5xx.
- `AiRateLimiter`: limites por hora, conteo independiente
  por (user, project), limite diario separado, fallback a
  defaults cuando la config tiene 0.
- Feature admin: el admin ve la pagina de settings, el
  cliente es redirigido al portal; el admin guarda config
  global y por proyecto; la api_key se conserva si el
  admin no la envia; el boton "Probar conexion" hace una
  peticion real con `Http::fake()`.
- Feature admin: el admin ve el chat de sus proyectos, se
  redirige a la sesion mas reciente, puede abrir y borrar.
- Feature portal: el cliente ve el estado vacio o la
  sesion mas reciente; puede crear y borrar sesiones
  propias; NO puede ver proyectos de otras orgs, ni
  proyectos ocultos, ni sesiones de otros clientes.

### 14. Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:

- 390 tests pasan, 963 aserciones, 0 fallan.
- Build Vite sin warnings.
- 10 rutas custom nuevas (acumulado: 79 + 10 = 89).

## Decisiones tecnicas relevantes

1. **Multi-provider sin SDKs externos**: usamos el
   `Http` facade de Laravel (Guzzle bajo el capot). Esto
   evita dependencias extras y hace que los tests con
   `Http::fake()` sean triviales. La pega es que no
   tenemos tipos de retorno: si OpenAI cambia su API,
   fallaremos en runtime. Es aceptable para MVP; si
   aparecen mas providers en el futuro, se justifica un
   SDK mas robusto.

2. **Anthropic y `system`**: Anthropic no admite mensajes
   `role: system` en la lista. El provider los extrae y
   los concatena en el campo top-level `system`. Asi el
   `AiService` no tiene que conocer la diferencia: envia
   mensajes uniformes y deja que cada provider los
   traduzca a su formato.

3. **System prompt por proyecto con fallback global**:
   cada proyecto puede tener su propia `AiConfig` con
   un system prompt custom, o usar el autogenerado
   (`ProjectContextBuilder`) con el contexto del proyecto.
   Si un proyecto no tiene config propia, se usa la
   global. Asi el admin puede tener una sola API key
   compartida y un system prompt generico para todos
   los proyectos, o afinar el system prompt por cliente
   sin tocar codigo.

4. **`api_key` cifrada en BD**: el cast `encrypted` de
   Eloquent cifra la clave con el `APP_KEY` de Laravel
   (CBC + HMAC). En BD solo se ve ciphertext. En memoria
   se ve el texto plano. En la vista se renderiza un
   campo `type="password"` vacio: el admin no puede
   leer la clave actual; tiene que escribir una nueva
   para cambiarla.

5. **Rate limit por par (user, project)**: dos cubos
   separados (mensajes por hora, sesiones por dia). Las
   claves incluyen `user_id` y `project_id`, asi un
   usuario con sesiones en varios proyectos no comparte
   cuota. Si en el futuro se quiere un limite global
   por usuario, se cambia la clave del cubo en
   `AiRateLimiter`.

6. **Sesiones personales, no compartidas**: cada sesion
   tiene un `user_id`. Admin y cliente tienen sus
   propios chats aunque esten viendo el mismo proyecto.
   Esto simplifica la UI (no hay que mezclar conversaciones
   de varios usuarios) y la autorizacion (la policy
   verifica que el dueno de la sesion coincide con el
   usuario, o que es admin). Si en el futuro se
   quisiera sesiones compartidas por equipo, se
   relaja la policy.

7. **Auto-scroll via `x-data` de Alpine**: el componente
   Livewire emite `ai-chat-message-sent` cuando llega
   una respuesta y la vista usa `$nextTick` para hacer
   scroll al fondo. La logica de scroll es UI, no de
   negocio, asi que va en Alpine como en `ChatWindow`
   de fase 5. Livewire solo se ocupa de la
   persistencia y la comunicacion con el provider.

8. **`Opencode` como caso literal del enum**: aunque
   `docs/DATA_MODEL.md` solo lista `openai` y `anthropic`,
   `TODOs.md` menciona `Opencode` (probablemente un
   typo de "openai" duplicado, o un provider OpenAI-
   compatible pensado a futuro). Se ha anadido como
   caso del enum por consistencia con `TODOs.md` y se
   ha implementado como provider OpenAI-compatible con
   `base_url` configurable. Si en una fase futura se
   quiere quitar, basta con borrar el caso del enum
   y la clase del provider.

9. **Polimorfismo de `AiConfig`**: una fila global con
   `project_id = null` sirve como fallback. La migracion
   declara un `unique` sobre `project_id` para que solo
   exista una fila global y, como maximo, una por
   proyecto. La aplicacion de este unique en SQLite
   permite multiples `null` (no deberia, pero lo hace),
   asi que en la practica el admin puede tener varias
   filas globales si las crea via tinker. La policy y
   el `resolveConfig` lo manejan: el primero que se
   encuentre activo gana.

10. **No hay streaming**: el `AiService` espera la
    respuesta completa del provider antes de devolver.
    Streaming seria una mejora para reducir el tiempo
    percibido de respuesta, pero requiere WebSockets o
    SSE, lo que el MVP no admite. En una fase futura se
    podria anadir SSE en `/portal/projects/{project}/ai/stream`
    y consumirlo desde el componente Livewire con
    `fetch` + `ReadableStream`.

11. **`tokens_used` persistido pero no mostrado**: se
    guarda para una fase futura en la que se mostrara
    un dashboard de consumo. Asi no hay que migrar la
    tabla cuando se implemente.

12. **El system prompt autogenerado no incluye docs
    privados**: solo titulos de docs publicos. La IA
    no debe tener acceso a informacion que el cliente
    no veria. Si el admin quiere que la IA sepa sobre
    un doc privado, lo deja en `public` o lo cuenta
    en el `system_prompt` custom.

## Pendiente (fuera de scope de fase 7)

- Streaming de respuestas (SSE / WebSockets).
- Dashboard de consumo de tokens por proyecto / usuario.
- Sesiones compartidas entre miembros del mismo proyecto.
- Reacciones a mensajes de la IA.
- Re-generacion del system prompt cuando cambia el
  proyecto (cache invalidation).
- Mas providers (Mistral, Gemini, etc.).
- Persistencia de "feedback" del usuario (like/dislike)
  para entrenar al bot.
- Logs de las llamadas a providers para debugging.
- Limite global de tokens por usuario, no solo por
  peticiones.

## Riesgos y notas para la fase 8 (Calendario)

- `AiConfig::active()` se llama en cada peticion al
  provider. Si en el futuro se anaden providers que
  tardan en responder, vale la pena cachear la config
  activa con `Cache::remember()` durante 1-5 minutos.
- La tabla `notifications` (anadida en fase 5) puede
  usarse para notificar al admin cuando un provider
  falla repetidamente. No se ha implementado en esta
  fase; quedaria como una mejora menor.
- El binding de los providers en `AppServiceProvider`
  no es necesario ahora, pero si en una fase futura
  se quiere mockear providers en tests especificos, el
  patron de singleton + inyeccion explicita via
  `AiService::defaultProviders()` ya lo facilita.
