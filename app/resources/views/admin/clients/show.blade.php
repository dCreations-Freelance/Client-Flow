<x-layouts.admin title="Detalle cliente">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-3xl font-semibold tracking-tight">{{ $client->name }}</h1><p class="mt-3 text-[#6B7280]">{{ $client->company ?: 'Sin empresa' }} · {{ $client->status }}</p></div>
        <a href="{{ route('admin.clients.edit', $client) }}" class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Editar cliente</a>
    </div>
    @if (session('status')) <div class="mb-6 rounded-xl bg-[#DCFCE7] px-4 py-3 text-sm text-[#15803D]">{{ session('status') }}</div> @endif
    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Proyectos</h2>
            <div class="mt-5 space-y-3">
                @forelse ($client->projects as $project)
                    <a href="{{ route('admin.projects.show', $project) }}" class="block rounded-2xl border border-[#E7E2D8] p-4 hover:bg-[#FAFAF7]"><span class="font-medium">{{ $project->name }}</span><span class="ml-3 text-sm text-[#6B7280]">{{ $project->status }} · {{ $project->progress }}%</span></a>
                @empty
                    <p class="rounded-2xl bg-[#F4F1EA] p-5 text-sm text-[#6B7280]">Este cliente aún no tiene proyectos.</p>
                @endforelse
            </div>
        </section>
        <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Información</h2>
            <dl class="mt-5 space-y-4 text-sm"><div><dt class="text-[#6B7280]">Email</dt><dd class="font-medium">{{ $client->email }}</dd></div><div><dt class="text-[#6B7280]">Teléfono</dt><dd class="font-medium">{{ $client->phone ?: 'No indicado' }}</dd></div><div><dt class="text-[#6B7280]">Cuenta</dt><dd class="font-medium">{{ $client->user ? 'Activa' : 'Sin usuario' }}</dd></div><div><dt class="text-[#6B7280]">Invitación</dt><dd class="font-medium">{{ $client->invitation_status }}</dd></div></dl>
        </aside>
    </div>
</x-layouts.admin>
