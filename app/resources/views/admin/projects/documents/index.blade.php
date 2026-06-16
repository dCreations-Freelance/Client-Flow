<x-layouts.admin :title="'Documentos: '.$project->name">
    <div class="space-y-6">
        {{-- Breadcrumb y titulo --}}
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                    &larr; Volver al detalle
                </a>
                <h1 class="mt-2 truncate text-2xl font-semibold">Documentos</h1>
                <p class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
                    <span class="truncate">{{ $project->name }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $project->organization?->name }}</span>
                </p>
            </div>

            <a
                href="{{ route('admin.projects.documents.create', $project) }}"
                class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
            >
                + Nuevo documento
            </a>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        {{-- Filtros: busqueda + visibilidad --}}
        <form method="get" action="{{ route('admin.projects.documents.index', $project) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
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
            <div class="sm:w-48">
                <label for="visibility" class="mb-1 block text-xs font-medium uppercase tracking-wider text-[#6B7280]">Visibilidad</label>
                <select
                    id="visibility"
                    name="visibility"
                    class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                    <option value="">Todas</option>
                    <option value="public" @selected($visibility === 'public')>Publicos</option>
                    <option value="private" @selected($visibility === 'private')>Privados</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                    Filtrar
                </button>
                @if ($search !== '' || $visibility !== '')
                    <a
                        href="{{ route('admin.projects.documents.index', $project) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabla de documentos --}}
        <x-ui.card>
            @if ($documents->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F4F1EA]">
                        <svg class="h-6 w-6 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-[#111827]">Sin documentos todavia</h3>
                    <p class="mt-1 text-sm text-[#6B7280]">Crea el primer documento del proyecto para empezar a documentar.</p>
                    <a
                        href="{{ route('admin.projects.documents.create', $project) }}"
                        class="mt-4 inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                    >
                        Crear documento
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Titulo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Visibilidad</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Autor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Actualizado</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-[#6B7280]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E7E2D8]">
                            @foreach ($documents as $document)
                                <tr class="hover:bg-[#F4F1EA] transition-colors">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.projects.documents.show', [$project, $document]) }}" class="font-medium text-[#111827] hover:text-[#2563EB]">
                                            {{ $document->title }}
                                        </a>
                                        @if ($document->content)
                                            <p class="mt-0.5 line-clamp-1 text-xs text-[#6B7280]">{{ $document->excerpt(120) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-partials.document-visibility-badge :visibility="$document->visibility" />
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#6B7280]">
                                        {{ $document->creator?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#6B7280]">
                                        {{ $document->updated_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-3 text-sm">
                                            <a href="{{ route('admin.projects.documents.show', [$project, $document]) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Ver</a>
                                            <a href="{{ route('admin.projects.documents.edit', [$project, $document]) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Editar</a>
                                            <form
                                                method="post"
                                                action="{{ route('admin.projects.documents.destroy', [$project, $document]) }}"
                                                onsubmit="return confirm('Estas seguro de eliminar este documento?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-[#DC2626] hover:text-[#B91C1C]">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.admin>
