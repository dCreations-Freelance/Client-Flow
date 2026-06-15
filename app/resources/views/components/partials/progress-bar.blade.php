{{--
    Barra de progreso segun `docs/DESIGN.md`. El valor se redondea a
    entero y se acota a 0-100 para evitar estilos rotos si llega
    fuera de rango.

    Uso:
        <x-partials.progress-bar :value="$project->progress" />
        <x-partials.progress-bar :value="$project->progress" label="Progreso" />
--}}
@props([
    'value' => 0,
    'label' => null,
    'showPercent' => true,
])

@php
    $value = max(0, min(100, (int) $value));
@endphp

<div {{ $attributes }}>
    @if ($label || $showPercent)
        <div class="mb-1 flex items-center justify-between text-sm">
            @if ($label)
                <span class="text-xs font-medium text-[#6B7280]">{{ $label }}</span>
            @else
                <span></span>
            @endif
            @if ($showPercent)
                <span class="text-xs font-medium text-[#111827]">{{ $value }}%</span>
            @endif
        </div>
    @endif

    <div class="h-2 w-full overflow-hidden rounded-full bg-[#F4F1EA]">
        <div
            class="h-full rounded-full bg-[#2563EB] transition-all"
            style="width: {{ $value }}%"
        ></div>
    </div>
</div>
