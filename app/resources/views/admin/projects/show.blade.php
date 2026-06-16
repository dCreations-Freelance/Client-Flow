<x-layouts.admin :title="$project->name">
    <div class="space-y-6">
        <a href="{{ route('admin.projects.index') }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al listado
        </a>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        {{-- Header del proyecto --}}
        <x-ui.card>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ $project->name }}</h1>
                        <x-partials.status-badge :status="$project->status" />
                        @if ($project->isArchived())
                            <span class="inline-flex items-center rounded-full bg-[#F4F1EA] px-2.5 py-0.5 text-xs font-medium text-[#6B7280]">
                                Archivado
                            </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-[#6B7280]">
                        Organizacion:
                        <a href="{{ route('admin.organizations.show', $project->organization) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                            {{ $project->organization->name }}
                        </a>
                    </p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Inicio</p>
                            <p class="mt-1 text-sm text-[#111827]">
                                {{ $project->starts_at?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Fin estimado</p>
                            <p class="mt-1 text-sm text-[#111827]">
                                {{ $project->estimated_ends_at?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Visibilidad</p>
                            <p class="mt-1 text-sm text-[#111827]">
                                {{ $project->is_visible_to_client ? 'Visible para clientes' : 'Solo admin' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.projects.board', $project) }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                        Abrir tablero
                    </a>

                    @if (Route::has('admin.projects.documents.index'))
                        <a href="{{ route('admin.projects.documents.index', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                            Documentos
                        </a>
                    @endif

                    @if (Route::has('admin.projects.chat'))
                        <a href="{{ route('admin.projects.chat', $project) }}" class="relative inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                            Chat
                            @php
                                $chatUnread = \App\Models\ProjectChatRead::query()
                                    ->where('project_id', $project->id)
                                    ->where('user_id', auth()->id())
                                    ->first();
                                $unreadQuery = \App\Models\ProjectMessage::where('project_id', $project->id);
                                if ($chatUnread !== null) {
                                    $unreadQuery->where('id', '>', (int) $chatUnread->last_read_message_id);
                                }
                                $unreadCount = $unreadQuery->count();
                            @endphp
                            @if ($unreadCount > 0)
                                <span class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>
                    @endif

                    <a href="{{ route('admin.projects.edit', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                        Editar
                    </a>

                    @include('admin.projects._archive_button', ['project' => $project])
                </div>
            </div>

            <div class="mt-6">
                <p class="mb-1 text-xs font-medium uppercase tracking-wider text-[#6B7280]">Progreso</p>
                <x-partials.progress-bar :value="$project->tasks_progress_percent" :showPercent="true" />
                <p class="mt-2 text-xs text-[#6B7280]">
                    @if ($project->total_tasks_count === 0)
                        Sin tareas todavia. Crea tareas en el tablero para empezar a medir el progreso.
                    @else
                        {{ $project->completed_tasks_count }} de {{ $project->total_tasks_count }} tareas completadas.
                    @endif
                </p>
            </div>
        </x-ui.card>

        {{-- Descripcion --}}
        @if ($project->description)
            <x-ui.card>
                <h2 class="text-sm font-semibold">Descripcion</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-[#111827]">{{ $project->description }}</p>
            </x-ui.card>
        @endif

        {{-- Miembros --}}
        <livewire:admin.project.project-members :project="$project" :availableMembers="$availableMembers" />
    </div>
</x-layouts.admin>
