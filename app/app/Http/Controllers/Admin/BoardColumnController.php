<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderBoardColumnsRequest;
use App\Http\Requests\Admin\StoreBoardColumnRequest;
use App\Http\Requests\Admin\UpdateBoardColumnRequest;
use App\Models\BoardColumn;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion de columnas del kanban.
 *
 * Solo accesible para admin. La lectura de columnas (necesaria
 * para renderizar el tablero) ocurre en el componente Livewire
 * del kanban, que reutiliza la policy.
 */
class BoardColumnController extends Controller
{
    /**
     * Crea una columna al final del proyecto.
     */
    public function store(StoreBoardColumnRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [BoardColumn::class, $project]);

        $nextPosition = (int) $project->columns()->max('position') + 1;

        BoardColumn::create([
            'project_id' => $project->id,
            'name' => $request->string('name')->toString(),
            'color' => $request->input('color'),
            'position' => $nextPosition,
            'is_default' => false,
        ]);

        return back()->with('status', 'Columna anadida.');
    }

    /**
     * Actualiza nombre y/o color de una columna.
     */
    public function update(UpdateBoardColumnRequest $request, Project $project, BoardColumn $column): RedirectResponse
    {
        $this->authorize('update', $column);

        $column->update([
            'name' => $request->string('name')->toString(),
            'color' => $request->input('color'),
        ]);

        return back()->with('status', 'Columna actualizada.');
    }

    /**
     * Elimina una columna. No se permite si tiene tareas para
     * evitar perdidas accidentales de datos.
     */
    public function destroy(Project $project, BoardColumn $column): RedirectResponse
    {
        $this->authorize('delete', $column);

        if ($column->tasks()->exists()) {
            return back()->withErrors([
                'column' => 'No puedes eliminar una columna con tareas. Mueve o elimina las tareas primero.',
            ]);
        }

        DB::transaction(function () use ($column): void {
            $position = $column->position;
            $projectId = $column->project_id;
            $column->delete();

            // Compactar las posiciones del resto de columnas.
            BoardColumn::where('project_id', $projectId)
                ->where('position', '>', $position)
                ->decrement('position');
        });

        return back()->with('status', 'Columna eliminada.');
    }

    /**
     * Reordena las columnas segun el array recibido.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorder(ReorderBoardColumnsRequest $request, Project $project): RedirectResponse
    {
        // La policy de reorder se aplica contra la primera columna
        // del proyecto, como representante. Esto evita tener que
        // cargar todas las columnas en el authorize.
        $firstColumn = $project->columns()->ordered()->first();
        $this->authorize('reorder', $firstColumn ?? new BoardColumn(['project_id' => $project->id]));

        $orderedIds = $request->input('columns');

        DB::transaction(function () use ($project, $orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                BoardColumn::where('project_id', $project->id)
                    ->where('id', $id)
                    ->update(['position' => $position]);
            }
        });

        return back()->with('status', 'Orden de columnas actualizado.');
    }
}
