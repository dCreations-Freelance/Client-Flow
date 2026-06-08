<x-layouts.admin title="Clientes">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Clientes</h1>
            <p class="mt-3 text-[#6B7280]">Gestiona los clientes con acceso al portal.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.clients.invite') }}" class="rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-sm font-semibold hover:border-[#D8D0C3]">Invitar cliente</a>
            <a href="{{ route('admin.clients.create') }}" class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Nuevo cliente</a>
        </div>
    </div>

    @if (session('status')) <div class="mb-6 rounded-xl bg-[#DCFCE7] px-4 py-3 text-sm text-[#15803D]">{{ session('status') }}</div> @endif

    <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-[#6B7280]"><tr><th class="pb-4">Cliente</th><th class="pb-4">Empresa</th><th class="pb-4">Email</th><th class="pb-4">Proyectos</th><th class="pb-4">Estado</th><th class="pb-4"></th></tr></thead>
                <tbody class="divide-y divide-[#E7E2D8]">
                    @forelse ($clients as $client)
                        <tr>
                            <td class="py-4 font-medium">{{ $client->name }}</td>
                            <td class="py-4 text-[#6B7280]">{{ $client->company ?: 'Sin empresa' }}</td>
                            <td class="py-4 text-[#6B7280]">{{ $client->email }}</td>
                            <td class="py-4">{{ $client->projects_count }}</td>
                            <td class="py-4"><span class="rounded-full bg-[#F4F1EA] px-3 py-1 text-xs font-medium">{{ $client->status }}</span></td>
                            <td class="py-4 text-right"><a href="{{ route('admin.clients.show', $client) }}" class="font-medium text-[#111827]">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-[#6B7280]">Aún no hay clientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $clients->links() }}</div>
    </section>
</x-layouts.admin>
