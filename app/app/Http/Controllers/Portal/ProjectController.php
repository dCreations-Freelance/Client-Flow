<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vistas del portal de cliente para proyectos.
 *
 * El cliente solo ve los proyectos de sus organizaciones, siempre
 * que esten marcados como visibles y no esten archivados. Las
 * politicas de `ProjectPolicy` se combinan con scopes del modelo
 * para garantizar el aislamiento.
 */
class ProjectController extends Controller
{
    /**
     * Listado de proyectos visibles al cliente en sus organizaciones.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->visibleToClient()
            ->forUser($request->user())
            ->with('organization')
            ->latest()
            ->paginate(15);

        // Calculamos en una sola vuelta los mensajes no leidos
        // para evitar N queries en el listado.
        $unreadByProject = $this->unreadCountsFor($projects->pluck('id')->all(), $request->user()->id);

        return view('portal.projects.index', [
            'projects' => $projects,
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
     * Detalle de un proyecto. La policy ya valida que el cliente sea
     * miembro de la organizacion y que el proyecto este visible.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['organization', 'members']);

        return view('portal.projects.show', [
            'project' => $project,
        ]);
    }

    /**
     * Detalle de una organizacion desde la perspectiva del cliente:
     * nombre, descripcion y proyectos visibles.
     *
     * @param  \App\Models\Organization  $organization
     * @return \Illuminate\View\View
     */
    public function showOrganization(Organization $organization): View
    {
        $this->authorize('view', $organization);

        $projects = $organization->projects()
            ->visibleToClient()
            ->orderBy('name')
            ->get();

        return view('portal.organizations.show', [
            'organization' => $organization,
            'projects' => $projects,
        ]);
    }
}
