<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MoveTaskRequest;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Services\Activity\ActivityLogger;
use App\Services\TaskMoveService;
use Illuminate\Http\RedirectResponse;

/**
 * Endpoint dedicado para mover tareas entre columnas. Usa el
 * servicio `TaskMoveService` que encapsula el renumerado y la
 * sincronizacion de `completed_at`.
 */
class TaskMoveController extends Controller
{
    public function __construct(
        private TaskMoveService $mover,
    ) {
    }

    /**
     * Mueve la tarea a la columna y posicion indicadas.
     */
    public function update(MoveTaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('move', $task);

        $columnId = (int) $request->integer('column_id');
        $position = (int) $request->integer('position');

        $column = BoardColumn::find($columnId);
        if ($column === null) {
            return back()->withErrors(['column_id' => 'La columna no existe.']);
        }

        // Capturamos la columna origen ANTES de mover la tarea
        // para poder registrarla en el feed como `from_column`.
        // Si la tarea ya esta en la columna destino (es un
        // reorden dentro de la misma columna), `from_column` y
        // `to_column` coinciden y el feed aun asi lo registra
        // (es un cambio de posicion, no de estado).
        $oldColumn = $task->column;

        try {
            $this->mover->move($task, $column, $position);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['column_id' => $e->getMessage()]);
        }

        // Doble persistencia: feed (activity_log) y chat.
        // `ActivityLogger::taskMoved` recibe la columna origen
        // para guardar el diff completo en `properties`.
        app(ActivityLogger::class)->taskMoved(
            $project,
            $task,
            $column,
            $oldColumn,
            $request->user(),
        );

        return back()->with('status', 'Tarea movida.');
    }
}
