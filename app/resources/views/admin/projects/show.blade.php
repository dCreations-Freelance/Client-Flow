<x-layouts.admin :title="$summary->project->name">
    <div class="space-y-6">
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

        @php
            $project = $summary->project;
            $crumbs = [
                ['label' => 'Organizaciones', 'href' => route('admin.organizations.index')],
                ['label' => $project->organization->name, 'href' => route('admin.organizations.show', $project->organization)],
                ['label' => $project->name],
            ];
        @endphp

        <x-partials.project-hero :project="$project" :crumbs="$crumbs" :unreadMessages="$summary->unreadMessages">
            <x-slot:actions>
                @if (Route::has('admin.projects.board'))
                    <a href="{{ route('admin.projects.board', $project) }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                        Abrir tablero
                    </a>
                @endif

                @if (Route::has('admin.projects.chat'))
                    <a href="{{ route('admin.projects.chat', $project) }}" class="relative inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Chat
                        @if ($summary->unreadMessages > 0)
                            <span class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                                {{ $summary->unreadMessages }}
                            </span>
                        @endif
                    </a>
                @endif

                @if (Route::has('admin.projects.documents.index'))
                    <a href="{{ route('admin.projects.documents.index', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Documentos
                    </a>
                @endif

                @if (Route::has('admin.projects.calendar'))
                    <a href="{{ route('admin.projects.calendar', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Calendario
                    </a>
                @endif

                @if (Route::has('admin.projects.ai'))
                    <a href="{{ route('admin.projects.ai', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Asistente IA
                    </a>
                @endif

                @if (Route::has('admin.projects.agents.index'))
                    <a href="{{ route('admin.projects.agents.index', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Agentes
                    </a>
                @endif

                @if (Route::has('admin.projects.time.index'))
                    <a href="{{ route('admin.projects.time.index', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Registro de tiempo
                    </a>
                @endif

                @if (Route::has('admin.projects.activity'))
                    <a href="{{ route('admin.projects.activity', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Actividad
                    </a>
                @endif

                <a href="{{ route('admin.projects.edit', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                    Editar
                </a>

                @include('admin.projects._archive_button', ['project' => $project])
            </x-slot:actions>
        </x-partials.project-hero>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-partials.project-stat-tile
                title="Progreso"
                :value="$project->tasks_progress_percent.'%'"
                :sub="$project->completed_tasks_count.' de '.$project->total_tasks_count.' tareas'"
                :href="Route::has('admin.projects.board') ? route('admin.projects.board', $project) : null"
                tone="primary"
            />

            <x-partials.project-stat-tile
                title="Proxima entrega"
                :value="$summary->nextDelivery?->format('d/m/Y') ?? '—'"
                :sub="$summary->nextDeliveryLabel"
                :href="Route::has('admin.projects.edit') ? route('admin.projects.edit', $project) : null"
                :tone="$summary->nextDeliveryTone"
            />

            <x-partials.project-stat-tile
                title="Mensajes sin leer"
                :value="$summary->unreadMessages"
                :sub="$summary->totalMessages.' mensajes en total'"
                :href="Route::has('admin.projects.chat') ? route('admin.projects.chat', $project) : null"
                tone="danger"
            />

            <x-partials.project-stat-tile
                title="Miembros"
                :value="$summary->totalMembers"
                :sub="$summary->totalMembers === 1 ? 'persona en el equipo' : 'personas en el equipo'"
                :href="Route::has('admin.organizations.show') ? route('admin.organizations.show', $project->organization).'#members' : null"
            />
        </div>

        @php
            $totalHours = intdiv($summary->totalLoggedMinutes, 60);
            $totalMins = $summary->totalLoggedMinutes % 60;
            $totalDisplay = $totalHours > 0
                ? $totalHours.'h '.str_pad((string) $totalMins, 2, '0', STR_PAD_LEFT).'m'
                : $totalMins.'m';
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-partials.project-stat-tile
                title="Horas registradas"
                :value="$totalDisplay"
                :sub="$summary->totalLoggedMinutes > 0 ? 'Total dedicado al proyecto' : 'Aun no se ha registrado tiempo'"
                :href="Route::has('admin.projects.time.index') ? route('admin.projects.time.index', $project) : null"
                tone="primary"
            />
        </div>

        <x-partials.project-previews :summary="$summary" area="admin" />

        <div id="miembros">
            <livewire:admin.project.project-members :project="$project" :availableMembers="$availableMembers" />
        </div>
    </div>
</x-layouts.admin>
