{{--
    Hero del detalle de proyecto: titulo, badges, breadcrumb, CTA
    principal y menu kebab de acciones admin.

    Patron actual (fase de rediseño de la UI):
    - El hero ya NO aloja los 7-9 botones de antes. Ahora solo
      lleva el titulo, el status, la organizacion, UN CTA
      principal (tipicamente "Abrir tablero") y un menu kebab
      "..." con el resto de acciones admin (Editar, Archivar).
    - Los 7-8 destinos secundarios (Chat, Documentos, etc.) se
      mueven al partial `project-nav` que vive justo debajo.

    El hero deja de ser `sticky`: esa responsabilidad pasa al
    nav strip. Asi evitamos dos barras pegajosas compitiendo por
    el mismo scroll.

    Slots:
        (ninguno: el padre controla todo via props)

    Props:
        - project: el modelo Project (con `organization` cargado).
        - crumbs: array para `<x-partials.project-breadcrumbs>`.
        - showStatus: si true, pinta el badge de status junto al
          titulo. Default true.
        - showArchived: si true y el proyecto esta archivado,
          pinta un badge "Archivado" adicional. Default true.
        - primaryAction: array con `['label' => '...', 'href' => '...']`
          que pinta el CTA azul principal a la derecha del titulo.
          Si es null, no se renderiza el boton (modo "no hay
          accion principal", util para vistas que no son el hub).
        - kebabActions: array de acciones para el menu kebab.
          Cada item es `['label' => '...', 'href' => '...',
          'method' => 'get'|'post', 'tone' => 'normal'|'danger']`.
          Si esta vacio o es null, el boton "..." no se muestra.
          - `get` (default) renderiza un enlace.
          - `post` renderiza un mini form con CSRF.
          - `tone` controla el color del texto: 'normal' usa el
            color por defecto, 'danger' usa rojo para acciones
            destructivas (Archivar).

    Uso:
        <x-partials.project-hero
            :project="$project"
            :crumbs="$crumbs"
            :primaryAction="['label' => 'Abrir tablero', 'href' => route('admin.projects.board', $project)]"
            :kebabActions="[
                ['label' => 'Editar', 'href' => route('admin.projects.edit', $project)],
                ['label' => 'Archivar', 'href' => route('admin.projects.archive', $project), 'method' => 'post', 'tone' => 'danger'],
            ]"
        />
--}}
@props([
    'project',
    'crumbs' => [],
    'showStatus' => true,
    'showArchived' => true,
    'primaryAction' => null,
    'kebabActions' => null,
])

<header {{ $attributes->merge(['class' => '-mx-6 mb-2 px-6 pt-6 pb-4 lg:-mx-8 lg:px-8']) }}>
    <div class="space-y-3">
        <x-partials.project-breadcrumbs :crumbs="$crumbs" />

        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            {{-- Columna izquierda: titulo + status + organizacion --}}
            <div class="min-w-0 flex-1 space-y-2">
                {{--
                    Titulo y status en una sola linea. `flex-wrap` solo
                    en pantallas pequenas para que el titulo baje
                    junto al badge si no caben. `truncate` evita
                    que el titulo se rompa a dos lineas (problema
                    que tenia la version anterior con muchos
                    botones compitiendo por el ancho).
                --}}
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-2xl font-semibold text-[#111827] md:text-3xl" title="{{ $project->name }}">
                        {{ $project->name }}
                    </h1>

                    @if ($showStatus)
                        <x-partials.status-badge :status="$project->status" />
                    @endif

                    @if ($showArchived && $project->isArchived())
                        <span class="inline-flex items-center rounded-full bg-[#F4F1EA] px-2.5 py-0.5 text-xs font-medium text-[#6B7280]">
                            Archivado
                        </span>
                    @endif
                </div>

                {{--
                    Organizacion en una sola linea sin romper
                    palabras. Si el nombre de la org es largo se
                    trunca con "..." en vez de partirse en
                    multiples lineas (como hacia la version
                    anterior). El titulo `title` muestra el
                    nombre completo al hacer hover.
                --}}
                @if ($project->organization && ($project->is_visible_to_client || auth()->user()?->isAdmin()))
                    <p class="truncate text-sm text-[#6B7280]">
                        <span class="text-[#9CA3AF]">Organizacion:</span>
                        <span class="font-medium text-[#111827]" title="{{ $project->organization->name }}">{{ $project->organization->name }}</span>
                    </p>
                @endif
            </div>

            {{-- Columna derecha: CTA principal + menu kebab --}}
            @if ($primaryAction || ! empty($kebabActions))
                <div class="flex shrink-0 items-center gap-2">
                    {{-- CTA principal --}}
                    @if ($primaryAction)
                        <a
                            href="{{ $primaryAction['href'] }}"
                            class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-[#1D4ED8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-2"
                        >
                            {{ $primaryAction['label'] }}
                        </a>
                    @endif

                    {{--
                        Menu kebab con <details>/<summary>: cero
                        JavaScript, accesible (teclado funciona
                        nativamente) y robusto. El icono son tres
                        puntos horizontales en SVG. Al abrirse
                        muestra los items con su tono (normal /
                        danger).
                    --}}
                    @if (! empty($kebabActions))
                        <details class="relative" {{ $attributes->only('kebabId') }}>
                            <summary
                                class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-[#E7E2D8] bg-white text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden"
                                aria-label="Mas opciones"
                                title="Mas opciones"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                </svg>
                            </summary>

                            {{--
                                Panel del menu. `absolute right-0`
                                lo alinea al borde derecho del
                                summary. `min-w-[12rem]` garantiza
                                un ancho minimo legible. El borde y
                                la sombra lo separan del fondo.
                            --}}
                            <div class="absolute right-0 z-30 mt-2 min-w-[12rem] overflow-hidden rounded-lg border border-[#E7E2D8] bg-white py-1 shadow-lg">
                                @foreach ($kebabActions as $action)
                                    @php
                                        $tone = $action['tone'] ?? 'normal';
                                        $toneClass = $tone === 'danger'
                                            ? 'text-[#DC2626] hover:bg-[#FEF2F2]'
                                            : 'text-[#111827] hover:bg-[#F4F1EA]';
                                        $method = strtoupper($action['method'] ?? 'get');
                                    @endphp

                                    @if ($method === 'GET')
                                        <a
                                            href="{{ $action['href'] }}"
                                            class="block px-4 py-2 text-sm font-medium transition-colors {{ $toneClass }}"
                                        >
                                            {{ $action['label'] }}
                                        </a>
                                    @else
                                        {{--
                                            Accion no-GET (tipicamente
                                            POST para archivar).
                                            Renderizamos un mini
                                            form con CSRF. El estilo
                                            del boton imita al
                                            enlace para que la
                                            jerarquia visual sea
                                            consistente.
                                        --}}
                                        <form
                                            method="POST"
                                            action="{{ $action['href'] }}"
                                            onsubmit="return confirm('{{ $action['confirm'] ?? 'Estas seguro?' }}')"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="block w-full px-4 py-2 text-left text-sm font-medium transition-colors {{ $toneClass }}"
                                            >
                                                {{ $action['label'] }}
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            @endif
        </div>
    </div>
</header>
