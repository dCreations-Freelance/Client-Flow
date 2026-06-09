<x-layouts.admin title="Proyecto">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $project->name }}</h1>
            <p class="mt-3 text-[#6B7280]">{{ $project->client->name }} · {{ str_replace('_', ' ', $project->status) }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.projects.updates.create', $project) }}" class="rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">Nueva actualización</a>
            <a href="{{ route('admin.projects.edit', $project) }}" class="rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-sm font-semibold hover:bg-[#FAFAF7]">Editar proyecto</a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Estado general</h2>
            <div class="mt-6">
                <div class="mb-2 flex justify-between text-sm"><span class="text-[#6B7280]">Progreso</span><span class="font-medium">{{ $project->progress }}%</span></div>
                <div class="h-4 rounded-full bg-[#F4F1EA]"><div class="h-4 rounded-full bg-[#111827]" style="width: {{ $project->progress }}%"></div></div>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-[#FAFAF7] p-4"><dt class="text-sm text-[#6B7280]">Fase</dt><dd class="mt-1 font-medium">{{ $project->current_phase ?: 'Pendiente' }}</dd></div>
                <div class="rounded-2xl bg-[#FAFAF7] p-4"><dt class="text-sm text-[#6B7280]">Próximo hito</dt><dd class="mt-1 font-medium">{{ $project->next_milestone ?: 'Pendiente' }}</dd></div>
            </dl>
        </section>

        <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Acciones</h2>
            <div class="mt-5 space-y-3">
                <a href="{{ route('admin.projects.timeline', $project) }}" class="block rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm font-medium hover:bg-[#FAFAF7]">Ver timeline</a>
                <a href="{{ route('admin.projects.updates.create', $project) }}" class="block rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm font-medium hover:bg-[#FAFAF7]">Nueva actualización</a>
                <a href="{{ route('admin.projects.visual-entries.create', $project) }}" class="block rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm font-medium hover:bg-[#FAFAF7]">Nueva entrada visual</a>
                <a href="#" class="block rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm font-medium">Subir documento</a>
                <a href="#" class="block rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm font-medium">Crear entregable</a>
            </div>
        </aside>
    </div>
</x-layouts.admin>
