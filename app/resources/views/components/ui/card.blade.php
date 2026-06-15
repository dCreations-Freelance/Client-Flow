{{--
    Card base con borde warm. Soporta `hover` para filas clickeables y
    `highlighted` para indicar seleccion.
--}}
@props([
    'hover' => false,
    'highlighted' => false,
])

@php
    $base = 'rounded-xl border border-[#E7E2D8] bg-white p-6';
    $hover && ($base .= ' hover:border-[#D8D0C3] hover:shadow-sm transition-all');
    $highlighted && ($base .= ' ring-2 ring-[#2563EB]');
@endphp

<div {{ $attributes->merge(['class' => $base]) }}>
    {{ $slot }}
</div>
