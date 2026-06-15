<x-layouts.admin title="Panel">
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-semibold">Panel de administracion</h1>
            <p class="mt-1 text-sm text-[#6B7280]">Resumen de la actividad reciente de tus clientes.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Organizaciones</p>
                <p class="mt-2 text-3xl font-semibold">{{ $stats['organizations'] }}</p>
                <p class="mt-1 text-xs text-[#6B7280]">{{ $stats['activeOrganizations'] }} activas</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Invitaciones pendientes</p>
                <p class="mt-2 text-3xl font-semibold">{{ $stats['pendingInvitations'] }}</p>
                <p class="mt-1 text-xs text-[#6B7280]">Esperando aceptacion</p>
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
        </div>

        <x-ui.card>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Organizaciones recientes</h2>
                <a href="{{ route('admin.organizations.index') }}" class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]">Ver todas</a>
            </div>

            <ul class="mt-4 divide-y divide-[#E7E2D8]">
                @forelse ($recentOrganizations as $organization)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-[#111827] hover:text-[#2563EB]">
                                {{ $organization->name }}
                            </a>
                            <p class="text-xs text-[#6B7280]">{{ $organization->members_count }} miembros</p>
                        </div>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-[#F0FDF4] text-[#16A34A]' => $organization->status->value === 'active',
                            'bg-[#F4F1EA] text-[#6B7280]' => $organization->status->value === 'inactive',
                        ])>
                            {{ $organization->status->label() }}
                        </span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-[#6B7280]">
                        Aun no hay organizaciones.
                        <a href="{{ route('admin.organizations.create') }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Crear la primera</a>
                    </li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</x-layouts.admin>
