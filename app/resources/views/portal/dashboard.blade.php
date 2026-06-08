<x-layouts.portal title="Dashboard cliente">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">Portal cliente</p>
        <h1 class="text-3xl font-semibold tracking-tight">Hola, {{ auth()->user()->name }}</h1>
        <p class="mt-3 text-[#6B7280]">Aquí verás el estado real de tus proyectos, avances visuales y entregables pendientes.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="rounded-[24px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">Tus proyectos</h2>
                    <p class="mt-2 text-sm text-[#6B7280]">Estos son los proyectos visibles para tu cuenta.</p>
                </div>
                <span class="rounded-full bg-[#F4F1EA] px-3 py-1 text-xs font-medium text-[#6B7280]">{{ $projects->count() }} activos</span>
            </div>

            <div class="mt-8 space-y-4">
                @forelse ($projects as $project)
                    <article class="rounded-[20px] border border-[#E7E2D8] bg-[#FAFAF7] p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold">{{ $project->name }}</h3>
                                <p class="mt-2 text-sm text-[#6B7280]">{{ $project->current_phase ?: 'Fase pendiente de definir' }}</p>
                            </div>
                            <span class="rounded-full bg-[#DBEAFE] px-3 py-1 text-xs font-medium text-[#1D4ED8]">{{ str_replace('_', ' ', $project->status) }}</span>
                        </div>
                        <div class="mt-5">
                            <div class="mb-2 flex justify-between text-sm"><span class="text-[#6B7280]">Progreso</span><span class="font-medium">{{ $project->progress }}%</span></div>
                            <div class="h-3 rounded-full bg-white"><div class="h-3 rounded-full bg-[#111827]" style="width: {{ $project->progress }}%"></div></div>
                        </div>
                        <p class="mt-4 text-sm text-[#6B7280]">Próximo hito: {{ $project->next_milestone ?: 'Pendiente de definir' }}</p>
                    </article>
                @empty
                    <div class="rounded-[20px] border border-dashed border-[#D8D0C3] bg-[#FAFAF7] p-8 text-center">
                        <p class="text-lg font-semibold">Todavía no hay proyectos visibles</p>
                        <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#6B7280]">Cuando tu equipo publique avances, verás aquí el progreso, el próximo hito y lo que requiere tu revisión.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-[24px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                <h2 class="text-xl font-semibold">Pendiente de tu parte</h2>
                <p class="mt-4 text-sm leading-6 text-[#6B7280]">No tienes aprobaciones ni comentarios pendientes.</p>
            </section>

            <section class="rounded-[24px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                <h2 class="text-xl font-semibold">Último avance</h2>
                <p class="mt-4 text-sm leading-6 text-[#6B7280]">El diario visual se activará cuando haya capturas, vídeos o notas publicadas para ti.</p>
            </section>
        </aside>
    </div>
</x-layouts.portal>
