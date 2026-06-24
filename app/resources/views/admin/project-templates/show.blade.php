<x-layouts.admin :title="'Plantilla: '.$template->name">
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
            <span class="text-[#111827]">{{ $template->name }}</span>
        </nav>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-[#111827]">{{ $template->name }}</h1>
                @if ($template->category)
                    <span class="mt-1 inline-flex items-center rounded-full bg-[#F4F1EA] px-2 py-0.5 text-xs font-medium text-[#6B7280]">
                        {{ $template->category }}
                    </span>
                @endif
                @if ($template->description)
                    <p class="mt-2 max-w-2xl text-sm text-[#6B7280]">{{ $template->description }}</p>
                @endif
            </div>
            <a
                href="{{ route('admin.projects.create-from-template', $template) }}"
                class="inline-flex items-center gap-1 rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Crear proyecto desde esta plantilla
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Columnas --}}
            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-[#111827]">
                    Columnas
                    <span class="ml-1 text-xs font-normal text-[#6B7280]">({{ $template->columns->count() }})</span>
                </h2>
                @if ($template->columns->isEmpty())
                    <p class="text-xs text-[#6B7280]">Sin columnas.</p>
                @else
                    <ol class="space-y-1 text-sm">
                        @foreach ($template->columns as $column)
                            <li class="flex items-center gap-2">
                                @if ($column->color)
                                    <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $column->color }}"></span>
                                @endif
                                <span class="text-[#111827]">{{ $column->name }}</span>
                                <span class="ml-auto text-[10px] text-[#9CA3AF]">pos {{ $column->position }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>

            {{-- Tareas predefinidas --}}
            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-[#111827]">
                    Tareas
                    <span class="ml-1 text-xs font-normal text-[#6B7280]">({{ $template->tasks->count() }})</span>
                </h2>
                @if ($template->tasks->isEmpty())
                    <p class="text-xs text-[#6B7280]">Sin tareas predefinidas.</p>
                @else
                    <ul class="space-y-1 text-xs">
                        @foreach ($template->tasks as $task)
                            <li class="flex items-center gap-2">
                                <span class="inline-flex shrink-0 items-center rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[10px] text-[#6B7280]">
                                    col {{ $task->column_position }}
                                </span>
                                <span class="truncate text-[#111827]">{{ $task->title }}</span>
                                <span class="ml-auto inline-flex items-center rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[10px] text-[#2563EB]">
                                    {{ $task->priority?->label() ?? '' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Documentos --}}
            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-[#111827]">
                    Documentos
                    <span class="ml-1 text-xs font-normal text-[#6B7280]">({{ $template->documents->count() }})</span>
                </h2>
                @if ($template->documents->isEmpty())
                    <p class="text-xs text-[#6B7280]">Sin documentos esqueleto.</p>
                @else
                    <ul class="space-y-1 text-xs">
                        @foreach ($template->documents as $document)
                            <li class="flex items-center gap-2">
                                <span class="truncate text-[#111827]">{{ $document->title }}</span>
                                @if ($document->isPublic())
                                    <span class="ml-auto inline-flex items-center rounded bg-[#F0FDF4] px-1.5 py-0.5 text-[10px] text-[#16A34A]">
                                        Publico
                                    </span>
                                @else
                                    <span class="ml-auto inline-flex items-center rounded bg-[#F4F1EA] px-1.5 py-0.5 text-[10px] text-[#6B7280]">
                                        Privado
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <div class="text-right">
            <a href="{{ route('admin.project-templates.edit', $template) }}" class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                Editar plantilla &rarr;
            </a>
        </div>
    </div>
</x-layouts.admin>
