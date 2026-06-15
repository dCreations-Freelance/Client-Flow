<x-layouts.portal title="Inicio">
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-semibold">Hola, {{ $user->name }}</h1>
            <p class="mt-1 text-sm text-[#6B7280]">Estas son las organizaciones a las que perteneces.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Organizaciones</p>
                <p class="mt-2 text-3xl font-semibold">{{ $stats['organizations'] }}</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Proyectos</p>
                <p class="mt-2 text-3xl font-semibold text-[#9CA3AF]">{{ $stats['projects'] }}</p>
                <p class="mt-1 text-xs text-[#6B7280]">Disponible en fase 2</p>
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
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($organizations as $organization)
                        <li class="rounded-xl border border-[#E7E2D8] bg-white p-5 transition-colors hover:border-[#D8D0C3]">
                            <h3 class="text-sm font-semibold text-[#111827]">{{ $organization->name }}</h3>
                            @if ($organization->description)
                                <p class="mt-1 line-clamp-2 text-sm text-[#6B7280]">{{ $organization->description }}</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between text-xs text-[#6B7280]">
                                <span>{{ $organization->members_count }} miembros</span>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 font-medium',
                                    'bg-[#EFF6FF] text-[#2563EB]' => $organization->pivot->role === 'owner',
                                    'bg-[#F4F1EA] text-[#6B7280]' => $organization->pivot->role !== 'owner',
                                ])>
                                    {{ ucfirst($organization->pivot->role) }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-layouts.portal>
