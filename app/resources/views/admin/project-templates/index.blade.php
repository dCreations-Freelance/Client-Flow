<x-layouts.admin :title="'Plantillas de proyecto'">
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        {{-- Cabecera con titulo y boton de crear --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-[#111827]">Plantillas de proyecto</h1>
                <p class="text-sm text-[#6B7280]">
                    Biblioteca de esqueletos reutilizables: columnas, tareas y documentos iniciales
                    que se copian al crear un proyecto.
                </p>
            </div>
            <a
                href="{{ route('admin.project-templates.create') }}"
                class="inline-flex items-center gap-1 rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva plantilla
            </a>
        </div>

        {{-- Filtros: search + chips de categoria --}}
        <form method="GET" action="{{ route('admin.project-templates.index') }}" class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Buscar por nombre o descripcion..."
                    class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm sm:max-w-sm"
                >
                <button
                    type="submit"
                    class="rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm font-medium text-[#111827] hover:bg-[#F4F1EA]"
                >
                    Buscar
                </button>
            </div>

            @if ($categories->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-[#6B7280]">Categoria:</span>
                    <a
                        href="{{ route('admin.project-templates.index', ['search' => $search]) }}"
                        @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                            'bg-[#111827] text-white' => $selectedCategory === '',
                            'bg-[#F4F1EA] text-[#6B7280] hover:bg-[#E7E2D8]' => $selectedCategory !== '',
                        ])
                    >
                        Todas
                    </a>
                    @foreach ($categories as $cat)
                        <a
                            href="{{ route('admin.project-templates.index', ['search' => $search, 'category' => $cat]) }}"
                            @class([
                                'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                                'bg-[#111827] text-white' => $selectedCategory === $cat,
                                'bg-[#F4F1EA] text-[#6B7280] hover:bg-[#E7E2D8]' => $selectedCategory !== $cat,
                            ])
                        >
                            {{ $cat }}
                        </a>
                    @endforeach
                </div>
            @endif
        </form>

        {{-- Listado de plantillas --}}
        @if ($templates->isEmpty())
            <x-ui.card>
                <p class="py-8 text-center text-sm text-[#6B7280]">
                    @if ($search !== '' || $selectedCategory !== '')
                        No hay plantillas que coincidan con los filtros.
                    @else
                        Aun no has creado ninguna plantilla. Empieza creando una para reutilizar columnas, tareas y documentos en tus proyectos.
                    @endif
                </p>
            </x-ui.card>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($templates as $template)
                    <x-ui.card>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <a
                                        href="{{ route('admin.project-templates.show', $template) }}"
                                        class="text-base font-semibold text-[#111827] hover:text-[#2563EB]"
                                    >
                                        {{ $template->name }}
                                    </a>
                                    @if ($template->category)
                                        <span class="inline-flex items-center rounded-full bg-[#F4F1EA] px-2 py-0.5 text-[10px] font-medium text-[#6B7280]">
                                            {{ $template->category }}
                                        </span>
                                    @endif
                                </div>
                                @if ($template->description)
                                    <p class="mt-1 line-clamp-2 text-xs text-[#6B7280]">
                                        {{ $template->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-[#6B7280]">
                                <span class="inline-flex items-center gap-1 rounded bg-[#F4F1EA] px-2 py-0.5">
                                    <span class="font-semibold text-[#111827]">{{ $template->column_count }}</span>
                                    {{ $template->column_count === 1 ? 'columna' : 'columnas' }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded bg-[#F4F1EA] px-2 py-0.5">
                                    <span class="font-semibold text-[#111827]">{{ $template->task_count }}</span>
                                    {{ $template->task_count === 1 ? 'tarea' : 'tareas' }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded bg-[#F4F1EA] px-2 py-0.5">
                                    <span class="font-semibold text-[#111827]">{{ $template->document_count }}</span>
                                    {{ $template->document_count === 1 ? 'doc' : 'docs' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                <a
                                    href="{{ route('admin.projects.create-from-template', $template) }}"
                                    class="inline-flex items-center gap-1 rounded-lg bg-[#2563EB] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#1D4ED8]"
                                >
                                    Crear proyecto
                                </a>
                                <div class="flex items-center gap-1">
                                    <a
                                        href="{{ route('admin.project-templates.edit', $template) }}"
                                        class="rounded p-1.5 text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                                        title="Editar"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.project-templates.destroy', $template) }}"
                                        onsubmit="return confirm('Eliminar esta plantilla y todos sus elementos?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded p-1.5 text-[#6B7280] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                                            title="Eliminar"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v9.546c0 .85-1.037 1.351-1.688 0L8.93 14.05m0 0 6.09-3.087" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
