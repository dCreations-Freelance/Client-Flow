<x-layouts.portal title="Diario visual">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-[#B88746]">{{ $project->name }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">Diario visual</h1>
        <p class="mt-3 text-[#6B7280]">Avances visuales publicados para que veas cómo evoluciona el proyecto.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($entries as $entry)
            <article class="overflow-hidden rounded-[24px] border border-[#E7E2D8] bg-white shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
                <a href="{{ route('portal.projects.visual-entries.show', [$project, $entry]) }}" class="block">
                    <div class="flex aspect-video items-center justify-center bg-[#F4F1EA]">
                        @if ($entry->isImage())
                            <img src="{{ route('visual-entries.media', $entry) }}" alt="{{ $entry->title }}" class="h-full w-full object-cover">
                        @elseif ($entry->isVideo())
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-semibold">Ver video</span>
                        @else
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-semibold">Escuchar audio</span>
                        @endif
                    </div>
                    <div class="p-5">
                        <span class="rounded-full bg-[#F4F1EA] px-3 py-1 text-xs font-medium">{{ \App\Models\VisualEntry::TYPES[$entry->type] ?? $entry->type }}</span>
                        <h2 class="mt-4 text-lg font-semibold">{{ $entry->title }}</h2>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-[#6B7280]">{{ $entry->description ?: 'Ver avance del proyecto.' }}</p>
                    </div>
                </a>
            </article>
        @empty
            <div class="rounded-[20px] border border-dashed border-[#D8D0C3] bg-white p-8 text-center md:col-span-2 xl:col-span-3">
                <p class="text-lg font-semibold">Aún no hay entradas visuales</p>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#6B7280]">Cuando el equipo publique capturas, videos o audios para ti, aparecerán aquí.</p>
            </div>
        @endforelse
    </div>
</x-layouts.portal>
