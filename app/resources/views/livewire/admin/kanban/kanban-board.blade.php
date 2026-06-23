<div>
    {{-- Filtros: en movil se distribuyen en columnas automaticas --}}
    <div class="mb-4 grid grid-cols-1 gap-2 rounded-xl border border-[#E7E2D8] bg-white p-3 sm:flex sm:flex-wrap sm:items-center">
        <span class="text-xs font-medium text-[#6B7280] sm:mr-1">Filtros:</span>

        <select wire:model.live="priorityFilter" class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs">
            <option value="">Prioridad</option>
            @foreach (\App\Enums\TaskPriority::cases() as $priority)
                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="typeFilter" class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs">
            <option value="">Tipo</option>
            @foreach (\App\Enums\TaskType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="assigneeFilter" class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs">
            <option value="">Asignado a</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="dueFilter" class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs">
            <option value="">Fecha</option>
            <option value="overdue">Vencidas</option>
            <option value="today">Hoy</option>
            <option value="week">Esta semana</option>
        </select>

        @if ($priorityFilter || $typeFilter || $assigneeFilter || $dueFilter)
            <button type="button" wire:click="clearFilters" class="text-left text-xs font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                Limpiar
            </button>
        @endif
    </div>

    {{-- Tablero: scroll horizontal. Cada columna se adapta al ancho
         del viewport en movil (w-[85vw]) y a 18rem en sm+. --}}
    <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-4 sm:mx-0 sm:gap-4 sm:px-0">
        @foreach ($columns as $column)
            <div
                class="flex w-[85vw] shrink-0 flex-col rounded-xl border border-[#E7E2D8] bg-[#FAFAF7] sm:w-72"
                data-column-id="{{ $column->id }}"
                ondragover="event.preventDefault(); this.classList.add('kanban-drop-target');"
                ondragleave="this.classList.remove('kanban-drop-target');"
                ondrop="clientflowKanbanDrop(event, {{ $column->id }}); this.classList.remove('kanban-drop-target');"
            >
                {{-- Cabecera de la columna: min-w-0 en el contenedor
                     flexible para que el truncate funcione, y shrink-0
                     en el contador y el boton para que no se aplasten. --}}
                <div class="flex items-center gap-2 border-b border-[#E7E2D8] bg-white px-3 py-2">
                    @if ($column->color)
                        <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $column->color }}"></span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-[#111827]">{{ $column->name }}</h3>
                    </div>
                    <span class="shrink-0 text-xs text-[#6B7280]">({{ $this->tasksInColumn($column)->count() }})</span>
                    <button
                        type="button"
                        wire:click="openCreateForm({{ $column->id }})"
                        class="shrink-0 rounded p-1 text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                        title="Anadir tarea"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 space-y-2 p-2" data-column-body>
                    @forelse ($this->tasksInColumn($column) as $task)
                        <article
                            data-task
                            data-task-id="{{ $task->id }}"
                            draggable="true"
                            ondragstart="event.dataTransfer.setData('text/plain', '{{ $task->id }}'); event.dataTransfer.effectAllowed = 'move';"
                            wire:key="task-{{ $task->id }}"
                            class="cursor-grab rounded-lg border border-[#E7E2D8] bg-white p-3 shadow-sm transition-all hover:border-[#D8D0C3] hover:shadow-md {{ $task->isCompleted() ? 'opacity-60' : '' }}"
                        >
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <button
                                    type="button"
                                    wire:click="openEditForm({{ $task->id }})"
                                    class="min-w-0 flex-1 text-left text-sm font-medium text-[#111827] hover:text-[#2563EB]"
                                >
                                    <span class="line-clamp-3 break-words">{{ $task->title }}</span>
                                </button>
                                <x-partials.task-priority-badge :priority="$task->priority" />
                            </div>

                            @if ($task->description)
                                <p class="mb-2 line-clamp-2 text-xs text-[#6B7280]">{{ $task->description }}</p>
                            @endif

                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                <x-partials.task-type-badge :type="$task->type" />

                                @if ($task->estimated_hours)
                                    <span class="inline-flex items-center rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[10px] text-[#6B7280]">
                                        {{ $task->estimated_hours }}h est.
                                    </span>
                                @endif

                                @if ($task->due_date)
                                    <span @class([
                                        'inline-flex items-center rounded px-1.5 py-0.5 text-[10px]',
                                        'bg-[#FEF2F2] text-[#DC2626]' => $task->isOverdue(),
                                        'bg-[#FFFBEB] text-[#D97706]' => $task->due_date->isToday(),
                                        'bg-[#F4F1EA] text-[#6B7280]' => ! $task->isOverdue() && ! $task->due_date->isToday(),
                                    ])>
                                        {{ $task->due_date->format('d/m') }}
                                    </span>
                                @endif

                                @if ($task->subtasks_count > 0)
                                    <span class="inline-flex items-center rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[10px] text-[#6B7280]">
                                        {{ $task->subtasks_completed_count }}/{{ $task->subtasks_count }}
                                    </span>
                                @endif
                            </div>

                            {{-- Footer: en pantallas muy estrechas el avatar y
                                 la accion se apilan; en sm+ siguen en una fila. --}}
                            <div class="mt-2 flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between">
                                @if ($task->assignee)
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-[#2563EB] text-[10px] font-medium text-white" title="{{ $task->assignee->name }}">
                                        {{ strtoupper(mb_substr($task->assignee->name, 0, 2)) }}
                                    </div>
                                @else
                                    <span class="text-[10px] text-[#9CA3AF]">Sin asignar</span>
                                @endif

                                <button
                                    type="button"
                                    wire:click="deleteTask({{ $task->id }})"
                                    wire:confirm="Eliminar esta tarea? Sus subtareas tambien se eliminaran."
                                    class="self-start text-[10px] font-medium text-[#DC2626] hover:text-[#B91C1C] sm:self-auto"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </article>
                    @empty
                        <p class="py-4 text-center text-xs text-[#9CA3AF]">Sin tareas.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal de tarea: padding generoso en movil, maxima altura
         con scroll interno. --}}
    @if ($taskForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="closeForm">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[28px] border border-[#E7E2D8] bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold">
                    {{ $taskForm['mode'] === 'create' ? 'Nueva tarea' : 'Editar tarea' }}
                </h3>

                <form wire:submit="saveTask" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#111827]">Titulo <span class="text-[#DC2626]">*</span></label>
                        <input
                            type="text"
                            wire:model="taskForm.title"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                            required
                        >
                        @error('taskForm.title') <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#111827]">Descripcion</label>
                        <textarea
                            wire:model="taskForm.description"
                            rows="3"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Prioridad</label>
                            <select wire:model="taskForm.priority" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                                @foreach (\App\Enums\TaskPriority::cases() as $p)
                                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Tipo</label>
                            <select wire:model="taskForm.type" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                                @foreach (\App\Enums\TaskType::cases() as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Horas est.</label>
                            <input type="number" step="0.5" min="0" wire:model="taskForm.estimated_hours" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Horas reales</label>
                            <input type="number" step="0.5" min="0" wire:model="taskForm.actual_hours" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Fecha limite</label>
                            <input type="date" wire:model="taskForm.due_date" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Asignado a</label>
                            <select wire:model="taskForm.assignee_id" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                                <option value="">Sin asignar</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                            @error('taskForm.assignee_id') <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Tarea padre</label>
                            <select wire:model="taskForm.parent_id" class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm">
                                <option value="">Ninguna</option>
                                @foreach ($tasks->where('id', '!=', $taskForm['task_id']) as $other)
                                    <option value="{{ $other->id }}">{{ $other->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Adjuntos: solo en modo create. La subida desde
                        el modal es opcional y admite hasta el limite
                        configurado. La validacion se hace en el
                        componente via `#[Validate]`. --}}
                    @if ($taskForm['mode'] === 'create')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-[#111827]">Adjuntos</label>

                            @if (count($taskFormAttachments) > 0)
                                <div class="mb-2 flex flex-wrap gap-2">
                                    @foreach ($taskFormAttachments as $index => $file)
                                        <div class="flex items-center gap-2 rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] px-2.5 py-1.5 text-xs">
                                            <svg class="h-4 w-4 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                            </svg>
                                            <span class="max-w-[180px] truncate text-[#111827]">{{ $file->getClientOriginalName() }}</span>
                                            <span class="text-[#9CA3AF]">{{ \App\Models\TaskAttachment::formatBytes($file->getSize()) }}</span>
                                            <button
                                                type="button"
                                                wire:click="removeTaskFormAttachment({{ $index }})"
                                                class="ml-1 text-[#6B7280] hover:text-[#DC2626]"
                                                title="Quitar"
                                            >&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-[#E7E2D8] bg-[#FAFAF7] px-3 py-2 text-xs text-[#6B7280] hover:border-[#2563EB] hover:text-[#2563EB]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                <span>Subir archivos (opcional)</span>
                                <input type="file" wire:model="taskFormAttachments" multiple class="hidden">
                            </label>

                            <p class="mt-1 text-[10px] text-[#9CA3AF]">
                                Maximo {{ (int) config('clientflow.attachments.max_files_per_upload', 5) }} archivos,
                                {{ (int) config('clientflow.attachments.max_size_kb', 10240) / 1024 }} MB cada uno.
                                Los adjuntos tambien pueden gestionarse despues desde el detalle de la tarea.
                            </p>

                            @error('taskFormAttachments')
                                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                            @enderror
                            @error('taskFormAttachments.*')
                                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="flex flex-col-reverse gap-2 pt-4 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" wire:click="closeForm" class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">
                            Cancelar
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                            {{ $taskForm['mode'] === 'create' ? 'Crear tarea' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<style>
    .kanban-drop-target {
        outline: 2px solid #2563EB;
        outline-offset: -2px;
    }
</style>

<script>
    // Handler global de drop para el kanban. Se mantiene en un
    // script para no depender de Alpine.js. Cuenta las tareas en
    // la columna destino y envia la posicion calculada a Livewire.
    function clientflowKanbanDrop(event, targetColumnId) {
        event.preventDefault();
        const taskId = event.dataTransfer.getData('text/plain');
        if (!taskId) return;

        const column = event.currentTarget;
        const tasksInColumn = column.querySelectorAll('[data-task]');
        const position = tasksInColumn.length;

        // Llama al metodo Livewire del componente mas cercano.
        const wire = window.Livewire?.find(
            column.closest('[wire\\:id]')?.getAttribute('wire:id')
        );
        if (wire) {
            wire.call('moveTask', parseInt(taskId, 10), targetColumnId, position);
        }
    }
</script>
