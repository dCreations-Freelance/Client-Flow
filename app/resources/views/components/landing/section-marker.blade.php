{{--
    Encabezado editorial de sección: número + título + bajada.

    Refuerza la sensación de "reportaje" y permite a los usuarios
    que hacen scroll rápido ubicarse. Sin parpadeos: el contenido
    aparece cuando entra en viewport.
--}}
@props([
    'number',
    'eyebrow' => null,
    'align' => 'left', // left | center
])

@php
    $alignClass = $align === 'center' ? 'items-center text-center mx-auto' : 'items-start text-left';
@endphp

<div {{ $attributes->merge(['class' => "cf-reveal flex max-w-2xl flex-col gap-3 {$alignClass}"]) }}>
    <span class="cf-section-marker">
        <span class="font-mono text-[#8B5CF6]">{{ $number }}</span>
        <span>{{ $eyebrow }}</span>
    </span>
</div>
