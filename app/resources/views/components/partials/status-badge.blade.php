{{--
    Badge de status segun `ProjectStatus::badgeClasses()`. Pensado
    para reutilizarse en listados y detalles de proyecto.

    Uso:
        <x-partials.status-badge :status="$project->status" />
--}}
@props(['status'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $status->badgeClasses()]) }}>
    {{ $status->label() }}
</span>
