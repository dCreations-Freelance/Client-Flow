{{--
    Tile de resumen para el hub del proyecto. Muestra un valor
    principal con un subtitulo opcional y un enlace al destino
    relacionado.

    - `tone` define el color del valor principal. Default `neutral`
      usa el texto primary del design system. Los demas tonos
      sirven para alertar (danger: rojo, warning: naranja) o
      reforzar semantica (success: verde, primary: azul).
    - `icon` es una prop opcional que admite el nombre de un
      Heroicon (sin prefijo `Icon`). El partial no las importa
      para mantenerlo ligero; si la vista lo necesita, lo pinta
      con SVG inline en lugar de depender de un paquete.

    Uso:
        <x-partials.project-stat-tile
            title="Progreso"
            :value="$project->tasks_progress_percent.'%'"
            :sub="$project->completed_tasks_count.' de '.$project->total_tasks_count.' tareas'"
            :href="route('admin.projects.board', $project)"
            tone="primary"
        />
--}}
@props([
    'title' => '',
    'value' => '',
    'sub' => null,
    'href' => null,
    'tone' => 'neutral',
])

@php
    $toneClass = match ($tone) {
        'primary' => 'text-[#2563EB]',
        'success' => 'text-[#16A34A]',
        'warning' => 'text-[#D97706]',
        'danger' => 'text-[#DC2626]',
        default => 'text-[#111827]',
    };

    $hasBadge = isset($badge) && (int) $badge > 0;
@endphp

<x-ui.card :hover="(bool) $href">
    <div class="flex items-start justify-between gap-2">
        <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">{{ $title }}</p>

        @if ($hasBadge)
            <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[10px] font-semibold text-white">
                {{ $badge }}
            </span>
        @endif
    </div>

    <p class="mt-2 text-2xl font-semibold {{ $toneClass }}">{{ $value }}</p>

    @if ($sub)
        <p class="mt-1 text-xs text-[#6B7280]">{{ $sub }}</p>
    @endif

    @if ($href)
        <a href="{{ $href }}" class="sr-only">Ver {{ $title }}</a>
    @endif
</x-ui.card>
