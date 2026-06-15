<x-layouts.admin title="Proyectos">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Buscar por nombre..."
                    class="w-full max-w-xs rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >

                <select
                    name="organization_id"
                    class="rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                    <option value="">Todas las organizaciones</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((string) $organization->id === $organizationId)>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="status"
                    class="rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                    <option value="">Todos los estados</option>
                    <option value="planning" @selected($status === 'planning')>Planificacion</option>
                    <option value="in_progress" @selected($status === 'in_progress')>En progreso</option>
                    <option value="on_hold" @selected($status === 'on_hold')>En pausa</option>
                    <option value="waiting_client" @selected($status === 'waiting_client')>Esperando cliente</option>
                    <option value="completed" @selected($status === 'completed')>Completado</option>
                    <option value="archived" @selected($status === 'archived')>Archivado</option>
                </select>

                <button type="submit" class="rounded-lg bg-[#F4F1EA] px-3 py-2 text-sm font-medium text-[#111827] hover:bg-[#EDE9DE]">Filtrar</button>
            </form>

            <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                Nuevo proyecto
            </a>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="overflow-x-auto rounded-xl border border-[#E7E2D8] bg-white">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Proyecto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Organizacion</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Progreso</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Miembros</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-[#6B7280]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E2D8]">
                    @forelse ($projects as $project)
                        <tr class="hover:bg-[#F4F1EA] transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.projects.show', $project) }}" class="font-medium text-[#111827] hover:text-[#2563EB]">
                                    {{ $project->name }}
                                </a>
                                <p class="text-xs text-[#6B7280]">{{ $project->slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">{{ $project->organization?->name }}</td>
                            <td class="px-4 py-3 text-sm">
                                <x-partials.status-badge :status="$project->status" />
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <x-partials.progress-bar :value="$project->progress" :showPercent="false" class="w-32" />
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">{{ $project->members_count }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.projects.show', $project) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-[#6B7280]">
                                Aun no hay proyectos. Crea el primero.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $projects->links() }}
        </div>
    </div>
</x-layouts.admin>
