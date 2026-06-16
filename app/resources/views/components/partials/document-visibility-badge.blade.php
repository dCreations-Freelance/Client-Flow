{{--
    Badge de visibilidad de documento segun `DocumentVisibility::badgeClasses()`.

    Uso:
        <x-partials.document-visibility-badge :visibility="$document->visibility" />
--}}
@props(['visibility'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $visibility->badgeClasses()]) }}>
    {{ $visibility->label() }}
</span>
