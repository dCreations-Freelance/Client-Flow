{{--
    Avatar de usuario para el chat.

    Muestra las iniciales del nombre sobre un fondo cuyo color se
    deriva de un hash determinista del nombre. Asi cada usuario
    tiene un color estable que se distingue a simple vista.

    Uso:
        <x-partials.chat-user-avatar :user="$message->user" size="sm" />
--}}
@props([
    'user',
    'size' => 'sm',  // sm | md
])

@php
    $palette = ['#2563EB', '#16A34A', '#D97706', '#8B5CF6', '#DB2777'];
    $hash = crc32((string) $user?->name);
    $color = $palette[$hash % count($palette)];
    $initials = strtoupper(mb_substr((string) $user?->name, 0, 2));
    $sizeClass = $size === 'md' ? 'h-9 w-9 text-sm' : 'h-8 w-8 text-xs';
@endphp

<div
    {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center rounded-full font-medium text-white {$sizeClass}"]) }}
    style="background-color: {{ $color }};"
    title="{{ $user?->name }}"
>
    {{ $initials }}
</div>
