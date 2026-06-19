<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vista del calendario de un proyecto en el panel admin.
 *
 * El grueso de la logica interactiva (navegacion, queries,
 * modal, persistencia) vive en el componente Livewire compartido
 * `Shared\CalendarView`. Este controlador solo renderiza la
 * vista estatica y pasa el proyecto.
 */
class ProjectCalendarController extends Controller
{
    /**
     * Muestra el calendario del proyecto. La autorizacion la
     * hace la policy via el componente Livewire, pero la
     * hacemos aqui tambien para que un acceso directo por URL
     * no devuelva 500 si algo del componente falla.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        return view('admin.projects.calendar', [
            'project' => $project,
        ]);
    }
}
