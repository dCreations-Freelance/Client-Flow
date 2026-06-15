<x-layouts.portal :title="$task->title">
    <div class="space-y-6">
        <a href="{{ route('portal.projects.board', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al tablero
        </a>

        <x-ui.card>
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold">{{ $task->title }}</h1>
                    <x-partials.status-badge :status="$project->status" />
                </div>

                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <x-partials.task-priority-badge :priority="$task->priority" />
                    <x-partials.task-type-badge :type="$task->type" />
                    @if ($task->due_date)
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-[#FEF2F2] text-[#DC2626]' => $task->isOverdue(),
                            'bg-[#FFFBEB] text-[#D97706]' => $task->due_date->isToday(),
                            'bg-[#F4F1EA] text-[#6B7280]' => ! $task->isOverdue() && ! $task->due_date->isToday(),
                        ])>
                            Fecha limite: {{ $task->due_date->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
            </div>

            @if ($task->description)
                <div class="mt-4 whitespace-pre-line text-sm text-[#111827]">{{ $task->description }}</div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-sm font-semibold">Asignado a</h2>
            @if ($task->assignee)
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563EB] text-sm font-medium text-white">
                        {{ strtoupper(mb_substr($task->assignee->name, 0, 2)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#111827]">{{ $task->assignee->name }}</p>
                        <p class="text-xs text-[#6B7280]">{{ $task->assignee->email }}</p>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-[#6B7280]">Sin asignar.</p>
            @endif
        </x-ui.card>

        @if ($task->subtasks->isNotEmpty())
            <x-ui.card>
                <h2 class="text-sm font-semibold">Subtareas</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($task->subtasks as $sub)
                        <li class="flex items-center justify-between rounded-lg border border-[#E7E2D8] p-2 text-sm">
                            <div class="flex items-center gap-2">
                                @if ($sub->isCompleted())
                                    <span class="text-[#16A34A]">&#10003;</span>
                                @else
                                    <span class="text-[#9CA3AF]">&middot;</span>
                                @endif
                                <span @class(['text-[#111827]', 'line-through opacity-60' => $sub->isCompleted()])>
                                    {{ $sub->title }}
                                </span>
                            </div>
                            <x-partials.task-priority-badge :priority="$sub->priority" />
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </div>
</x-layouts.portal>
