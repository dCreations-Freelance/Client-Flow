<x-layouts.portal :title="$document->title">
    <div class="space-y-6">
        <div>
            <a href="{{ route('portal.projects.documents.index', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                &larr; Volver al listado
            </a>
            <h1 class="mt-2 text-2xl font-semibold">{{ $document->title }}</h1>
            <p class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
                <span class="truncate">{{ $project->name }}</span>
                <span aria-hidden="true">·</span>
                <span>Actualizado {{ $document->updated_at?->format('d/m/Y') }}</span>
            </p>
        </div>

        <x-ui.card>
            @if (trim($document->content ?? '') === '')
                <p class="text-sm italic text-[#9CA3AF]">Este documento no tiene contenido.</p>
            @else
                <x-partials.markdown-body :html="$document->rendered_content" />
            @endif
        </x-ui.card>
    </div>
</x-layouts.portal>
