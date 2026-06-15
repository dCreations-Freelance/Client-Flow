<x-layouts.admin title="Organizaciones">
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
                    name="status"
                    class="rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                    <option value="">Todos los estados</option>
                    <option value="active" @selected($status === 'active')>Activas</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactivas</option>
                </select>
                <button type="submit" class="rounded-lg bg-[#F4F1EA] px-3 py-2 text-sm font-medium text-[#111827] hover:bg-[#EDE9DE]">Filtrar</button>
            </form>

            <a href="{{ route('admin.organizations.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                Nueva organizacion
            </a>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="overflow-x-auto rounded-xl border border-[#E7E2D8] bg-white">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Miembros</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-[#6B7280]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E2D8]">
                    @forelse ($organizations as $organization)
                        <tr class="hover:bg-[#F4F1EA] transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-[#111827] hover:text-[#2563EB]">
                                    {{ $organization->name }}
                                </a>
                                <p class="text-xs text-[#6B7280]">{{ $organization->slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">{{ $organization->members()->count() }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-[#F0FDF4] text-[#16A34A]' => $organization->status->value === 'active',
                                    'bg-[#F4F1EA] text-[#6B7280]' => $organization->status->value === 'inactive',
                                ])>
                                    {{ $organization->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-[#6B7280]">
                                Aun no hay organizaciones. Crea la primera.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $organizations->links() }}
        </div>
    </div>
</x-layouts.admin>
