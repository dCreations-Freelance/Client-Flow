<x-layouts.portal title="Inicio">
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-semibold">Hola, {{ $user->name }}</h1>
            <p class="mt-1 text-sm text-[#6B7280]">Estas son las organizaciones a las que perteneces y los proyectos mas recientes.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Organizaciones</p>
                <p class="mt-2 text-3xl font-semibold">{{ $stats['organizations'] }}</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Proyectos abiertos</p>
                <p class="mt-2 text-3xl font-semibold">{{ $stats['projects'] }}</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Tareas</p>
                <p class="mt-2 text-3xl font-semibold text-[#9CA3AF]">{{ $stats['tasks'] }}</p>
                <p class="mt-1 text-xs text-[#6B7280]">Disponible en fase 3</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Sin leer</p>
                <p class="mt-2 text-3xl font-semibold text-[#9CA3AF]">{{ $stats['unread'] }}</p>
                <p class="mt-1 text-xs text-[#6B7280]">Disponible en fase 5</p>
            </x-ui.card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="text-base font-semibold">Tus organizaciones</h2>

                @if ($organizations->isEmpty())
                    <div class="mt-6 flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-full bg-[#F4F1EA] flex items-center justify-center mb-4">
                            <span class="text-2xl text-[#6B7280]">+</span>
                        </div>
                        <h3 class="text-sm font-medium text-[#111827]">Aun no perteneces a ninguna organizacion</h3>
                        <p class="mt-1 text-sm text-[#6B7280]">Cuando un administrador te invite, aparecera aqui.</p>
                    </div>
                @else
                    <ul class="mt-4 divide-y divide-[#E7E2D8]">
                        @foreach ($organizations as $organization)
                            <li class="flex items-center justify-between py-3 text-sm">
                                <div>
                                    <a href="{{ route('portal.organizations.show', $organization) }}" class="font-medium text-[#111827] hover:text-[#2563EB]">
                                        {{ $organization->name }}
                                    </a>
                                    <p class="text-xs text-[#6B7280]">{{ $organization->members_count }} miembros</p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-[#EFF6FF] text-[#2563EB]' => $organization->pivot->role === 'owner',
                                    'bg-[#F4F1EA] text-[#6B7280]' => $organization->pivot->role !== 'owner',
                                ])>
                                    {{ ucfirst($organization->pivot->role) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold">Proyectos recientes</h2>
                    <a href="{{ route('portal.projects.index') }}" class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]">Ver todos</a>
                </div>

                @if ($recentProjects->isEmpty())
                    <p class="mt-6 text-center text-sm text-[#6B7280] py-8">
                        Aun no hay proyectos visibles.
                    </p>
                @else
                    <ul class="mt-4 divide-y divide-[#E7E2D8]">
                        @foreach ($recentProjects as $project)
                            <li class="py-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('portal.projects.show', $project) }}" class="block truncate font-medium text-[#111827] hover:text-[#2563EB]">
                                            {{ $project->name }}
                                        </a>
                                        <p class="truncate text-xs text-[#6B7280]">{{ $project->organization?->name }}</p>
                                    </div>
                                    <x-partials.status-badge :status="$project->status" />
                                </div>
                                <div class="mt-2">
                                    <x-partials.progress-bar :value="$project->progress" :showPercent="false" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-layouts.portal>
