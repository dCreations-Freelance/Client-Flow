<x-layouts.portal :title="$organization->name">
    <div class="space-y-6">
        <div>
            <a href="{{ route('portal.dashboard') }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                &larr; Volver al panel
            </a>
        </div>

        <x-ui.card>
            <h1 class="text-2xl font-semibold">{{ $organization->name }}</h1>
            @if ($organization->description)
                <p class="mt-2 whitespace-pre-line text-sm text-[#6B7280]">{{ $organization->description }}</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-base font-semibold">Proyectos de la organizacion</h2>

            @if ($projects->isEmpty())
                <p class="mt-4 text-center text-sm text-[#6B7280] py-8">Aun no hay proyectos visibles en esta organizacion.</p>
            @else
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <li>
                            <a href="{{ route('portal.projects.show', $project) }}" class="block">
                                <x-ui.card hover>
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-base font-semibold text-[#111827]">{{ $project->name }}</h3>
                                        <x-partials.status-badge :status="$project->status" />
                                    </div>
                                    @if ($project->description)
                                        <p class="mt-2 line-clamp-2 text-sm text-[#6B7280]">{{ $project->description }}</p>
                                    @endif
                                    <div class="mt-4">
                                        <x-partials.progress-bar :value="$project->tasks_progress_percent" />
                                    </div>
                                </x-ui.card>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-layouts.portal>
