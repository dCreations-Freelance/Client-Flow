<x-layouts.admin title="Proyectos">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-3xl font-semibold tracking-tight">Proyectos</h1><p class="mt-3 text-[#6B7280]">Gestiona proyectos y visibilidad del portal cliente.</p></div><a href="{{ route('admin.projects.create') }}" class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Nuevo proyecto</a></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($projects as $project)
            <article class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]"><div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">{{ $project->name }}</h2><p class="mt-2 text-sm text-[#6B7280]">Cliente: {{ $project->client->name }}</p></div><span class="rounded-full bg-[#F4F1EA] px-3 py-1 text-xs font-medium">{{ $project->status }}</span></div><div class="mt-5"><div class="mb-2 flex justify-between text-sm"><span class="text-[#6B7280]">Progreso</span><span class="font-medium">{{ $project->progress }}%</span></div><div class="h-3 rounded-full bg-[#F4F1EA]"><div class="h-3 rounded-full bg-[#111827]" style="width: {{ $project->progress }}%"></div></div></div><p class="mt-4 text-sm text-[#6B7280]">Próximo: {{ $project->next_milestone ?: 'Pendiente' }}</p><a href="{{ route('admin.projects.show', $project) }}" class="mt-5 inline-flex rounded-xl border border-[#E7E2D8] px-4 py-2 text-sm font-medium hover:bg-[#FAFAF7]">Ver proyecto</a></article>
        @empty
            <div class="rounded-[20px] border border-dashed border-[#D8D0C3] bg-white p-8 text-center text-[#6B7280] md:col-span-2 xl:col-span-3">Aún no hay proyectos.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $projects->links() }}</div>
</x-layouts.admin>
