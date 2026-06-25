<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Services\Activity\ActivityLogger;
use App\Services\DefaultBoardColumnsService;
use App\Services\Project\ProjectSummaryService;
use App\Services\ProjectTemplate\ProjectTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD de proyectos del panel de administracion.
 *
 * Las autorizaciones pasan por `ProjectPolicy`. El admin actual no
 * se anade automaticamente al equipo: queda como responsable via la
 * organizacion (owner), y los miembros del proyecto se gestionan
 * desde rutas anidadas.
 */
class ProjectController extends Controller
{
    /**
     * Listado paginado con busqueda por nombre y filtros por
     * organizacion y estado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $search = trim((string) $request->query('search', ''));
        $organizationId = (string) $request->query('organization_id', '');
        $status = (string) $request->query('status', '');

        $projects = Project::query()
            ->with(['organization', 'members'])
            ->withCount('members')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($organizationId !== '', fn ($q) => $q->where('organization_id', $organizationId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $organizations = Organization::orderBy('name')->get();

        // Calculamos en una sola query los mensajes no leidos del
        // usuario actual en los proyectos de la pagina actual.
        // Asi el listado no dispara N queries (una por proyecto).
        $unreadByProject = $this->unreadCountsFor($projects->pluck('id')->all(), $request->user()->id);

        return view('admin.projects.index', [
            'projects' => $projects,
            'organizations' => $organizations,
            'search' => $search,
            'organizationId' => $organizationId,
            'status' => $status,
            'unreadByProject' => $unreadByProject,
        ]);
    }

    /**
     * Calcula el numero de mensajes no leidos por proyecto para
     * un usuario en una sola query agregada. Si el usuario no
     * tiene marcador en un proyecto, todos sus mensajes cuentan
     * como no leidos.
     *
     * @param  array<int, int>  $projectIds
     * @return array<int, int>  mapa project_id => unread_count
     */
    private function unreadCountsFor(array $projectIds, int $userId): array
    {
        if ($projectIds === []) {
            return [];
        }

        // Marcadores de lectura del usuario en estos proyectos.
        $reads = \App\Models\ProjectChatRead::query()
            ->whereIn('project_id', $projectIds)
            ->where('user_id', $userId)
            ->pluck('last_read_message_id', 'project_id');

        $counts = [];
        foreach ($projectIds as $projectId) {
            $lastRead = (int) ($reads[$projectId] ?? 0);
            $counts[$projectId] = \App\Models\ProjectMessage::query()
                ->where('project_id', $projectId)
                ->where('id', '>', $lastRead)
                ->count();
        }

        return $counts;
    }

    /**
     * Muestra el formulario de creacion. Solo se listan las
     * organizaciones que tienen al menos un owner definido, para
     * evitar crear proyectos sobre organizaciones huerfanas.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $this->authorize('create', Project::class);

        $organizations = Organization::orderBy('name')->get();

        return view('admin.projects.create', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Persiste el proyecto. El admin actual no se anade al equipo:
     * su relacion con el proyecto es via la organizacion. Tras la
     * creacion se generan las columnas default del kanban.
     *
     * @param  \App\Http\Requests\Admin\StoreProjectRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();
        $data['is_visible_to_client'] = $request->boolean('is_visible_to_client', true);
        $data['status'] = $data['status'] ?? \App\Enums\ProjectStatus::Planning->value;

        $project = Project::create($data);

        app(DefaultBoardColumnsService::class)->create($project);

        // Registramos la creacion en el feed (evento privado,
        // admin-only). El chat no recibe system message.
        app(ActivityLogger::class)->projectCreated($project, $request->user());

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Proyecto creado.');
    }

    // -----------------------------------------------------------------
    // Creacion de proyectos desde plantilla
    // -----------------------------------------------------------------

    /**
     * Muestra el formulario de creacion de un
     * proyecto a partir de una plantilla. El nombre
     * se pre-rellena como `"{nombre plantilla}
     * (copia)"` para que el admin solo tenga que
     * ajustarlo si quiere.
     *
     * @return \Illuminate\View\View
     */
    public function createFromTemplate(ProjectTemplate $projectTemplate): View
    {
        $this->authorize('apply', $projectTemplate);
        $this->authorize('create', Project::class);

        $organizations = Organization::orderBy('name')->get();

        return view('admin.projects.create-from-template', [
            'template' => $projectTemplate->load('creator'),
            'organizations' => $organizations,
            'suggestedName' => $projectTemplate->name.' (copia)',
        ]);
    }

    /**
     * Crea el proyecto a partir de la plantilla:
     * 1. Crea el proyecto (con los 4 columnas por
     *    defecto, igual que el flujo normal).
     * 2. Sobrescribe las columnas por defecto con
     *    las de la plantilla, junto con las tareas
     *    y los documentos, via
     *    `ProjectTemplateService::applyToProject`.
     *
     * @return RedirectResponse
     */
    public function storeFromTemplate(Request $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $this->authorize('apply', $projectTemplate);
        $this->authorize('create', Project::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', 'in:planning,in_progress,on_hold,waiting_client,completed,archived'],
            'is_visible_to_client' => ['nullable', 'boolean'],
        ]);

        // Por defecto, las plantillas se crean en
        // estado `planning` y visibles al cliente.
        $data['is_visible_to_client'] = $request->boolean('is_visible_to_client', true);
        $data['status'] = $data['status'] ?? \App\Enums\ProjectStatus::Planning->value;

        $project = Project::create($data);

        // Aplica la plantilla encima de las columnas
        // por defecto. `applyToProject` es la unica
        // fuente de verdad de la copia.
        $result = app(ProjectTemplateService::class)->applyToProject(
            $projectTemplate,
            $project,
            $request->user(),
        );

        // Registramos la aplicacion de la plantilla en el feed
        // (evento privado). Lo hacemos despues de aplicar para
        // que el `template_id` este disponible en `properties`.
        app(ActivityLogger::class)->templateApplied(
            $project,
            $projectTemplate,
            $request->user(),
        );

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', sprintf(
                'Proyecto creado desde la plantilla "%s": %d columnas, %d tareas y %d documentos.',
                $projectTemplate->name,
                $result['columns'],
                $result['tasks'],
                $result['documents'],
            ));
    }

    /**
     * Detalle del proyecto (hub).
     *
     * Carga el snapshot precalculado via `ProjectSummaryService`
     * para que la vista no lance queries adicionales, y resuelve
     * los miembros de la organizacion que aun no estan en el
     * proyecto (necesarios para el componente Livewire de gestion
     * de miembros).
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $summary = app(ProjectSummaryService::class)
            ->loadForAdmin($project, request()->user());

        $availableMembers = $project->organization
            ->members()
            ->orderBy('name')
            ->get()
            ->diff($summary->project->members);

        return view('admin.projects.show', [
            'summary' => $summary,
            'availableMembers' => $availableMembers,
        ]);
    }

    /**
     * Muestra el formulario de edicion.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        $organizations = Organization::orderBy('name')->get();

        return view('admin.projects.edit', [
            'project' => $project,
            'organizations' => $organizations,
        ]);
    }

    /**
     * Actualiza el proyecto. Si el status pasa a `archived`
     * sincronizamos `archived_at` para mantener consistencia con
     * la accion dedicada de archivar.
     *
     * @param  \App\Http\Requests\Admin\UpdateProjectRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validated();
        $data['is_visible_to_client'] = $request->boolean('is_visible_to_client');

        // Capturamos el status anterior ANTES de aplicar la
        // actualizacion para detectar la transicion. Si cambia,
        // emitimos un `StatusChanged` en el feed (no en el chat).
        $previousStatus = $project->status;

        $status = \App\Enums\ProjectStatus::from($data['status']);
        if ($status === \App\Enums\ProjectStatus::Archived && ! $project->isArchived()) {
            $data['archived_at'] = now();
        } elseif ($status !== \App\Enums\ProjectStatus::Archived && $project->isArchived()) {
            $data['archived_at'] = null;
        }

        $project->update($data);

        if ($project->status !== $previousStatus) {
            app(ActivityLogger::class)->statusChanged(
                $project,
                $previousStatus,
                $project->status,
                $request->user(),
            );
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Proyecto actualizado.');
    }

    /**
     * Elimina el proyecto. Las relaciones (miembros, tareas, etc)
     * se eliminan en cascada por las FKs.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Proyecto eliminado.');
    }
}
