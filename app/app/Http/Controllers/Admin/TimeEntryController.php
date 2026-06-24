<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TimeEntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTimeEntryRequest;
use App\Http\Requests\Admin\UpdateTimeEntryRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Http\RedirectResponse;

/**
 * CRUD HTTP de entradas de tiempo en una tarea.
 *
 * La mayor parte de la interaccion (start/stop del
 * cronometro, alta de entrada manual, edicion, borrado
 * y toggle de facturable) se hace via componente
 * Livewire (`TimeTracker` y `ProjectTimeDashboard`).
 * Este controlador expone endpoints HTTP como fallback
 * para tests, integraciones externas o un futuro
 * cliente MCP.
 *
 * El cliente del portal no tiene acceso a este
 * controlador: la policy `TimeEntryPolicy::create`
 * ya lo bloquea, y el middleware `admin` lo protege
 * a nivel de ruta.
 */
class TimeEntryController extends Controller
{
    public function __construct(
        private TimeTrackingService $timeTracking,
    ) {
    }

    /**
     * Crea una entrada manual contra una tarea. La
     * fecha de trabajo se toma del campo `entry_date`
     * (no se persiste como columna: se usa para
     * validar que no es futura; el `created_at` del
     * modelo refleja cuando se registro la entrada).
     *
     * @return RedirectResponse
     */
    public function store(StoreTimeEntryRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->ensureTaskBelongsToProject($project, $task);
        $this->authorize('create', [TimeEntry::class, $project]);

        $data = $request->validated();
        $data['type'] = TimeEntryType::Manual->value;

        $this->timeTracking->createManualEntry(
            $task,
            $request->user(),
            $data,
        );

        return back()->with('status', 'Tiempo registrado.');
    }

    /**
     * Actualiza los campos editables de una entrada.
     * La cache `total_logged_minutes` se actualiza
     * automaticamente via observer si los minutos
     * cambian.
     *
     * @return RedirectResponse
     */
    public function update(UpdateTimeEntryRequest $request, Project $project, Task $task, TimeEntry $entry): RedirectResponse
    {
        $this->ensureTaskBelongsToProject($project, $task);
        $this->ensureEntryBelongsToTask($task, $entry);
        $this->authorize('update', $entry);

        $this->timeTracking->updateEntry($entry, $request->validated());

        return back()->with('status', 'Entrada de tiempo actualizada.');
    }

    /**
     * Elimina una entrada. La cache de la tarea
     * padre se recalcula automaticamente via observer.
     *
     * @return RedirectResponse
     */
    public function destroy(Project $project, Task $task, TimeEntry $entry): RedirectResponse
    {
        $this->ensureTaskBelongsToProject($project, $task);
        $this->ensureEntryBelongsToTask($task, $entry);
        $this->authorize('delete', $entry);

        $entry->delete();

        return back()->with('status', 'Entrada de tiempo eliminada.');
    }

    /**
     * Verifica que la tarea de la URL pertenece al
     * proyecto. Si no, 404. Cierra el vector "admin
     * manipula la URL con un id de tarea ajeno".
     *
     * @return void
     */
    private function ensureTaskBelongsToProject(Project $project, Task $task): void
    {
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }
    }

    /**
     * Verifica que la entrada pertenece a la tarea
     * de la URL. Si no, 404.
     *
     * @return void
     */
    private function ensureEntryBelongsToTask(Task $task, TimeEntry $entry): void
    {
        if ((int) $entry->task_id !== (int) $task->id) {
            abort(404);
        }
    }
}
