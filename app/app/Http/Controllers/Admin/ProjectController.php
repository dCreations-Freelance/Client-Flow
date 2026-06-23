<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DefaultBoardColumnsService;
use App\Services\Project\ProjectSummaryService;
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

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Proyecto creado.');
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

        $status = \App\Enums\ProjectStatus::from($data['status']);
        if ($status === \App\Enums\ProjectStatus::Archived && ! $project->isArchived()) {
            $data['archived_at'] = now();
        } elseif ($status !== \App\Enums\ProjectStatus::Archived && $project->isArchived()) {
            $data['archived_at'] = null;
        }

        $project->update($data);

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
