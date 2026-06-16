<x-layouts.portal :title="'Documentos: '.$project->name">
    <div class="space-y-6">
        <div>
            <a href="{{ route('portal.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                &larr; Volver al detalle
            </a>
            <h1 class="mt-2 text-2xl font-semibold">Documentos</h1>
            <p class="text-sm text-[#6B7280]">
                {{ $project->name }} · {{ $project->organization?->name }}
            </p>
        </div>

        <p class="text-sm text-[#6B7280]">
            Documentacion publicada para que estes al dia del proyecto. Si necesitas algo concreto que no encuentras aqui, dejalo en el chat del proyecto.
        </p>

        {{-- Busqueda --}}
        <form method="get" action="{{ route('portal.projects.documents.index', $project) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wider text-[#6B7280]">Buscar</label>
                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Titulo o contenido..."
                    class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                    Buscar
                </button>
                @if ($search !== '')
                    <a
                        href="{{ route('portal.projects.documents.index', $project) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        {{-- Listado de documentos --}}
        <x-ui.card>
            @if ($documents->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F4F1EA]">
                        <svg class="h-6 w-6 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-[#111827]">
                        @if ($search !== '')
                            Sin resultados para "{{ $search }}"
                        @else
                            Aun no hay documentos publicos
                        @endif
                    </h3>
                    <p class="mt-1 text-sm text-[#6B7280]">
                        @if ($search === '')
                            Cuando el administrador publique un documento aparecera aqui.
                        @else
                            Prueba con otras palabras clave.
                        @endif
                    </p>
                </div>
            @else
                <ul class="divide-y divide-[#E7E2D8]">
                    @foreach ($documents as $document)
                        <li>
                            <a href="{{ route('portal.projects.documents.show', [$project, $document]) }}" class="block py-4 transition-colors hover:bg-[#F4F1EA] sm:px-2 sm:rounded-lg">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="truncate text-sm font-semibold text-[#111827]">
                                            {{ $document->title }}
                                        </h3>
                                        @if ($document->content)
                                            <p class="mt-1 line-clamp-2 text-sm text-[#6B7280]">
                                                {{ $document->excerpt(180) }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="shrink-0 text-right text-xs text-[#6B7280]">
                                        <time datetime="{{ $document->updated_at?->toIso8601String() }}">
                                            {{ $document->updated_at?->format('d/m/Y') }}
                                        </time>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.portal>
