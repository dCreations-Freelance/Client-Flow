<x-layouts.portal :title="$summary->project->name">
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @php
            $project = $summary->project;
            $crumbs = [
                ['label' => 'Mis organizaciones', 'href' => route('portal.dashboard')],
                ['label' => $project->organization->name, 'href' => route('portal.organizations.show', $project->organization)],
                ['label' => $project->name],
            ];
        @endphp

        <x-partials.project-hero :project="$project" :crumbs="$crumbs" :unreadMessages="$summary->unreadMessages" :showArchived="false">
            <x-slot:actions>
                @if (Route::has('portal.projects.board'))
                    <a href="{{ route('portal.projects.board', $project) }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                        Ver tablero kanban
                    </a>
                @endif

                @if (Route::has('portal.projects.chat'))
                    <a href="{{ route('portal.projects.chat', $project) }}" class="relative inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Chat
                        @if ($summary->unreadMessages > 0)
                            <span class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                                {{ $summary->unreadMessages }}
                            </span>
                        @endif
                    </a>
                @endif

                @if (Route::has('portal.projects.documents.index'))
                    <a href="{{ route('portal.projects.documents.index', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Ver documentos
                    </a>
                @endif

                @if (Route::has('portal.projects.calendar'))
                    <a href="{{ route('portal.projects.calendar', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Ver calendario
                    </a>
                @endif

                @if (Route::has('portal.projects.ai'))
                    <a href="{{ route('portal.projects.ai', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Asistente IA
                    </a>
                @endif

                @if (Route::has('portal.projects.time.index'))
                    <a href="{{ route('portal.projects.time.index', $project) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]">
                        Tiempo dedicado
                    </a>
                @endif
            </x-slot:actions>
        </x-partials.project-hero>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-partials.project-stat-tile
                title="Progreso"
                :value="$project->tasks_progress_percent.'%'"
                :sub="$project->completed_tasks_count.' de '.$project->total_tasks_count.' tareas completadas'"
                :href="Route::has('portal.projects.board') ? route('portal.projects.board', $project) : null"
                tone="primary"
            />

            <x-partials.project-stat-tile
                title="Proxima entrega"
                :value="$summary->nextDelivery?->format('d/m/Y') ?? '—'"
                :sub="$summary->nextDeliveryLabel"
                :tone="$summary->nextDeliveryTone"
            />

            <x-partials.project-stat-tile
                title="Mensajes"
                :value="$summary->unreadMessages"
                :sub="$summary->unreadMessages > 0 ? 'Tienes mensajes sin leer' : 'Al dia con la conversacion'"
                :href="Route::has('portal.projects.chat') ? route('portal.projects.chat', $project) : null"
                :tone="$summary->unreadMessages > 0 ? 'danger' : 'neutral'"
            />

            <x-partials.project-stat-tile
                title="Tu equipo"
                :value="$summary->totalMembers"
                :sub="$summary->totalMembers === 1 ? 'persona trabajando' : 'personas trabajando'"
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
                title="Horas dedicadas"
                :value="$totalDisplay"
                :sub="$summary->totalLoggedMinutes > 0 ? 'Total invertido en este proyecto' : 'Aun no hay tiempo registrado'"
                :href="Route::has('portal.projects.time.index') ? route('portal.projects.time.index', $project) : null"
                tone="primary"
            />
        </div>

        <x-partials.project-previews :summary="$summary" area="portal" />
    </div>
</x-layouts.portal>
