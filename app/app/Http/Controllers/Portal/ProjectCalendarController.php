<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vista del calendario de un proyecto en el portal del cliente.
 *
 * Reutiliza el mismo componente Livewire que el panel admin,
 * montado en modo `readOnly = true` para ocultar los controles
 * de creacion, edicion y eliminacion. La autorizacion la hace
 * la policy a traves del componente.
 */
class ProjectCalendarController extends Controller
{
    /**
     * Muestra el calendario del proyecto para un cliente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        return view('portal.projects.calendar', [
            'project' => $project,
        ]);
    }
}
