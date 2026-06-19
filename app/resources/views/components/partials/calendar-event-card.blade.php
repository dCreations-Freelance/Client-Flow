{{--
    Tarjeta compacta de un evento de calendario. Pensada para
    renderizarse dentro de las celdas del grid mensual o en la
    lista semanal.

    - Muestra el titulo truncado, la hora (o "Todo el dia") y un
      dot de color segun el tipo.
    - En modo `readOnly` oculta los botones de editar y eliminar.
    - En modo `compact` oculta la descripcion y los asistentes
      para encajar en celdas pequenas.

    Uso:
        <x-partials.calendar-event-card :event="$event" />
        <x-partials.calendar-event-card :event="$event" compact />
        <x-partials.calendar-event-card :event="$event" readOnly />
--}}
@props([
    'event',
    'compact' => false,
    'readOnly' => false,
])

@php
    $type = $event->type;
    $isAllDay = $event->is_all_day;
    $isVirtual = isset($event->is_virtual) && $event->is_virtual;
    $start = $event->starts_at;
@endphp

<div
    @class([
        'group flex flex-col gap-1 rounded-md border px-2 py-1.5 text-xs transition-colors',
        'border-[#E7E2D8] bg-white hover:border-[#D8D0C3] hover:bg-[#FAFAF7]',
        $isVirtual ? 'border-dashed' : '',
    ])
    wire:key="calendar-event-{{ $event->id }}"
>
    <div class="flex items-center gap-1.5">
        <span
            @class([
                'inline-block h-2 w-2 shrink-0 rounded-full',
                $type->color() === 'blue' ? 'bg-[#2563EB]' : '',
                $type->color() === 'green' ? 'bg-[#16A34A]' : '',
                $type->color() === 'orange' ? 'bg-[#D97706]' : '',
            ])
        ></span>
        <span class="truncate font-medium text-[#111827]" title="{{ $event->title }}">
            {{ $event->title }}
        </span>
    </div>

    @if (! $compact)
        <div class="flex items-center gap-2 text-[10px] text-[#6B7280]">
            @if ($isAllDay)
                <span>Todo el dia</span>
            @elseif ($start)
                <span>{{ $start->format('H:i') }}</span>
            @endif
            <x-partials.calendar-event-type-badge :type="$type" />
        </div>
    @endif

    @if (! $readOnly && ! $isVirtual)
        <div class="hidden gap-1 group-hover:flex">
            <button
                type="button"
                wire:click="openEditForm({{ is_object($event) && method_exists($event, 'getKey') ? $event->getKey() : $event->id }})"
                class="flex-1 rounded border border-[#E7E2D8] bg-white px-1.5 py-0.5 text-[10px] hover:bg-[#F4F1EA]"
            >
                Editar
            </button>
            <button
                type="button"
                wire:click="deleteEvent({{ is_object($event) && method_exists($event, 'getKey') ? $event->getKey() : $event->id }})"
                wire:confirm="Estas seguro de eliminar este evento?"
                class="rounded border border-[#FCA5A5] bg-white px-1.5 py-0.5 text-[10px] text-[#DC2626] hover:bg-[#FEF2F2]"
            >
                Eliminar
            </button>
        </div>
    @endif
</div>
