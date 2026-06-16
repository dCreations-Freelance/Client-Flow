<x-layouts.admin :title="$document->title">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.projects.documents.index', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                    &larr; Volver al listado
                </a>
                <h1 class="mt-2 truncate text-2xl font-semibold">{{ $document->title }}</h1>
                <p class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
                    <span class="truncate">{{ $project->name }}</span>
                    <span aria-hidden="true">·</span>
                    <x-partials.document-visibility-badge :visibility="$document->visibility" />
                    <span aria-hidden="true">·</span>
                    <span>Actualizado {{ $document->updated_at?->format('d/m/Y H:i') }}</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('admin.projects.documents.edit', [$project, $document]) }}"
                    class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                >
                    Editar
                </a>
                <form
                    method="post"
                    action="{{ route('admin.projects.documents.destroy', [$project, $document]) }}"
                    onsubmit="return confirm('Estas seguro de eliminar este documento?');"
                    class="inline"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#DC2626] hover:bg-[#F4F1EA]"
                    >
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        {{-- Metadatos --}}
        <x-ui.card>
            <dl class="grid gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Autor</dt>
                    <dd class="mt-1 text-sm text-[#111827]">{{ $document->creator?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Creado</dt>
                    <dd class="mt-1 text-sm text-[#111827]">{{ $document->created_at?->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">Actualizado</dt>
                    <dd class="mt-1 text-sm text-[#111827]">{{ $document->updated_at?->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Contenido renderizado --}}
        <x-ui.card>
            @if (trim($document->content ?? '') === '')
                <p class="text-sm italic text-[#9CA3AF]">Este documento aun no tiene contenido.</p>
            @else
                <x-partials.markdown-body :html="$document->rendered_content" />
            @endif
        </x-ui.card>
    </div>
</x-layouts.admin>
