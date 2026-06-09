<x-layouts.portal title="Avance visual">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-[#B88746]">{{ $project->name }}</p>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $entry->title }}</h1>
            <p class="mt-3 text-[#6B7280]">Publicado el {{ $entry->published_at?->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('portal.projects.visual-entries.index', $project) }}" class="rounded-xl border border-[#E7E2D8] bg-white px-5 py-3 text-sm font-semibold hover:bg-[#FAFAF7]">Volver al diario</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="overflow-hidden rounded-[24px] border border-[#E7E2D8] bg-white shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <div class="bg-[#111827] p-3">
                @if ($entry->isImage())
                    <img src="{{ route('visual-entries.media', $entry) }}" alt="{{ $entry->title }}" class="mx-auto max-h-[70vh] rounded-2xl object-contain">
                @elseif ($entry->isVideo())
                    <video src="{{ route('visual-entries.media', $entry) }}" controls class="mx-auto max-h-[70vh] w-full rounded-2xl"></video>
                @elseif ($entry->isAudio())
                    <div class="flex min-h-72 items-center justify-center rounded-2xl bg-[#F4F1EA] p-8">
                        <audio src="{{ route('visual-entries.media', $entry) }}" controls class="w-full max-w-xl"></audio>
                    </div>
                @endif
            </div>
        </section>

        <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
            <h2 class="text-xl font-semibold">Qué estás viendo</h2>
            <p class="mt-4 whitespace-pre-line text-sm leading-6 text-[#6B7280]">{{ $entry->description ?: 'Una evidencia visual del avance del proyecto.' }}</p>
            <div class="mt-5 rounded-2xl bg-[#FAFAF7] p-4 text-sm text-[#6B7280]">Tipo: {{ \App\Models\VisualEntry::TYPES[$entry->type] ?? $entry->type }}</div>
        </aside>
    </div>
</x-layouts.portal>
