{{--
    Badge del tipo de evento de calendario segun
    `CalendarEventType::badgeClasses()`. Pensado para reutilizarse
    en tarjetas del calendario y listados de eventos.

    Uso:
        <x-partials.calendar-event-type-badge :type="$event->type" />
--}}
@props(['type'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ' . $type->badgeClasses()]) }}>
    {{ $type->label() }}
</span>
