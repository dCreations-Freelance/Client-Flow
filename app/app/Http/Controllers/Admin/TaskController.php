<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaskRequest;
use App\Http\Requests\Admin\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\TaskAssigned;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CRUD de tareas del proyecto.
 *
 * El cliente no tiene acceso a este controlador: vive detras del
 * grupo de rutas admin y la policy `TaskPolicy` rechaza a
 * clientes. Las acciones adicionales (mover, completar, reabrir)
 * estan en `TaskMoveController` y los metodos `complete`/`reopen`
 * de este controlador.
 */
class TaskController extends Controller
{
    /**
     * Crea una tarea en la columna indicada. La posicion se asigna
     * al final de la columna.
     */
    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $data = $request->validated();
        $data['project_id'] = $project->id;
        $data['created_by'] = $request->user()->id;

        // Validar columna del proyecto.
        $column = $project->columns()->find($data['column_id']);
        if ($column === null) {
            return back()->withErrors(['column_id' => 'La columna no pertenece al proyecto.']);
        }

        // Validar parent (si se informo) pertenece al mismo proyecto.
        if (! empty($data['parent_id'])) {
            $parent = Task::find($data['parent_id']);
            if ($parent === null || $parent->project_id !== $project->id) {
                return back()->withErrors(['parent_id' => 'La tarea padre debe pertenecer al mismo proyecto.']);
            }
        }

        // Validar asignado es miembro del proyecto.
        if (! empty($data['assignee_id'])) {
            $isMember = $project->members()->where('users.id', $data['assignee_id'])->exists();
            if (! $isMember) {
                return back()->withErrors(['assignee_id' => 'El usuario asignado debe ser miembro del proyecto.']);
            }
        }

        $data['position'] = (int) Task::where('column_id', $column->id)
            ->whereNull('parent_id')
            ->max('position') + 1;

        $task = Task::create($data);

        // Doble persistencia: el feed (activity_log) recibe el
        // evento estructurado y el chat recibe el system message
        // correspondiente. `ActivityLogger` se encarga de ambas
        // escrituras.
        app(ActivityLogger::class)->taskCreated($project, $task, $request->user());

        // Si la tarea se ha creado con un assignee, le enviamos
        // la notificacion de "tarea asignada". Pasamos por el
        // dispatcher para respetar el opt-out por canal.
        if (! empty($data['assignee_id']) && (int) $data['assignee_id'] !== (int) $request->user()->id) {
            $assignee = \App\Models\User::find($data['assignee_id']);
            if ($assignee !== null) {
                NotificationDispatcher::dispatch(
                    $assignee,
                    new TaskAssigned($task, $project, $request->user()),
                    NotificationEvent::TaskAssigned,
                );
            }
        }

        return back()->with('status', 'Tarea creada.');
    }

    /**
     * Actualiza los campos editables de una tarea. No permite
     * cambiar de columna aqui: para eso esta `TaskMoveController`.
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        // Si se especifica column_id, validar que pertenece al proyecto.
        if (! empty($data['column_id'])) {
            $column = $project->columns()->find($data['column_id']);
            if ($column === null) {
                return back()->withErrors(['column_id' => 'La columna no pertenece al proyecto.']);
            }
        } else {
            unset($data['column_id']);
        }

        // Validar parent.
        if (! empty($data['parent_id'])) {
            $parent = Task::find($data['parent_id']);
            if ($parent === null || $parent->project_id !== $project->id || $parent->id === $task->id) {
                return back()->withErrors(['parent_id' => 'La tarea padre no es valida.']);
            }
        }

        // Validar assignee.
        if (! empty($data['assignee_id'])) {
            $isMember = $project->members()->where('users.id', $data['assignee_id'])->exists();
            if (! $isMember) {
                return back()->withErrors(['assignee_id' => 'El usuario asignado debe ser miembro del proyecto.']);
            }
        }

        // Detectamos el cambio de assignee para disparar la
        // notificacion solo si el nuevo assignee es distinto al
        // actual. Asi un update que no toca el assignee (por
        // ejemplo, solo cambia la prioridad) no vuelve a notificar.
        $previousAssigneeId = $task->assignee_id !== null ? (int) $task->assignee_id : null;
        $newAssigneeId = array_key_exists('assignee_id', $data) && $data['assignee_id'] !== null
            ? (int) $data['assignee_id']
            : null;
        $assigneeChanged = $previousAssigneeId !== $newAssigneeId;

        // Capturamos el diff de campos trackeables para emitir
        // un `TaskUpdated` en el feed (no genera system message
        // en el chat, solo aparece en el feed admin). Asi un
        // cambio de prioridad o de fecha limite queda trazado
        // sin saturar el chat.
        $trackedFields = ['title', 'description', 'priority', 'type', 'due_date', 'assignee_id'];
        $changes = [];
        foreach ($trackedFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $oldValue = $task->{$field};
            $newValue = $data[$field];

            // Normalizamos la representacion a string para
            // comparar de forma estable. Importante para los
            // enums: compararlos por su `->value` evita que un
            // cambio `Medium` -> `medium` se detecte como
            // distinto (seria ruido en el feed).
            $oldString = $oldValue instanceof \BackedEnum ? (string) $oldValue->value : (string) ($oldValue ?? '');
            $newString = $newValue instanceof \BackedEnum ? (string) $newValue->value : (string) ($newValue ?? '');

            if ($oldString !== $newString) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        $task->update($data);

        if ($changes !== []) {
            app(ActivityLogger::class)->taskUpdated(
                $project,
                $task->fresh(),
                $request->user(),
                $changes,
            );
        }

        if ($assigneeChanged && $newAssigneeId !== null && $newAssigneeId !== (int) $request->user()->id) {
            $assignee = \App\Models\User::find($newAssigneeId);
            if ($assignee !== null) {
                NotificationDispatcher::dispatch(
                    $assignee,
                    new TaskAssigned($task->fresh(), $project, $request->user()),
                    NotificationEvent::TaskAssigned,
                );
            }
        }

        return back()->with('status', 'Tarea actualizada.');
    }

    /**
     * Elimina una tarea. Las subtareas se eliminan en cascade por
     * la FK de `parent_id`.
     */
    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $title = $task->title;
        $actor = request()->user();

        DB::transaction(function () use ($task): void {
            $columnId = $task->column_id;
            $position = $task->position;
            $task->delete();

            // Compactar las posiciones restantes en la columna.
            Task::where('column_id', $columnId)
                ->whereNull('parent_id')
                ->where('position', '>', $position)
                ->decrement('position');
        });

        // Tras borrarla registramos el evento en el feed. El
        // `taskDeleted` recibe el titulo (string) porque la
        // tarea ya no existe en BD.
        app(ActivityLogger::class)->taskDeleted($project, $title, $actor);

        return back()->with('status', 'Tarea eliminada.');
    }

    /**
     * Muestra la vista de detalle de una tarea con
     * todos sus componentes interactivos: registro
     * de tiempo, adjuntos, subtareas, etc. Pensada
     * para que el admin gestione una tarea
     * concreta sin volver al tablero kanban.
     *
     * Verifica que la tarea pertenece al proyecto
     * (defensa en profundidad contra manipulacion
     * de IDs en la URL).
     *
     * @return \Illuminate\View\View
     */
    public function show(Project $project, Task $task): View
    {
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }

        $this->authorize('view', $task);

        $task->load(['project.organization', 'assignee', 'column']);

        return view('admin.projects.tasks.show', [
            'project' => $project,
            'task' => $task,
        ]);
    }

    /**
     * Marca la tarea como completada.
     */
    public function complete(Project $project, Task $task): RedirectResponse
    {
        $this->authorize('complete', $task);

        $task->markCompleted();

        app(ActivityLogger::class)->taskCompleted($project, $task, request()->user());

        return back()->with('status', 'Tarea completada.');
    }

    /**
     * Re-abre una tarea completada.
     */
    public function reopen(Project $project, Task $task): RedirectResponse
    {
        $this->authorize('reopen', $task);

        $task->markPending();

        app(ActivityLogger::class)->taskReopened($project, $task, request()->user());

        return back()->with('status', 'Tarea re-abierta.');
    }
}
