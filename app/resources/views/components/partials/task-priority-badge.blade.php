{{--
    Badge de prioridad de tarea segun `TaskPriority::badgeClasses()`.
    Pensado para reutilizarse en cards de kanban y listados.
--}}
@props(['priority'])

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-medium ' . $priority->badgeClasses()]) }}>
    {{ $priority->label() }}
</span>
