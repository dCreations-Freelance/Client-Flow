{{--
    Input estandar con label y mensaje de error. Sigue el design system:
    borde gris, focus azul, placeholder tenue. Pensado para uso dentro
    de formularios de auth y admin.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
])

@php
    $hasError = $errors->has($name);
    $classes = 'w-full rounded-lg border bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 transition-colors '
        . ($hasError
            ? 'border-[#DC2626] focus:ring-[#DC2626]'
            : 'border-[#E7E2D8] focus:ring-[#2563EB] focus:border-transparent');
@endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-[#111827]">
            {{ $label }}
            @if ($required)<span class="text-[#DC2626]">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($required) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >

    @error($name)
        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
    @enderror
</div>
