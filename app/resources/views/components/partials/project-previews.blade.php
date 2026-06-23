{{--
    Bloque de previsualizaciones del hub del proyecto.

    Renderiza, dentro de la pagina de detalle, un resumen visual
    de las areas mas activas del proyecto (kanban, documentos,
    calendario, chat, equipo) sin necesidad de navegar a cada
    sub-pantalla. La vista padre solo tiene que pasar el DTO
    `ProjectSummary` ya construido por `ProjectSummaryService`.

    El layout es de dos columnas en desktop (`lg:grid-cols-3`)
    con la columna principal al 2/3 y la sidebar al 1/3. En
    movil se apilan en una sola columna.

    Variantes:
        - area: `admin` (default) muestra documentos privados y
          enlaces a gestion; `portal` oculta lo que el cliente
          no debe ver y usa copy mas tranquilizador.

    Uso:
        <x-partials.project-previews :summary="$summary" />
--}}
@props([
    'summary',
    'area' => null,
])

@php
    /** @var \App\DTOs\Project\ProjectSummary $summary */
    $area = $area ?? $summary->area();
    $project = $summary->project;
    $viewer = $summary->viewer;
    $isAdmin = $area === 'admin';
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-6 lg:grid-cols-3']) }}>
    {{-- Columna principal (2/3) --}}
    <div class="space-y-6 lg:col-span-2">
        {{-- Descripcion del proyecto --}}
        @if ($project->description)
            <x-ui.card>
                <h2 class="text-sm font-semibold text-[#111827]">
                    {{ $isAdmin ? 'Descripcion' : 'Sobre este proyecto' }}
                </h2>
                <p class="mt-2 whitespace-pre-line text-sm text-[#111827]">{{ $project->description }}</p>
            </x-ui.card>
        @endif

        {{-- Preview del kanban --}}
        <x-ui.card>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[#111827]">
                    {{ $isAdmin ? 'Tareas' : 'Como va el trabajo' }}
                </h2>
                @if (Route::has($area.'.projects.board'))
                    <a
                        href="{{ route($area.'.projects.board', $project) }}"
                        class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]"
                    >
                        {{ $isAdmin ? 'Abrir tablero completo' : 'Ver tablero' }}
                    </a>
                @endif
            </div>

            @if ($summary->boardPreview->isEmpty())
                <p class="mt-4 text-sm text-[#6B7280]">
                    {{ $isAdmin
                        ? 'Aun no se han creado columnas para este proyecto.'
                        : 'Estamos terminando de organizar las tareas del proyecto.' }}
                </p>
            @else
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($summary->boardPreview as $columnPreview)
                        @php
                            $column = $columnPreview->column;
                            $tasks = $columnPreview->previewTasks;
                            $count = $summary->columnCounts[$column->slug] ?? $tasks->count();
                        @endphp
                        <div class="flex flex-col rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-3">
                            <div class="flex items-center gap-2">
                                @if ($column->color)
                                    <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $column->color }}"></span>
                                @endif
                                <h3 class="truncate text-xs font-semibold text-[#111827]">{{ $column->name }}</h3>
                                <span class="ml-auto text-[10px] font-medium text-[#6B7280]">{{ $count }}</span>
                            </div>

                            @if ($tasks->isEmpty())
                                <p class="mt-3 text-[11px] text-[#9CA3AF]">
                                    {{ $isAdmin ? 'Sin tareas' : 'Vacio' }}
                                </p>
                            @else
                                <ul class="mt-3 space-y-1.5">
                                    @foreach ($tasks as $task)
                                        <li class="flex items-center gap-2 rounded-md border border-[#E7E2D8] bg-white px-2 py-1.5 text-[11px]">
                                            <span class="truncate text-[#111827]">{{ $task->title }}</span>
                                            @if ($task->priority?->color() === 'red' || $task->priority?->color() === 'orange')
                                                <span class="ml-auto shrink-0 inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-semibold {{ $task->priority->badgeClasses() }}">
                                                    {{ $task->priority->label() }}
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                    @if ($count > $tasks->count())
                                        <li class="text-[10px] text-[#6B7280]">
                                            +{{ $count - $tasks->count() }} {{ $isAdmin ? 'mas' : 'tareas mas' }}
                                        </li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Documentos recientes --}}
        <x-ui.card>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[#111827]">
                    {{ $isAdmin ? 'Documentos' : 'Documentos publicos' }}
                </h2>
                @if (Route::has($area.'.projects.documents.index'))
                    <a
                        href="{{ route($area.'.projects.documents.index', $project) }}"
                        class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]"
                    >
                        {{ $isAdmin ? 'Ver todos' : 'Ver documentacion' }}
                    </a>
                @endif
            </div>

            @if ($summary->previewDocuments->isEmpty())
                <p class="mt-4 text-sm text-[#6B7280]">
                    {{ $isAdmin
                        ? 'Todavia no hay documentos. Empieza creando uno desde el menu Documentos.'
                        : 'Cuando el administrador publique documentacion, aparecera aqui.' }}
                </p>
            @else
                <ul class="mt-4 divide-y divide-[#E7E2D8]">
                    @foreach ($summary->previewDocuments as $document)
                        <li class="flex items-start gap-3 py-3 text-sm">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F4F1EA] text-[#6B7280]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <a
                                    href="{{ route($area.'.projects.documents.show', [$project, $document]) }}"
                                    class="block truncate font-medium text-[#111827] hover:text-[#2563EB]"
                                >
                                    {{ $document->title }}
                                </a>
                                <p class="mt-0.5 line-clamp-1 text-xs text-[#6B7280]">
                                    {{ $document->excerpt(120) ?: 'Sin contenido todavia.' }}
                                </p>
                            </div>
                            @if ($isAdmin)
                                <x-partials.document-visibility-badge :visibility="$document->visibility" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    {{-- Sidebar (1/3) --}}
    <div class="space-y-6">
        {{-- Proximo evento --}}
        <x-ui.card>
            <h2 class="text-sm font-semibold text-[#111827]">
                {{ $isAdmin ? 'Proximo evento' : 'Proxima cita' }}
            </h2>

            @if ($summary->nextEvent)
                <div class="mt-4 flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg bg-[#EFF6FF] text-[#2563EB]">
                        <span class="text-[10px] font-medium uppercase">{{ $summary->nextEvent->starts_at->locale('es')->translatedFormat('M') }}</span>
                        <span class="text-sm font-semibold leading-none">{{ $summary->nextEvent->starts_at->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-[#111827]">{{ $summary->nextEvent->title }}</p>
                        <p class="mt-0.5 text-xs text-[#6B7280]">
                            {{ $summary->nextEvent->starts_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="mt-1 text-[10px] font-medium uppercase tracking-wider text-[#9CA3AF]">
                            {{ $summary->nextEvent->type->label() }}
                        </p>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-[#6B7280]">
                    {{ $isAdmin
                        ? 'No hay eventos proximos. Crea uno desde el calendario.'
                        : 'No tenemos citas pendientes. Te avisaremos cuando agendemos algo.' }}
                </p>
            @endif

            @if (Route::has($area.'.projects.calendar'))
                <a
                    href="{{ route($area.'.projects.calendar', $project) }}"
                    class="mt-3 inline-flex text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]"
                >
                    {{ $isAdmin ? 'Ver calendario' : 'Ver todas las citas' }}
                </a>
            @endif
        </x-ui.card>

        {{-- Ultimo mensaje --}}
        <x-ui.card>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[#111827]">
                    {{ $isAdmin ? 'Ultimo mensaje' : 'Conversacion' }}
                </h2>
                @if (Route::has($area.'.projects.chat'))
                    <a
                        href="{{ route($area.'.projects.chat', $project) }}"
                        class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]"
                    >
                        Ir al chat
                    </a>
                @endif
            </div>

            @if ($summary->latestMessage)
                @php
                    $message = $summary->latestMessage;
                    $author = $message->user;
                @endphp
                <div class="mt-4 flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2563EB] text-xs font-medium text-white">
                        {{ $author ? strtoupper(mb_substr($author->name, 0, 2)) : 'SY' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-medium text-[#111827]">
                                {{ $author?->name ?? 'Sistema' }}
                            </p>
                            <span class="text-[10px] text-[#9CA3AF]">
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="mt-1 line-clamp-2 text-xs text-[#6B7280]">
                            {{ $message->content }}
                        </p>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-[#6B7280]">
                    {{ $isAdmin
                        ? 'Aun no hay mensajes. Inicia la conversacion.'
                        : 'Cuando el equipo te escriba, aparecera aqui.' }}
                </p>
            @endif
        </x-ui.card>

        {{-- Equipo --}}
        <x-ui.card>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[#111827]">
                    {{ $isAdmin ? 'Equipo' : 'Tu equipo' }}
                </h2>
                <span class="text-xs text-[#6B7280]">
                    {{ $summary->totalMembers }} {{ $summary->totalMembers === 1 ? 'persona' : 'personas' }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($summary->previewMembers as $member)
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2563EB] text-xs font-medium text-white"
                        title="{{ $member->name }}"
                    >
                        {{ strtoupper(mb_substr($member->name, 0, 2)) }}
                    </div>
                @empty
                    <p class="text-sm text-[#6B7280]">
                        {{ $isAdmin
                            ? 'Todavia no hay miembros asignados.'
                            : 'Todavia no hay nadie asignado a este proyecto.' }}
                    </p>
                @endforelse

                @if ($summary->totalMembers > $summary->previewMembers->count())
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-dashed border-[#E7E2D8] bg-[#FAFAF7] text-[11px] font-medium text-[#6B7280]"
                        title="Hay {{ $summary->totalMembers - $summary->previewMembers->count() }} personas mas"
                    >
                        +{{ $summary->totalMembers - $summary->previewMembers->count() }}
                    </div>
                @endif
            </div>

            @if ($isAdmin)
                <a
                    href="{{ route('admin.projects.show', $project) }}#miembros"
                    class="mt-3 inline-flex text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]"
                >
                    Gestionar miembros
                </a>
            @endif
        </x-ui.card>
    </div>
</div>
