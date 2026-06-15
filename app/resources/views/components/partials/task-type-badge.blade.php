{{--
    Badge de tipo de tarea segun `TaskType::badgeClasses()`.
--}}
@props(['type'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium ' . $type->badgeClasses()]) }}>
    {{ $type->label() }}
</span>
