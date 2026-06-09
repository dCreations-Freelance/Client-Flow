<x-layouts.portal title="Timeline">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">{{ $project->name }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">Timeline del proyecto</h1>
        <p class="mt-3 text-[#6B7280]">Avances publicados para que sepas qué se ha hecho y qué viene después.</p>
    </div>

    <section class="rounded-[24px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
        <div class="space-y-6">
            @forelse ($updates as $update)
                <article class="relative border-l border-[#E7E2D8] pl-6">
                    <span class="absolute -left-2 top-1 h-4 w-4 rounded-full bg-[#B88746]"></span>
                    <p class="text-xs font-medium text-[#9CA3AF]">{{ $update->published_at?->format('d/m/Y H:i') }}</p>
                    <h2 class="mt-3 text-lg font-semibold">{{ $update->title }}</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#6B7280]">{{ $update->content }}</p>
                </article>
            @empty
                <div class="rounded-[20px] border border-dashed border-[#D8D0C3] bg-[#FAFAF7] p-8 text-center">
                    <p class="text-lg font-semibold">Aún no hay avances publicados</p>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#6B7280]">Cuando el equipo publique una actualización visible para ti, aparecerá aquí ordenada por fecha.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-layouts.portal>
