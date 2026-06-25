<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

/**
 * Vista del feed de actividad de un proyecto en el panel
 * admin.
 *
 * El componente `Shared\ProjectActivityFeed` concentra toda
 * la logica de carga, filtros y paginacion. Este controlador
 * solo autoriza el acceso y monta el componente en modo
 * `portalMode = false` (admin ve todos los eventos, incluidos
 * los privados como `task_updated`, `member_added`, etc).
 */
class ProjectActivityController extends Controller
{
    /**
     * Muestra el feed de actividad del proyecto.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Project $project): View
    {
        $this->authorize('view', $project);

        return view('admin.projects.activity', [
            'project' => $project,
        ]);
    }
}
