<x-layouts.portal :title="$project->name">
    <div class="space-y-6">
        <a href="{{ route('portal.projects.index') }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al listado
        </a>

        <x-ui.card>
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold">{{ $project->name }}</h1>
                    <x-partials.status-badge :status="$project->status" />
                </div>

                @if ($project->organization)
                    <p class="text-sm text-[#6B7280]">
                        Organizacion: <span class="font-medium text-[#111827]">{{ $project->organization->name }}</span>
                    </p>
                @endif

                <div class="mt-2 grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Inicio</p>
                        <p class="mt-1 text-sm text-[#111827]">{{ $project->starts_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Fin estimado</p>
                        <p class="mt-1 text-sm text-[#111827]">{{ $project->estimated_ends_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Equipo</p>
                        <p class="mt-1 text-sm text-[#111827]">{{ $project->members->count() }} personas</p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <p class="mb-1 text-xs font-medium uppercase tracking-wider text-[#6B7280]">Progreso</p>
                <x-partials.progress-bar :value="$project->tasks_progress_percent" />
                <p class="mt-2 text-xs text-[#6B7280]">
                    @if ($project->total_tasks_count === 0)
                        Sin tareas todavia.
                    @else
                        {{ $project->completed_tasks_count }} de {{ $project->total_tasks_count }} tareas completadas.
                    @endif
                </p>
            </div>

            @if (Route::has('portal.projects.board'))
                <div class="mt-6">
                    <a href="{{ route('portal.projects.board', $project) }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                        Ver tablero kanban
                    </a>
                </div>
            @endif
        </x-ui.card>

        @if ($project->description)
            <x-ui.card>
                <h2 class="text-sm font-semibold">Descripcion</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-[#111827]">{{ $project->description }}</p>
            </x-ui.card>
        @endif

        <x-ui.card>
            <h2 class="text-sm font-semibold">Equipo</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse ($project->members as $member)
                    <li class="flex items-center gap-3 rounded-lg border border-[#E7E2D8] bg-white p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2563EB] text-xs font-medium text-white">
                            {{ strtoupper(mb_substr($member->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-[#111827]">{{ $member->name }}</p>
                            <p class="text-xs text-[#6B7280]">{{ $member->email }}</p>
                        </div>
                    </li>
                @empty
                    <li class="col-span-2 text-center text-sm text-[#6B7280]">Aun no hay miembros asignados al proyecto.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</x-layouts.portal>
