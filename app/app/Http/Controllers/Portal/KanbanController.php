<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vistas del portal cliente para el kanban.
 *
 * El cliente solo ve proyectos visibles y dentro de ellos solo
 * puede leer. Las cards no son draggables y no hay botones de
 * creacion, edicion o movimiento. Esta vista envuelve el mismo
 * componente Livewire que el admin pero sin los handlers de
 * mutacion.
 */
class KanbanController extends Controller
{
    /**
     * Tablero kanban en modo lectura.
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['organization', 'members']);

        return view('portal.projects.board', [
            'project' => $project,
        ]);
    }

    /**
     * Detalle de una tarea para el portal.
     */
    public function showTask(Request $request, Project $project, Task $task): View
    {
        $this->authorize('view', $task);

        $task->load(['assignee', 'creator', 'subtasks', 'parent']);

        return view('portal.projects.tasks.show', [
            'project' => $project,
            'task' => $task,
        ]);
    }
}
