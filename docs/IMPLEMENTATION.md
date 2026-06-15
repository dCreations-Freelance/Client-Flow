# IMPLEMENTATION.md — Convenciones y patrones de implementacion

## Estructura de archivos

### Directorios principiales

```txt
app/
├── Console/Commands/          # Comandos artisan custom
├── DTOs/                       # Data Transfer Objects
├── Enums/                      # Enums del dominio (UserRole, ProjectStatus, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # Panel administrador
│   │   ├── Portal/             # Portal cliente
│   │   └── Auth/               # Login, registro, invitacion
│   ├── Middleware/
│   │   ├── EnsureUserIsAdmin.php
│   │   └── EnsureUserIsClient.php
│   └── Requests/               # Form requests con validacion
│       ├── Admin/
│       └── Portal/
├── Livewire/
│   ├── Admin/                  # Componentes Livewire admin
│   │   ├── Organization/
│   │   ├── Project/
│   │   ├── Kanban/
│   │   ├── Chat/
│   │   ├── Calendar/
│   │   └── AgentTemplate/
│   ├── Portal/                 # Componentes Livewire portal
│   │   ├── Project/
│   │   ├── Chat/
│   │   ├── Calendar/
│   │   └── AiChat/
│   └── Shared/                 # Componentes compartidos
│       ├── NotificationBadge.php
│       └── SearchInput.php
├── Models/                     # Modelos Eloquent
├── Policies/                   # Policies de autorizacion
├── Services/                   # Logica de negocio reutilizable
│   ├── Ai/                     # Servicio de IA
│   │   ├── AiService.php
│   │   ├── Providers/
│   │   │   ├── OpenAiProvider.php
│   │   │   └── AnthropicProvider.php
│   │   └── AiProviderInterface.php
│   ├── Mcp/                    # MCP server logic
│   │   ├── McpServer.php
│   │   └── Tools/
│   │       ├── ListProjectsTool.php
│   │       ├── GetProjectTool.php
│   │       └── ...
│   └── Notification/           # Servicio de notificaciones
│       └── NotificationService.php
├── Notifications/              # Notificaciones Laravel
│   ├── ProjectMessageSent.php
│   ├── TaskAssigned.php
│   └── OrganizationInvitationSent.php
└── ViewModels/                 # ViewModels complejos (opcional)
```

### Vistas Blade

```txt
resources/views/
├── components/
│   ├── layouts/
│   │   ├── admin.blade.php
│   │   ├── portal.blade.php
│   │   └── auth.blade.php
│   ├── ui/                     # Componentes Blade reutilizables
│   │   ├── badge.blade.php
│   │   ├── button.blade.php
│   │   ├── card.blade.php
│   │   ├── input.blade.php
│   │   ├── modal.blade.php
│   │   ├── select.blade.php
│   │   └── textarea.blade.php
│   └── project-status-badge.blade.php
├── partials/
│   ├── admin-sidebar.blade.php
│   ├── portal-sidebar.blade.php
│   └── notification-badge.blade.php
├── admin/
│   ├── dashboard.blade.php
│   ├── organizations/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── show.blade.php
│   │   └── edit.blade.php
│   ├── projects/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── show.blade.php
│   ├── agent-templates/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── show.blade.php
│   └── settings/
│       ├── ai.blade.php
│       └── api-tokens.blade.php
├── portal/
│   ├── dashboard.blade.php
│   ├── organizations/
│   │   └── show.blade.php
│   └── projects/
│       ├── show.blade.php
│       ├── board.blade.php
│       ├── documents.blade.php
│       ├── chat.blade.php
│       ├── ai.blade.php
│       └── calendar.blade.php
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── invitation.blade.php
│   ├── password-request.blade.php
│   └── password-reset.blade.php
└── welcome.blade.php
```

### Tests

```txt
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── RegistrationTest.php
│   │   └── InvitationAcceptanceTest.php
│   ├── Admin/
│   │   ├── OrganizationManagementTest.php
│   │   ├── ProjectManagementTest.php
│   │   ├── TaskManagementTest.php
│   │   ├── DocumentManagementTest.php
│   │   ├── AgentTemplateTest.php
│   │   └── AiSettingsTest.php
│   ├── Portal/
│   │   ├── DashboardTest.php
│   │   ├── ProjectViewTest.php
│   │   ├── DocumentViewTest.php
│   │   └── ChatTest.php
│   └── Mcp/
│       └── McpServerTest.php
├── Unit/
│   ├── Models/
│   │   ├── OrganizationTest.php
│   │   ├── ProjectTest.php
│   │   └── TaskTest.php
│   └── Services/
│       ├── AiServiceTest.php
│       └── McpServerTest.php
└── TestCase.php
```

---

## Naming conventions

### Modelos

- Singular, PascalCase: `Organization`, `Project`, `BoardColumn`, `Task`.
- Relaciones en camelCase: `organization()`, `projects()`, `boardColumns()`.
- Scopes en camelCase con prefijo `scope`: `scopeVisible($query)`, `scopeByOrganization($query, $orgId)`.

### Controladores

- Plural, PascalCase en `Http/Controllers/`: `Admin/OrganizationController`, `Portal/ProjectController`.
- Metodos REST: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Controladores que sirven vistas + procesan formularios (clasicos) cuando no hay logica compleja.
- Cuando la vista necesita interactividad (kanban, chat, calendario), usar Livewire components.

### Componentes Livewire

- PascalCase en `Livewire/`: `Admin/Kanban/Board`, `Portal/Chat/MessageList`.
- Nombre del componente Blade: `livewire.admin.kanban.board`.
- Usar Livewire para: kanban (drag & drop), chat (polling), formularios interactivos, filtros en tiempo real.
- No usar Livewire para: paginas estaticas de solo lectura (usar Blade normal).

### Form Requests

- PascalCase con sufijo `Request`: `StoreOrganizationRequest`, `UpdateProjectRequest`, `CreateTaskRequest`.
- Ubicacion: `Http/Requests/Admin/` y `Http/Requests/Portal/`.
- Contienen reglas de validacion y autorizacion.

### Enums

- Ubicacion: `app/Enums/`.
- Backed enums con string: `enum UserRole: string { case Admin = 'admin'; case Client = 'client'; }`.
- Metodos utiles dentro del enum: `labels()`, `colors()`, `badge()`.

### Rutas

- Prefijo por zona: `/admin/*`, `/portal/*`, `/api/mcp/*`.
- Names con prefijo: `admin.organizations.index`, `portal.projects.show`.
- Resource routes cuando aplique: `Route::resource('organizations', OrganizationController::class)`.

### Migraciones

- Nombres descriptivos: `create_organizations_table`, `add_parent_id_to_tasks_table`.
- Ordenar correctamente con timestamps.

### Migrations

Los timestamps de migraciones deben seguir el patron:
- Migraciones del framework: `0001_01_01_000000_...` (ya existentes).
- Migraciones custom: `YYYY_MM_DD_HHMMSS_descripcion_descriptiva.php`.

### Factories

- Un factory por modelo: `OrganizationFactory`, `ProjectFactory`, `TaskFactory`.
- Relaciones en el factory: usar callbacks o metodos `for()` / `has()`.

### Seeders

- `DatabaseSeeder` principal que llama a otros seeders.
- Seeders especificos: `OrganizationSeeder`, `ProjectSeeder`, `TaskSeeder`.
- Datos realistas en espanol para el seeder.

---

## Patrones de implementacion

### Autorizacion

Siempre usar Policies. Nunca verificar permisos directamente en el controlador.

```php
// Policy generada por modelo
php artisan make:policy ProjectPolicy --model=Project

// En controlador
public function show(Project $project)
{
    $this->authorize('view', $project);
    // ...
}

// En Livewire
public function mount(Project $project)
{
    $this->authorize('view', $project);
}
```

Reglas por Policy:
- **OrganizationPolicy**: solo admin y miembros de la organizacion pueden ver/editar.
- **ProjectPolicy**: solo admin y miembros del proyecto pueden ver. Client solo sus organizaciones.
- **TaskPolicy**: solo admin puede crear/editar. Client puede ver las de sus proyectos.
- **ProjectDocumentPolicy**: admin ve todo. Client solo documentos public.

### Validacion

Siempre usar Form Requests en controladores. En Livewire, usar `rules()`.

```php
// Controlador con Form Request
public function store(StoreOrganizationRequest $request)
{
    $organization = Organization::create($request->validated());
    // ...
}

// Livewire con rules()
class CreateTask extends Component
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:critical,high,medium,low'],
        ];
    }
}
```

### Servicios

Crear un Service cuando la logica:
- Se reutiliza en mas de un lugar.
- Involucra mas de un modelo.
- Tiene side effects (notificaciones, eventos).

```php
namespace App\Services\Notification;

class NotificationService
{
    public function notifyProjectMessage(Project $project, User $sender, string $content): void
    {
        $recipients = $project->members()
            ->where('id', '!=', $sender->id)
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new ProjectMessageSent($project, $sender));
        }
    }
}
```

Servicios nunca devuelven vistas. Solo procesan datos y devuelven modelos, DTOs o void.

### Enums con metodos utiles

```php
enum ProjectStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case WaitingClient = 'waiting_client';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Planning => 'Planificacion',
            self::InProgress => 'En progreso',
            self::OnHold => 'En pausa',
            self::WaitingClient => 'Esperando cliente',
            self::Completed => 'Completado',
            self::Archived => 'Archivado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Planning => 'blue',
            self::InProgress => 'yellow',
            self::OnHold => 'gray',
            self::WaitingClient => 'orange',
            self::Completed => 'green',
            self::Archived => 'gray',
        };
    }

    public function badgeClasses(): string
    {
        return match($this->color()) {
            'blue' => 'bg-[#EFF6FF] text-[#2563EB]',
            'yellow' => 'bg-[#FFFBEB] text-[#D97706]',
            'green' => 'bg-[#F0FDF4] text-[#16A34A]',
            'orange' => 'bg-[#FFFBEB] text-[#D97706]',
            default => 'bg-[#F4F1EA] text-[#6B7280]',
        };
    }
}
```

### Relaciones Eloquent

Definir siempre las relaciones en ambos sentidos cuando tenga sentido:

```php
// Organization.php
public function projects(): HasMany
{
    return $this->hasMany(Project::class);
}

// Project.php
public function organization(): BelongsTo
{
    return $this->belongsTo(Organization::class);
}
```

Usar scopes para consultas frecuentes:

```php
// Project.php
public function scopeVisibleToClient($query)
{
    return $query->where('is_visible_to_client', true);
}

public function scopeActive($query)
{
    return $query->whereNotIn('status', ['archived', 'completed']);
}
```

### Blade components

Componentes Blade anonimos para UI reutilizable en `resources/views/components/ui/`:

```html
<!-- resources/views/components/ui/badge.blade.php -->
@props(['color' => 'blue'])
@php
    $classes = match($color) {
        'blue' => 'bg-[#EFF6FF] text-[#2563EB]',
        'green' => 'bg-[#F0FDF4] text-[#16A34A]',
        'orange' => 'bg-[#FFFBEB] text-[#D97706]',
        'red' => 'bg-[#FEF2F2] text-[#DC2626]',
        default => 'bg-[#F4F1EA] text-[#6B7280]',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
```

Uso:
```blade
<x-ui:badge :color="$status->color()">{{ $status->label() }}</x-ui:badge>
```

### Livewire patterns

**Formularios con Form Objects** (cuando el formulario es complejo):

```php
// app/Livewire/Admin/Project/CreateProject.php
class CreateProject extends Component
{
    public ?int $organization_id = null;
    public string $name = '';
    public string $description = '';
    public string $status = 'planning';
    // ...

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            // ...
        ];
    }

    public function save(): RedirectResponse
    {
        $this->validate();
        $project = Project::create($this->all());
        return redirect()->route('admin.projects.show', $project);
    }

    public function render(): View
    {
        return view('livewire.admin.project.create-project');
    }
}
```

**Kanban con Livewire** (componente principal):

```php
// app/Livewire/Admin/Kanban/Board.php
class Board extends Component
{
    public Project $project;
    public ?int $dragTaskId = null;
    public ?int $dragToColumnId = null;

    protected $listeners = ['taskMoved' => 'onTaskMoved'];

    public function onTaskMoved(int $taskId, int $columnId, int $position): void
    {
        $task = Task::find($taskId);
        $this->authorize('update', $task);
        $task->update([
            'column_id' => $columnId,
            'position' => $position,
        ]);
    }

    public function render(): View
    {
        $columns = $this->project->columns()
            ->with(['tasks' => fn($q) => $q->ordered()])
            ->ordered()
            ->get();

        return view('livewire.admin.kanban.board', compact('columns'));
    }
}
```

**Chat con polling** (no WebSocket en MVP):

```php
// app/Livewire/Portal/Chat/MessageList.php
class MessageList extends Component
{
    public Project $project;
    public string $newMessage = '';

    protected $listeners = ['refreshMessages' => '$refresh'];

    public function mount(): void
    {
        $this->authorize('view', $this->project);
    }

    public function sendMessage(): void
    {
        $this->validate(['newMessage' => 'required|string|max:2000']);
        ProjectMessage::create([
            'project_id' => $this->project->id,
            'user_id' => auth()->id(),
            'content' => $this->newMessage,
            'type' => MessageType::Text,
        ]);
        $this->newMessage = '';
        $this->dispatch('refreshMessages');
    }

    public function render(): View
    {
        $messages = $this->project->messages()
            ->with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse();

        return view('livewire.portal.chat.message-list', compact('messages'));
    }
}
```

Polling en la vista:
```blade
<div wire:poll.5s="refreshMessages">
    <!-- mensajes -->
</div>
```

---

## Testing

### Estructura

- Un test file por modelo/controlador/feature.
- `Feature/` para tests de integracion (HTTP, Livewire, flujos completos).
- `Unit/` para tests de modelos, servicios, enums.
- Usar `RefreshDatabase` en todos los tests de feature.

### Patron

```php
test('admin can create organization', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post('/admin/organizations', [
            'name' => 'Acme Corp',
            'description' => 'A test organization',
        ]);

    $response->assertRedirect('/admin/organizations/1');
    $this->assertDatabaseHas('organizations', ['name' => 'Acme Corp']);
});

test('client cannot access admin dashboard', function () {
    $client = User::factory()->client()->create();

    $response = $this->actingAs($client)
        ->get('/admin/dashboard');

    $response->assertForbidden();
});

test('client can only see projects from their organizations', function () {
    $client = User::factory()->client()->create();
    $org = Organization::factory()->create();
    $org->members()->attach($client);
    $visibleProject = Project::factory()->create(['organization_id' => $org->id]);
    $hiddenProject = Project::factory()->create(); // otra org

    $response = $this->actingAs($client)
        ->get('/portal/dashboard');

    $response->assertSee($visibleProject->name);
    $response->assertDontSee($hiddenProject->name);
});
```

### Factories

```php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => UserRole::Client,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }
}
```

### Comandos utiles

```bash
# Ejecutar todos los tests
php artisan test

# Tests especificos
php artisan test --filter=OrganizationManagement
php artisan test --filter=KanbanBoard

# Con coverage
php artisan test --coverage

# Migraciones frescas con seed
php artisan migrate:fresh --seed

# Con Docker
docker compose exec app php artisan test
docker compose exec app php artisan migrate:fresh --seed
```

---

## Git workflow

### Commits

Usar conventional commits en ingles:

```
feat: add organization CRUD
feat: add kanban board component
fix: prevent client from seeing other org projects
refactor: extract notification service
docs: update DATA_MODEL.md
test: add organization policy tests
chore: update dependencies
```

### Branches

- `main` — produccion.
- `develop` — desarrollo activo.
- `feat/phase-1-auth` — por fase.
- `feat/kanban-board` — por feature granular.
- `fix/task-sorting` — por bugfix.

---

## Seguridad

### Reglas basicas

1. Nunca confiar en IDs del cliente sin policy o scope.
2. Toda ruta admin protegida con middleware `admin`.
3. Toda ruta portal protegida con middleware `client`.
4. Toda accion verificada con `$this->authorize()`.
5. NUNCA exponer API keys en el frontend. La config IA se guarda encriptada.
6. El MCP usa API tokens, no sesiones.
7. Los archivos privados se sirven mediante controlador con autorizacion, nunca desde `public/`.

### Datos encriptados

- `ai_configs.api_key` siempre encriptado con `Crypt::encrypt()`.
- Tokens de invitacion hasheados con `Str::random(64)`.
- Passwords con `Hash::make()` (default de Laravel).

---

## Performance

### MVP (sin optimizar)

- `QUEUE_CONNECTION=sync` (sin workers).
- `CACHE_STORE=database` (sin Redis).
- Livewire polling (5s) para chat, no WebSocket.
- Queries optimizadas con eager loading (`with()`) para evitar N+1.
- Paginacion simple (15 items por pagina).

### Futuro (post-MVP)

- Redis para cache y colas.
- WebSocket con Laravel Reverb o Pusher.
- Query caching en listados pesados.
- Index optimizados en BD.