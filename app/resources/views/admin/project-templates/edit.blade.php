<x-layouts.admin :title="'Editar plantilla: '.$template->name">
    <div class="space-y-6">
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

        <nav class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
            <a href="{{ route('admin.project-templates.index') }}" class="hover:text-[#111827]">Plantillas</a>
            <span>/</span>
            <a href="{{ route('admin.project-templates.show', $template) }}" class="hover:text-[#111827]">{{ $template->name }}</a>
            <span>/</span>
            <span class="text-[#111827]">Editar</span>
        </nav>

        {{-- Metadatos --}}
        <form method="POST" action="{{ route('admin.project-templates.update', $template) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-[#111827]">Metadatos</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-[#111827]">
                            Nombre <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $template->name) }}"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="mb-1 block text-sm font-medium text-[#111827]">
                            Categoria
                        </label>
                        <input
                            id="category"
                            name="category"
                            type="text"
                            value="{{ old('category', $template->category) }}"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                        @error('category')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-[#111827]">
                            Descripcion
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >{{ old('description', $template->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-ui.card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
                <a
                    href="{{ route('admin.project-templates.show', $template) }}"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]"
                >
                    Volver
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                >
                    Guardar metadatos
                </button>
            </div>
        </form>

        {{-- Columnas --}}
        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-[#111827]">
                Columnas del tablero
                <span class="ml-1 text-xs font-normal text-[#6B7280]">({{ $template->columns->count() }})</span>
            </h2>
            <p class="mb-4 text-xs text-[#6B7280]">
                Se crearan en el proyecto destino en el mismo orden.
            </p>

            @if ($template->columns->isNotEmpty())
                <ul class="mb-4 divide-y divide-[#E7E2D8]">
                    @foreach ($template->columns as $column)
                        <li class="flex items-center gap-3 py-2 text-sm">
                            <span class="w-6 text-right text-[10px] text-[#9CA3AF]">{{ $column->position }}</span>
                            @if ($column->color)
                                <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $column->color }}"></span>
                            @else
                                <span class="h-3 w-3 shrink-0 rounded-full border border-[#E7E2D8]"></span>
                            @endif
                            <span class="text-[#111827]">{{ $column->name }}</span>
                            <form
                                method="POST"
                                action="{{ route('admin.project-templates.columns.update', [$template, $column]) }}"
                                class="ml-auto flex items-center gap-2"
                            >
                                @csrf
                                @method('PUT')
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ $column->name }}"
                                    class="w-32 rounded border border-[#E7E2D8] bg-white px-2 py-1 text-xs"
                                    required
                                >
                                <input
                                    type="color"
                                    name="color"
                                    value="{{ $column->color ?? '#94A3B8' }}"
                                    class="h-7 w-7 cursor-pointer rounded border border-[#E7E2D8]"
                                >
                                <button
                                    type="submit"
                                    class="rounded px-2 py-1 text-xs font-medium text-[#2563EB] hover:bg-[#F4F1EA]"
                                >
                                    Guardar
                                </button>
                            </form>
                            <form
                                method="POST"
                                action="{{ route('admin.project-templates.columns.destroy', [$template, $column]) }}"
                                onsubmit="return confirm('Eliminar esta columna?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded p-1 text-[#6B7280] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                                    title="Eliminar"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v9.546c0 .85-1.037 1.351-1.688 0L8.93 14.05m0 0 6.09-3.087" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form
                method="POST"
                action="{{ route('admin.project-templates.columns.store', $template) }}"
                class="flex flex-col gap-2 sm:flex-row sm:items-end"
            >
                @csrf
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-[#6B7280]">Anadir columna</label>
                    <input
                        type="text"
                        name="name"
                        placeholder="Nombre de la columna"
                        required
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#6B7280]">Color</label>
                    <input
                        type="color"
                        name="color"
                        value="#94A3B8"
                        class="h-9 w-12 cursor-pointer rounded border border-[#E7E2D8]"
                    >
                </div>
                <button
                    type="submit"
                    class="rounded-lg bg-[#2563EB] px-3 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8] sm:self-end"
                >
                    Anadir
                </button>
            </form>
        </x-ui.card>

        {{-- Tareas predefinidas --}}
        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-[#111827]">
                Tareas predefinidas
                <span class="ml-1 text-xs font-normal text-[#6B7280]">({{ $template->tasks->count() }})</span>
            </h2>
            <p class="mb-4 text-xs text-[#6B7280]">
                Se crearan como tareas raiz en la columna seleccionada (por su posicion).
            </p>

            @if ($template->tasks->isNotEmpty())
                <ul class="mb-4 divide-y divide-[#E7E2D8]">
                    @foreach ($template->tasks as $task)
                        <li class="flex flex-col gap-2 py-2 text-sm sm:flex-row sm:items-center">
                            <span class="w-12 shrink-0 text-[10px] text-[#9CA3AF]">col {{ $task->column_position }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[#111827]">{{ $task->title }}</p>
                                @if ($task->description)
                                    <p class="truncate text-xs text-[#6B7280]">{{ $task->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px]">
                                <span class="inline-flex items-center rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[#6B7280]">
                                    {{ $task->type?->label() ?? '' }}
                                </span>
                                <span class="inline-flex items-center rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[#2563EB]">
                                    {{ $task->priority?->label() ?? '' }}
                                </span>
                                @if ($task->estimated_hours)
                                    <span class="inline-flex items-center rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[#6B7280]">
                                        {{ $task->estimated_hours }}h est.
                                    </span>
                                @endif
                            </div>
                            <form
                                method="POST"
                                action="{{ route('admin.project-templates.tasks.destroy', [$template, $task]) }}"
                                onsubmit="return confirm('Eliminar esta tarea?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded p-1 text-[#6B7280] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                                    title="Eliminar"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022-.165m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456m0 0a48.108 48.108 0 0 0-3.478-.397" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form
                method="POST"
                action="{{ route('admin.project-templates.tasks.store', $template) }}"
                class="space-y-3 rounded-lg border border-dashed border-[#E7E2D8] bg-[#FAFAF7] p-3"
            >
                @csrf
                <p class="text-xs font-medium text-[#6B7280]">Anadir tarea predefinida</p>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs text-[#6B7280]">Columna destino</label>
                        <select
                            name="column_position"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-2 py-1.5 text-sm"
                        >
                            @foreach ($template->columns as $column)
                                <option value="{{ $column->position }}">
                                    {{ $column->position }} - {{ $column->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-[#6B7280]">Tipo</label>
                        <select
                            name="type"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-2 py-1.5 text-sm"
                        >
                            @foreach (\App\Enums\TaskType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-[#6B7280]">Prioridad</label>
                        <select
                            name="priority"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-2 py-1.5 text-sm"
                        >
                            @foreach (\App\Enums\TaskPriority::cases() as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs text-[#6B7280]">Titulo</label>
                    <input
                        type="text"
                        name="title"
                        required
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm"
                    >
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-[#6B7280]">Descripcion (opcional)</label>
                        <textarea
                            name="description"
                            rows="2"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm"
                        ></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-[#6B7280]">Horas estimadas</label>
                        <input
                            type="number"
                            step="0.5"
                            min="0"
                            name="estimated_hours"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm"
                        >
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#2563EB] px-3 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                >
                    Anadir tarea
                </button>
            </form>
        </x-ui.card>

        {{-- Documentos --}}
        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-[#111827]">
                Documentos esqueleto
                <span class="ml-1 text-xs font-normal text-[#6B7280]">({{ $template->documents->count() }})</span>
            </h2>
            <p class="mb-4 text-xs text-[#6B7280]">
                Titulo y contenido que se copian al crear el proyecto.
            </p>

            @if ($template->documents->isNotEmpty())
                <ul class="mb-4 divide-y divide-[#E7E2D8]">
                    @foreach ($template->documents as $document)
                        <li class="flex items-center gap-3 py-2 text-sm">
                            <span class="w-6 text-right text-[10px] text-[#9CA3AF]">{{ $document->position }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[#111827]">{{ $document->title }}</p>
                                <p class="truncate text-[10px] text-[#6B7280]">
                                    {{ $document->isPublic() ? 'Publico (visible por el cliente)' : 'Privado' }}
                                </p>
                            </div>
                            <form
                                method="POST"
                                action="{{ route('admin.project-templates.documents.destroy', [$template, $document]) }}"
                                onsubmit="return confirm('Eliminar este documento?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded p-1 text-[#6B7280] hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                                    title="Eliminar"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022-.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 0 0 0-3.478-.397" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form
                method="POST"
                action="{{ route('admin.project-templates.documents.store', $template) }}"
                class="space-y-3 rounded-lg border border-dashed border-[#E7E2D8] bg-[#FAFAF7] p-3"
            >
                @csrf
                <p class="text-xs font-medium text-[#6B7280]">Anadir documento esqueleto</p>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-[#6B7280]">Titulo</label>
                        <input
                            type="text"
                            name="title"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-[#6B7280]">Visibilidad</label>
                        <select
                            name="visibility"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-2 py-1.5 text-sm"
                        >
                            <option value="private">Privado</option>
                            <option value="public">Publico (cliente)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs text-[#6B7280]">Contenido (markdown, opcional)</label>
                    <textarea
                        name="content"
                        rows="4"
                        placeholder="# Titulo del documento&#10;&#10;Texto introductorio..."
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 font-mono text-sm"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#2563EB] px-3 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                >
                    Anadir documento
                </button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
