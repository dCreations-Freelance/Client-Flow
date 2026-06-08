<x-layouts.admin title="Dashboard admin">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-[#B88746]">Panel administrativo</p>
            <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
            <p class="mt-3 text-[#6B7280]">Revisa qué necesita atención hoy.</p>
        </div>
        <a href="#" class="inline-flex rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">+ Nuevo</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Proyectos activos', $projects->where('status', 'in_progress')->count()], ['Esperando cliente', $projects->where('status', 'waiting_client')->count()], ['Entregables pend.', '0'], ['Comentarios', '0 sin responder']] as [$label, $value])
            <article class="rounded-[20px] border border-[#E7E2D8] bg-white p-5 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                <p class="text-sm text-[#6B7280]">{{ $label }}</p>
                <p class="mt-3 text-3xl font-semibold">{{ $value }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Proyectos que requieren atención</h2>
            <div class="mt-6 space-y-3">
                @forelse ($projects as $project)
                    <a href="{{ route('admin.projects.show', $project) }}" class="block rounded-2xl border border-[#E7E2D8] p-4 hover:bg-[#FAFAF7]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium">{{ $project->name }}</p>
                                <p class="mt-1 text-sm text-[#6B7280]">{{ $project->client->name }} · {{ str_replace('_', ' ', $project->status) }}</p>
                            </div>
                            <span class="text-sm font-semibold">{{ $project->progress }}%</span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl bg-[#F4F1EA] p-6 text-sm text-[#6B7280]">Aún no hay proyectos. Crea el primero desde acciones rápidas.</div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Acciones rápidas</h2>
            <div class="mt-5 space-y-3">
                @foreach ([['Nuevo cliente', route('admin.clients.create')], ['Invitar cliente', route('admin.clients.invite')], ['Nuevo proyecto', route('admin.projects.create')], ['Nueva actualización', '#']] as [$action, $href])
                    <a href="{{ $href }}" class="block rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm font-medium hover:border-[#D8D0C3] hover:bg-[#FAFAF7]">{{ $action }}</a>
                @endforeach
            </div>
        </aside>
    </div>

    <section class="mt-6 rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
        <h2 class="text-xl font-semibold">Actividad reciente</h2>
        <p class="mt-4 text-sm text-[#6B7280]">La actividad del portal se mostrará cuando existan clientes, proyectos y entregables.</p>
    </section>
</x-layouts.admin>
