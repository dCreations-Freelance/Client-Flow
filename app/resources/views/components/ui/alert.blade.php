{{--
    Alerta para mostrar mensajes flash de exito o error. Pensada para
    mostrarse en la parte superior de formularios. Las variantes cubren
    los cuatro semanticos del design system.
--}}
@props(['variant' => 'info'])

@php
    $classes = match ($variant) {
        'success' => 'bg-[#F0FDF4] text-[#16A34A] border-[#16A34A]',
        'error' => 'bg-[#FEF2F2] text-[#DC2626] border-[#DC2626]',
        'warning' => 'bg-[#FFFBEB] text-[#D97706] border-[#D97706]',
        default => 'bg-[#EFF6FF] text-[#2563EB] border-[#2563EB]',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm {$classes}"]) }} role="alert">
    {{ $slot }}
</div>
