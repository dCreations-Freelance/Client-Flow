<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pagina principal del kanban de un proyecto. Renderiza la vista
 * estatica que contiene el componente Livewire del tablero.
 *
 * La logica interactiva (drag&drop, filtros, gestion de
 * columnas) vive en `Livewire\Admin\Kanban\KanbanBoard` para
 * mantener el controlador ligero.
 */
class KanbanController extends Controller
{
    /**
     * Muestra el tablero kanban del proyecto.
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['organization', 'members']);

        return view('admin.projects.board', [
            'project' => $project,
        ]);
    }
}
