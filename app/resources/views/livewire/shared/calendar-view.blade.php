{{--
    Vista del componente Livewire del calendario por proyecto.

    Estructura:
    - Header: navegacion (anterior / "Mes Y" / siguiente), boton
      "Hoy", switch Mes/Semana, y boton "Nuevo evento" (solo en
      admin).
    - Grid mensual: 7 columnas (L M X J V S D) x 6 filas. Cada
      celda muestra hasta 3 eventos y un "+N mas" si hay mas.
      Click en celda vacia abre el modal de creacion con la
      fecha pre-rellenada.
    - Lista semanal: 7 secciones, una por dia, con todos los
      eventos.
    - Modal de evento: formulario completo (titulo, tipo,
      descripcion, fechas, all-day, attendees).
    - Modal de "ver mas" del dia: lista completa de eventos.

    El JS es minimo e inline: los handlers de Livewire (`wire:click`,
    `wire:model`) cubren toda la logica de negocio. Solo se
    anaden dos atajos: Esc cierra el modal.
--}}
<div class="space-y-4">
    {{-- Header: navegacion, switch, nuevo evento --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="shiftPeriod(-1)"
                class="rounded-lg border border-[#E7E2D8] bg-white p-2 text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                title="Anterior"
            >
                &larr;
            </button>

            <h2 class="min-w-[180px] text-center text-lg font-semibold">
                @if ($view === 'week')
                    Semana del {{ $carbonDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('d/m/Y') }}
                @else
                    {{ $carbonDate->copy()->startOfMonth()->translatedFormat('F Y') }}
                @endif
            </h2>

            <button
                type="button"
                wire:click="shiftPeriod(1)"
                class="rounded-lg border border-[#E7E2D8] bg-white p-2 text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                title="Siguiente"
            >
                &rarr;
            </button>

            <button
                type="button"
                wire:click="goToToday"
                class="ml-2 rounded-lg border border-[#E7E2D8] bg-white px-3 py-1.5 text-xs font-medium hover:bg-[#F4F1EA]"
            >
                Hoy
            </button>
        </div>

        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-lg border border-[#E7E2D8] bg-white p-0.5">
                <button
                    type="button"
                    wire:click="setView('month')"
                    @class([
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        $view === 'month' ? 'bg-[#2563EB] text-white' : 'text-[#6B7280] hover:bg-[#F4F1EA]',
                    ])
                >
                    Mes
                </button>
                <button
                    type="button"
                    wire:click="setView('week')"
                    @class([
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        $view === 'week' ? 'bg-[#2563EB] text-white' : 'text-[#6B7280] hover:bg-[#F4F1EA]',
                    ])
                >
                    Semana
                </button>
            </div>

            @if (! $readOnly)
                <button
                    type="button"
                    wire:click="openCreateForm('{{ $carbonDate->format('Y-m-d') }}')"
                    class="inline-flex items-center rounded-lg bg-[#2563EB] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#1D4ED8]"
                >
                    + Nuevo evento
                </button>
            @endif
        </div>
    </div>

    {{-- Leyenda --}}
    <div class="flex flex-wrap items-center gap-3 text-xs text-[#6B7280]">
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 rounded-full bg-[#2563EB]"></span>
            <span>Reunion</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 rounded-full bg-[#16A34A]"></span>
            <span>Hito</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 rounded-full bg-[#D97706]"></span>
            <span>Fecha limite (tarea)</span>
        </div>
    </div>

    {{-- Grid mensual --}}
    @if ($view === 'month')
        <div class="overflow-hidden rounded-xl border border-[#E7E2D8] bg-white">
            {{-- Cabecera con dias de la semana --}}
            <div class="grid grid-cols-7 border-b border-[#E7E2D8] bg-[#FAFAF7] text-center text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">
                @foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $dayLabel)
                    <div class="px-2 py-2">{{ $dayLabel }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach ($days as $day)
                    @php
                        $dateKey = $day->format('Y-m-d');
                        $dayItems = collect($eventsByDay[$dateKey] ?? [])->merge($deadlinesByDay[$dateKey] ?? []);
                        $visibleItems = $dayItems->take(3);
                        $hiddenCount = $dayItems->count() - $visibleItems->count();
                    @endphp
                    <div
                        @class([
                            'group relative min-h-[110px] border-b border-r border-[#E7E2D8] p-1.5 text-xs',
                            ! $isCurrentMonth($day) ? 'bg-[#FAFAF7] text-[#9CA3AF]' : 'bg-white',
                        ])
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span @class([
                                'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[10px] font-medium',
                                $day->isToday() ? 'bg-[#2563EB] text-white' : '',
                            ])>
                                {{ $day->format('j') }}
                            </span>
                            @if (! $readOnly)
                                <button
                                    type="button"
                                    wire:click="openCreateForm('{{ $dateKey }}')"
                                    class="hidden h-5 w-5 items-center justify-center rounded text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827] group-hover:flex"
                                    title="Nuevo evento en este dia"
                                >
                                    +
                                </button>
                            @endif
                        </div>

                        <div class="space-y-1">
                            @foreach ($visibleItems as $item)
                                <x-partials.calendar-event-card :event="$item" compact :readOnly="$readOnly" />
                            @endforeach

                            @if ($hiddenCount > 0)
                                <button
                                    type="button"
                                    x-data
                                    x-on:click="$dispatch('open-day-detail', { date: '{{ $dateKey }}' })"
                                    class="block w-full rounded px-1.5 py-0.5 text-left text-[10px] text-[#2563EB] hover:bg-[#EFF6FF]"
                                >
                                    +{{ $hiddenCount }} mas
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- Lista semanal --}}
        <div class="space-y-3">
            @foreach ($days as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $dayItems = collect($eventsByDay[$dateKey] ?? [])->merge($deadlinesByDay[$dateKey] ?? []);
                @endphp
                <div class="rounded-xl border border-[#E7E2D8] bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">
                            {{ $day->locale('es')->translatedFormat('l, d \d\e F') }}
                            @if ($day->isToday())
                                <span class="ml-2 inline-flex items-center rounded-full bg-[#EFF6FF] px-2 py-0.5 text-[10px] font-medium text-[#2563EB]">Hoy</span>
                            @endif
                        </h3>

                        @if (! $readOnly)
                            <button
                                type="button"
                                wire:click="openCreateForm('{{ $dateKey }}')"
                                class="text-xs text-[#2563EB] hover:text-[#1D4ED8]"
                            >
                                + Nuevo evento
                            </button>
                        @endif
                    </div>

                    @if ($dayItems->isEmpty())
                        <p class="text-xs text-[#9CA3AF]">Sin eventos programados.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($dayItems as $item)
                                <x-partials.calendar-event-card :event="$item" :readOnly="$readOnly" />
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal de crear/editar evento --}}
    @if ($eventForm)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            x-data
            x-on:keydown.escape.window="$wire.call('closeForm')"
        >
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                <form wire:submit.prevent="saveEvent" class="space-y-4 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">
                            {{ ($eventForm['mode'] ?? null) === 'edit' ? 'Editar evento' : 'Nuevo evento' }}
                        </h3>
                        <button
                            type="button"
                            wire:click="closeForm"
                            class="text-[#6B7280] hover:text-[#111827]"
                        >
                            &times;
                        </button>
                    </div>

                    {{-- Titulo --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#6B7280]">Titulo</label>
                        <input
                            type="text"
                            wire:model.defer="eventForm.title"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                            maxlength="200"
                        />
                        @error('eventForm.title')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#6B7280]">Tipo</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($eventTypes as $value => $label)
                                <label
                                    @class([
                                        'flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm',
                                        $eventForm['type'] === $value
                                            ? 'border-[#2563EB] bg-[#EFF6FF] text-[#2563EB]'
                                            : 'border-[#E7E2D8] bg-white text-[#6B7280] hover:bg-[#F4F1EA]',
                                    ])
                                >
                                    <input
                                        type="radio"
                                        wire:model.defer="eventForm.type"
                                        value="{{ $value }}"
                                        class="sr-only"
                                    />
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Descripcion --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#6B7280]">Descripcion (opcional)</label>
                        <textarea
                            wire:model.defer="eventForm.description"
                            rows="3"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                            maxlength="5000"
                        ></textarea>
                    </div>

                    {{-- All-day --}}
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            wire:model.live="eventForm.is_all_day"
                            class="h-4 w-4 rounded border-[#E7E2D8] text-[#2563EB]"
                        />
                        <span>Todo el dia</span>
                    </label>

                    {{-- Fechas --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-[#6B7280]">Inicio</label>
                            <input
                                type="{{ ($eventForm['is_all_day'] ?? false) ? 'date' : 'datetime-local' }}"
                                wire:model.defer="eventForm.starts_at"
                                @if (($eventForm['is_all_day'] ?? false))
                                    value="{{ \Carbon\Carbon::parse($eventForm['starts_at'] ?? now())->format('Y-m-d') }}"
                                @endif
                                class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                            />
                            @error('eventForm.starts_at')
                                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-[#6B7280]">Fin (opcional)</label>
                            <input
                                type="{{ ($eventForm['is_all_day'] ?? false) ? 'date' : 'datetime-local' }}"
                                wire:model.defer="eventForm.ends_at"
                                class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20"
                            />
                            @error('eventForm.ends_at')
                                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Asistentes --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#6B7280]">Asistentes</label>
                        <div class="space-y-2 rounded-lg border border-[#E7E2D8] bg-white p-2">
                            @if (count($attendeeIds) > 0)
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($availableAttendees->whereIn('id', $attendeeIds) as $attendee)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#EFF6FF] px-2 py-0.5 text-xs text-[#2563EB]">
                                            {{ $attendee->name }}
                                            <button
                                                type="button"
                                                wire:click="$set('attendeeIds', {{ json_encode(array_values(array_diff($attendeeIds, [$attendee->id]))) }})"
                                                class="text-[#2563EB] hover:text-[#DC2626]"
                                            >&times;</button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <select
                                wire:change="addAttendee($event.target.value); $event.target.value = ''"
                                class="w-full rounded-lg border border-[#E7E2D8] bg-white px-2 py-1.5 text-xs"
                            >
                                <option value="">Anadir asistente...</option>
                                @foreach ($availableAttendees->whereNotIn('id', $attendeeIds) as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-[#E7E2D8] pt-4">
                        <button
                            type="button"
                            wire:click="closeForm"
                            class="rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                            wire:loading.attr="disabled"
                            wire:target="saveEvent"
                        >
                            <span wire:loading.remove wire:target="saveEvent">Guardar</span>
                            <span wire:loading wire:target="saveEvent">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
