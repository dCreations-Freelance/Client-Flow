<?php

namespace App\Services;

use App\Models\BoardColumn;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Logica de movimiento de tareas entre columnas y posiciones.
 *
 * Encapsula las reglas de negocio del kanban:
 *  - La columna destino debe pertenecer al mismo proyecto.
 *  - Al cambiar de columna, se recalculan las posiciones de las
 *    tareas afectadas (origen y destino) para mantener el orden.
 *  - Si la tarea entra en una columna marcada como "done"
 *    (ultima columna por defecto), se marca como completada.
 *  - Si la tarea sale de la columna done, se re-abre.
 *
 * La deteccion de "columna done" se hace por la posicion: la
 * ultima columna del proyecto es la de done. Esto evita tener
 * que anadir un flag explicito y mantiene la logica declarativa.
 */
class TaskMoveService
{
    /**
     * Mueve la tarea a otra columna y/o posicion.
     *
     * @param  Task  $task
     * @param  BoardColumn  $targetColumn
     * @param  int  $targetPosition  posicion 0-based dentro de la columna destino
     * @return void
     */
    public function move(Task $task, BoardColumn $targetColumn, int $targetPosition): void
    {
        if ($task->project_id !== $targetColumn->project_id) {
            throw new \InvalidArgumentException('La columna destino pertenece a otro proyecto.');
        }

        DB::transaction(function () use ($task, $targetColumn, $targetPosition): void {
            $sourceColumnId = $task->column_id;
            $taskId = $task->id;

            // 1. Mover la tarea a la nueva columna y posicion.
            $task->update([
                'column_id' => $targetColumn->id,
                'position' => $targetPosition,
            ]);

            // 2. Renormalizar posiciones de la columna origen
            //    (compactar huecos).
            $this->compactPositionsInColumn($sourceColumnId);

            // 3. Renormalizar posiciones de la columna destino,
            //    dejando hueco en $targetPosition para la tarea.
            $this->makeRoomInColumn($targetColumn->id, $targetPosition, $taskId);

            // 4. Sincronizar completed_at con la columna done.
            $this->syncCompletedAt($task);
        });
    }

    /**
     * Compacta las posiciones de una columna para que sean
     * consecutivas (0, 1, 2, ...). Se aplica a la columna origen
     * despues de sacar la tarea.
     */
    private function compactPositionsInColumn(int $columnId): void
    {
        $tasks = Task::where('column_id', $columnId)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get();

        foreach ($tasks as $index => $task) {
            if ($task->position !== $index) {
                $task->update(['position' => $index]);
            }
        }
    }

    /**
     * Deja un hueco en $position para la tarea movida y empuja el
     * resto. Se aplica a la columna destino.
     */
    private function makeRoomInColumn(int $columnId, int $position, int $taskId): void
    {
        $tasks = Task::where('column_id', $columnId)
            ->whereNull('parent_id')
            ->where('id', '!=', $taskId)
            ->orderBy('position')
            ->get();

        foreach ($tasks as $index => $task) {
            $desired = $index >= $position ? $index + 1 : $index;
            if ($task->position !== $desired) {
                $task->update(['position' => $desired]);
            }
        }

        // Finalmente, asegurarse de que la tarea movida esta en su
        // posicion exacta.
        $moved = Task::find($taskId);
        if ($moved && $moved->position !== $position) {
            $moved->update(['position' => $position]);
        }
    }

    /**
     * Marca o desmarca la tarea como completada segun la columna
     * "done" del proyecto. La columna done es la de mayor position.
     */
    private function syncCompletedAt(Task $task): void
    {
        $maxPosition = (int) BoardColumn::where('project_id', $task->project_id)->max('position');
        $targetColumnPosition = (int) BoardColumn::where('id', $task->column_id)->value('position');

        if ($targetColumnPosition === $maxPosition) {
            $task->markCompleted();
        } else {
            $task->markPending();
        }
    }
}
