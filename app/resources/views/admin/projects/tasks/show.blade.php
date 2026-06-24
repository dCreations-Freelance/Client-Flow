<x-layouts.admin :title="'Tarea: '.$task->title">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-[#111827]">Inicio</a>
            <span>/</span>
            <a href="{{ route('admin.projects.index') }}" class="hover:text-[#111827]">Proyectos</a>
            <span>/</span>
            <a href="{{ route('admin.projects.show', $project) }}" class="hover:text-[#111827]">{{ $project->name }}</a>
            <span>/</span>
            <a href="{{ route('admin.projects.board', $project) }}" class="hover:text-[#111827]">Tablero</a>
            <span>/</span>
            <span class="text-[#111827]">{{ $task->title }}</span>
        </nav>

        @if (session('status'))
            <div class="rounded-lg border border-[#16A34A] bg-[#F0FDF4] px-4 py-2 text-sm text-[#16A34A]">
                {{ session('status') }}
            </div>
        @endif

        {{-- Cabecera de la tarea --}}
        <x-ui.card>
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h1 class="text-2xl font-semibold text-[#111827]">{{ $task->title }}</h1>
                        <p class="mt-1 text-sm text-[#6B7280]">
                            Columna: <span class="font-medium">{{ $task->column?->name }}</span>
                        </p>
                    </div>
                    <a href="{{ route('admin.projects.board', $project) }}" class="inline-flex items-center gap-1 rounded-lg border border-[#E7E2D8] bg-white px-3 py-1.5 text-xs font-medium text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]">
                        &larr; Volver al tablero
                    </a>
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

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Columna principal: adjuntos y subtareas --}}
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card>
                    <livewire:admin.time-tracking.time-tracker :project="$project" :task="$task" />
                </x-ui.card>

                <x-ui.card>
                    <livewire:shared.task-attachment-list :project="$project" :task="$task" />
                </x-ui.card>

                @if ($task->subtasks->isNotEmpty())
                    <x-ui.card>
                        <h3 class="mb-3 text-sm font-semibold text-[#111827]">Subtareas</h3>
                        <ul class="space-y-2">
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

            {{-- Columna lateral: asignado, creador, estimado --}}
            <div class="space-y-6">
                <x-ui.card>
                    <h3 class="mb-3 text-sm font-semibold text-[#111827]">Asignado a</h3>
                    @if ($task->assignee)
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563EB] text-sm font-medium text-white">
                                {{ strtoupper(mb_substr($task->assignee->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[#111827]">{{ $task->assignee->name }}</p>
                                <p class="text-xs text-[#6B7280]">{{ $task->assignee->email }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-[#6B7280]">Sin asignar.</p>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="mb-3 text-sm font-semibold text-[#111827]">Estimacion</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-[#6B7280]">Horas estimadas</dt>
                            <dd class="text-[#111827]">{{ $task->estimated_hours ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#6B7280]">Horas reales</dt>
                            <dd class="text-[#111827]">{{ $task->actual_hours ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#6B7280]">Horas registradas</dt>
                            <dd class="font-semibold text-[#111827]" data-test="task-total-logged-card">
                                {{ $task->total_logged_display }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#6B7280]">Creada</dt>
                            <dd class="text-[#111827]">{{ $task->created_at?->format('d/m/Y') }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.admin>
