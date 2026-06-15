{{--
    Boton estandar con tres variantes: primary, secondary, danger.
    Mantiene la paleta warm y las esquinas redondeadas del design system.
--}}
@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'primary' => 'bg-[#2563EB] text-white hover:bg-[#1D4ED8]',
        'secondary' => 'border border-[#E7E2D8] bg-white text-[#111827] hover:bg-[#F4F1EA] hover:border-[#D8D0C3]',
        'danger' => 'bg-[#DC2626] text-white hover:bg-[#B91C1C]',
        default => 'bg-[#2563EB] text-white hover:bg-[#1D4ED8]',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition-colors {$classes}"]) }}
>
    {{ $slot }}
</button>
