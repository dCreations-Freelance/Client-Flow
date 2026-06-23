<?php

namespace App\Livewire\Admin\Kanban;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ProjectActivityLogger;
use App\Services\Attachments\AttachmentService;
use App\Services\TaskMoveService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente principal del tablero kanban.
 *
 * Renderiza las columnas del proyecto con sus tareas raiz, expone
 * los handlers de drag&drop (HTML5 nativo -> Livewire) y los
 * modales de creacion/edicion de tareas. Los filtros se aplican
 * en memoria para mantener la respuesta inmediata.
 *
 * El drag&drop se hace con un pequeno JS inline (en la vista
 * `kanban-board.blade.php`) que escucha `dragstart`/`dragover`/
 * `drop` y dispara `wire:method`. Sin librerias externas, sin
 * Alpine.js.
 */
class KanbanBoard extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Project $project;

    /**
     * Tareas raiz (sin parent) de cada columna, agrupadas en cliente.
     *
     * @var Collection<int, Task>
     */
    public Collection $tasks;

    /**
     * Adjuntos pendientes para la tarea que se esta creando o
     * editando. Solo se procesan en modo `create`; en `edit`
     * los adjuntos se gestionan en la vista de detalle de la
     * tarea para no romper el contrato "subida atomica" del
     * modal.
     *
     * @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[]
     */
    public array $taskFormAttachments = [];

    /**
     * Filtros: se mantienen en query string con `#[Url]` para que
     * la URL sea compartible y los tests puedan apuntar a vistas
     * filtradas.
     */
    #[Url(as: 'priority')]
    public string $priorityFilter = '';

    #[Url(as: 'assignee')]
    public ?int $assigneeFilter = null;

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'due')]
    public string $dueFilter = '';

    /**
     * Estado del modal de tarea. null = cerrado. array = abierto
     * con valores (modo crear o editar).
     *
     * @var array<string, mixed>|null
     */
    public ?array $taskForm = null;

    /**
     * Carga el proyecto con sus columnas y tareas raiz. Se hace en
     * mount para evitar N+1 al renderizar.
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->refreshTasks();
    }

    /**
     * Recarga la coleccion de tareas desde la BD. Llamado tras
     * cualquier mutacion (crear, editar, mover, eliminar).
     *
     * @return void
     */
    public function refreshTasks(): void
    {
        $this->project->load(['columns.rootTasks.assignee', 'members']);

        $allTasks = $this->project->columns->flatMap->rootTasks->values();
        $this->tasks = $allTasks;
    }

    /**
     * Devuelve las tareas de una columna ya filtradas. Pensado para
     * usar en la vista: `@foreach ($this->tasksInColumn($column) as $task)`.
     *
     * @return Collection<int, Task>
     */
    public function tasksInColumn(BoardColumn $column): Collection
    {
        return $this->tasks
            ->where('column_id', $column->id)
            ->filter(fn (Task $task) => $this->passesFilters($task))
            ->sortBy('position')
            ->values();
    }

    /**
     * Evalua si una tarea pasa los filtros activos.
     */
    private function passesFilters(Task $task): bool
    {
        if ($this->priorityFilter !== '' && $task->priority->value !== $this->priorityFilter) {
            return false;
        }

        if ($this->typeFilter !== '' && $task->type->value !== $this->typeFilter) {
            return false;
        }

        if ($this->assigneeFilter !== null && $task->assignee_id !== $this->assigneeFilter) {
            return false;
        }

        if ($this->dueFilter === 'overdue' && ! $task->isOverdue()) {
            return false;
        }

        if ($this->dueFilter === 'today' && ! $task->due_date?->isToday()) {
            return false;
        }

        if ($this->dueFilter === 'week' && ! ($task->due_date && $task->due_date->between(now()->startOfWeek(), now()->endOfWeek()))) {
            return false;
        }

        return true;
    }

    /**
     * Mueve una tarea a otra columna y posicion. Llamado desde el
     * `drop` del JS inline con los parametros `taskId`, `columnId`,
     * `position`.
     */
    public function moveTask(int $taskId, int $columnId, int $position): void
    {
        $task = Task::find($taskId);
        if ($task === null) {
            return;
        }

        $this->authorize('move', $task);

        $column = BoardColumn::find($columnId);
        if ($column === null) {
            return;
        }

        try {
            app(TaskMoveService::class)->move($task, $column, $position);
        } catch (\InvalidArgumentException $e) {
            $this->addError('move', $e->getMessage());

            return;
        }

        $this->refreshTasks();
        $this->dispatch('task-moved', taskId: $task->id);
    }

    /**
     * Abre el modal de creacion. `columnId` es la columna donde se
     * creara la tarea (default: primera columna).
     */
    public function openCreateForm(int $columnId): void
    {
        $this->authorize('create', [Task::class, $this->project]);

        $this->taskForm = [
            'mode' => 'create',
            'task_id' => null,
            'column_id' => $columnId,
            'title' => '',
            'description' => '',
            'priority' => TaskPriority::Medium->value,
            'type' => TaskType::Task->value,
            'estimated_hours' => null,
            'actual_hours' => null,
            'due_date' => null,
            'assignee_id' => null,
            'parent_id' => null,
        ];
    }

    /**
     * Abre el modal de edicion con los datos de la tarea.
     */
    public function openEditForm(int $taskId): void
    {
        $task = Task::find($taskId);
        if ($task === null) {
            return;
        }

        $this->authorize('update', $task);

        $this->taskForm = [
            'mode' => 'edit',
            'task_id' => $task->id,
            'column_id' => $task->column_id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority->value,
            'type' => $task->type->value,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => $task->actual_hours,
            'due_date' => optional($task->due_date)->format('Y-m-d'),
            'assignee_id' => $task->assignee_id,
            'parent_id' => $task->parent_id,
        ];
    }

    /**
     * Cierra el modal.
     */
    public function closeForm(): void
    {
        $this->taskForm = null;
        $this->taskFormAttachments = [];
        $this->resetErrorBag();
    }

    /**
     * Quita un adjunto pendiente del array. Pensado para el
     * boton "X" en la UI.
     *
     * @param  int  $index
     * @return void
     */
    public function removeTaskFormAttachment(int $index): void
    {
        if (array_key_exists($index, $this->taskFormAttachments)) {
            unset($this->taskFormAttachments[$index]);
            $this->taskFormAttachments = array_values($this->taskFormAttachments);
        }
    }

    /**
     * Persiste la tarea. Se llama tanto en crear como en editar; el
     * form lleva un flag `mode` para distinguirlos.
     *
     * En modo `create`, si hay adjuntos pendientes, se suben
     * tras crear la fila. En modo `edit` no se procesan
     * adjuntos del modal: la gestion de adjuntos se hace en
     * la vista de detalle para no romper la consistencia del
     * guardado.
     */
    public function saveTask(): void
    {
        if ($this->taskForm === null) {
            return;
        }

        $form = $this->taskForm;

        $rules = [
            'taskForm.title' => ['required', 'string', 'min:2', 'max:255'],
            'taskForm.description' => ['nullable', 'string', 'max:10000'],
            'taskForm.priority' => ['required', 'in:critical,high,medium,low'],
            'taskForm.type' => ['required', 'in:feature,bug,improvement,task'],
            'taskForm.estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'taskForm.actual_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'taskForm.due_date' => ['nullable', 'date'],
            'taskForm.assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'taskForm.parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'taskFormAttachments' => ['nullable', 'array', 'max:'.(int) config('clientflow.attachments.max_files_per_upload', 5)],
            'taskFormAttachments.*' => ['file', 'max:'.(int) config('clientflow.attachments.max_size_kb', 10240), 'mimes:'.implode(',', (array) config('clientflow.attachments.allowed_mimes', []))],
        ];

        $this->validate($rules);

        if ($form['mode'] === 'create') {
            $this->authorize('create', [Task::class, $this->project]);

            $column = BoardColumn::find($form['column_id']);
            if ($column === null || $column->project_id !== $this->project->id) {
                $this->addError('taskForm.column_id', 'Columna no valida.');

                return;
            }

            $task = Task::create([
                'project_id' => $this->project->id,
                'column_id' => $column->id,
                'parent_id' => $form['parent_id'] ?? null,
                'title' => $form['title'],
                'description' => $form['description'] ?? null,
                'priority' => $form['priority'],
                'type' => $form['type'],
                'estimated_hours' => $form['estimated_hours'] ?? null,
                'actual_hours' => $form['actual_hours'] ?? null,
                'due_date' => $form['due_date'] ?? null,
                'assignee_id' => $form['assignee_id'] ?? null,
                'created_by' => Auth::id(),
                'position' => (int) Task::where('column_id', $column->id)
                    ->whereNull('parent_id')
                    ->max('position') + 1,
            ]);

            // Subimos los adjuntos pendientes (si los hay).
            // El orden de subida se mantiene tal cual el usuario
            // los encolo. La validacion ya se ha hecho en el
            // `#[Validate]` de la propiedad.
            $attachmentCount = 0;
            if ($this->taskFormAttachments !== []) {
                $service = app(AttachmentService::class);
                foreach ($this->taskFormAttachments as $file) {
                    $service->store(
                        $this->project,
                        AttachmentService::CONTEXT_TASK,
                        $task->id,
                        $file,
                        Auth::user(),
                    );
                    $attachmentCount++;
                }
            }

            if ($attachmentCount > 0) {
                app(ProjectActivityLogger::class)->attachmentUploadedToTask(
                    $this->project,
                    $task,
                    $attachmentCount,
                    Auth::user(),
                );
            }
        } else {
            $task = Task::find($form['task_id']);
            if ($task === null) {
                return;
            }
            $this->authorize('update', $task);

            $task->update([
                'title' => $form['title'],
                'description' => $form['description'] ?? null,
                'priority' => $form['priority'],
                'type' => $form['type'],
                'estimated_hours' => $form['estimated_hours'] ?? null,
                'actual_hours' => $form['actual_hours'] ?? null,
                'due_date' => $form['due_date'] ?? null,
                'assignee_id' => $form['assignee_id'] ?? null,
                'parent_id' => $form['parent_id'] ?? null,
            ]);
        }

        $this->closeForm();
        $this->refreshTasks();
    }

    /**
     * Elimina una tarea. La action llega desde el menu de la card.
     */
    public function deleteTask(int $taskId): void
    {
        $task = Task::find($taskId);
        if ($task === null) {
            return;
        }

        $this->authorize('delete', $task);

        $columnId = $task->column_id;
        $position = $task->position;
        $task->delete();

        Task::where('column_id', $columnId)
            ->whereNull('parent_id')
            ->where('position', '>', $position)
            ->decrement('position');

        $this->refreshTasks();
    }

    /**
     * Resetea todos los filtros.
     */
    public function clearFilters(): void
    {
        $this->priorityFilter = '';
        $this->typeFilter = '';
        $this->assigneeFilter = null;
        $this->dueFilter = '';
    }

    /**
     * Render principal: kanban.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.kanban.kanban-board', [
            'project' => $this->project,
            'columns' => $this->project->columns()->ordered()->get(),
            'members' => $this->project->members,
        ]);
    }
}
