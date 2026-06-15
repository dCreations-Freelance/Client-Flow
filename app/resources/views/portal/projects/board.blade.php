<x-layouts.portal :title="'Tablero: '.$project->name">
    <div class="space-y-6">
        <div>
            <a href="{{ route('portal.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                &larr; Volver al detalle
            </a>
            <h1 class="mt-2 text-2xl font-semibold">{{ $project->name }}</h1>
            <p class="text-sm text-[#6B7280]">
                {{ $project->organization?->name }} ·
                <x-partials.status-badge :status="$project->status" />
            </p>
        </div>

        <p class="text-sm text-[#6B7280]">
            Vista de solo lectura. Si tienes dudas o necesitas cambios, dejalas en el chat del proyecto.
        </p>

        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach ($project->columns()->ordered()->get() as $column)
                <div class="flex w-72 shrink-0 flex-col rounded-xl border border-[#E7E2D8] bg-[#FAFAF7]">
                    <div class="flex items-center gap-2 border-b border-[#E7E2D8] bg-white px-3 py-2">
                        @if ($column->color)
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $column->color }}"></span>
                        @endif
                        <h3 class="text-sm font-semibold text-[#111827]">{{ $column->name }}</h3>
                        <span class="text-xs text-[#6B7280]">({{ $column->rootTasks()->count() }})</span>
                    </div>

                    <div class="flex-1 space-y-2 p-2">
                        @forelse ($column->rootTasks()->orderBy('position')->get() as $task)
                            <a
                                href="{{ route('portal.projects.tasks.show', [$project, $task]) }}"
                                wire:key="task-{{ $task->id }}"
                                class="block rounded-lg border border-[#E7E2D8] bg-white p-3 transition-all hover:border-[#D8D0C3] hover:shadow-md {{ $task->isCompleted() ? 'opacity-60' : '' }}"
                            >
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <span class="text-sm font-medium text-[#111827]">{{ $task->title }}</span>
                                    <x-partials.task-priority-badge :priority="$task->priority" />
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                    <x-partials.task-type-badge :type="$task->type" />

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
                            </a>
                        @empty
                            <p class="py-4 text-center text-xs text-[#9CA3AF]">Sin tareas.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.portal>
