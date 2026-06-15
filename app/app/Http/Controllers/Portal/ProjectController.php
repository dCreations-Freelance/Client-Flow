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

        return view('portal.projects.index', [
            'projects' => $projects,
        ]);
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
