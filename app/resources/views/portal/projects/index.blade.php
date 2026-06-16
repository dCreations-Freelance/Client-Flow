<x-layouts.portal title="Proyectos">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Proyectos</h1>
            <p class="mt-1 text-sm text-[#6B7280]">Listado de proyectos visibles de tus organizaciones.</p>
        </div>

        @if ($projects->isEmpty())
            <x-ui.card>
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="w-12 h-12 rounded-full bg-[#F4F1EA] flex items-center justify-center mb-4">
                        <span class="text-2xl text-[#6B7280]">+</span>
                    </div>
                    <h3 class="text-sm font-medium text-[#111827]">Aun no hay proyectos visibles</h3>
                    <p class="mt-1 text-sm text-[#6B7280]">Cuando el administrador publique un proyecto, aparecera aqui.</p>
                </div>
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    @php $unread = $unreadByProject[$project->id] ?? 0; @endphp
                    <a href="{{ route('portal.projects.show', $project) }}" class="block">
                        <x-ui.card hover>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-base font-semibold text-[#111827]">{{ $project->name }}</h3>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if (Route::has('portal.projects.chat') && $unread > 0)
                                        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                                            {{ $unread }}
                                        </span>
                                    @endif
                                    <x-partials.status-badge :status="$project->status" />
                                </div>
                            </div>
                            @if ($project->organization)
                                <p class="mt-1 text-xs text-[#6B7280]">{{ $project->organization->name }}</p>
                            @endif
                            @if ($project->description)
                                <p class="mt-3 line-clamp-2 text-sm text-[#6B7280]">{{ $project->description }}</p>
                            @endif
                            <div class="mt-4">
                                <x-partials.progress-bar :value="$project->tasks_progress_percent" />
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>

            <div>
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</x-layouts.portal>
