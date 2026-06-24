{{--
    Fila individual de una entrada de tiempo.

    Se usa tanto en la lista del tracker de tarea
    como en la tabla del dashboard de proyecto. La
    fila es declarativa: recibe la entrada y muestra
    fecha, descripcion, minutos, autor, badge de
    tipo y badges de facturable. Las acciones (editar,
    eliminar, toggle facturable) se exponen solo
    cuando `$canEdit` es true (modo admin).

    Variables:
        $entry: App\\Models\\TimeEntry
        $canEdit: bool (true si el usuario actual puede editar)
        $editRoute: nombre de la ruta para wire:click de edicion (default: time-tracker inline)
        $deleteRoute: nombre de la ruta para wire:click de borrado (default: time-tracker inline)
--}}
@props([
    'entry',
    'canEdit' => true,
    'editMethod' => 'openEditForm',
    'deleteMethod' => 'deleteEntry',
    'toggleMethod' => 'toggleBilled',
    'showDescription' => true,
])

@php
    $isTimer = $entry->isTimer();
    $badgeColor = $isTimer ? 'blue' : 'gray';
    $badgeLabel = $isTimer ? 'Cronometro' : 'Manual';
@endphp

<div class="flex flex-col gap-2 rounded-lg border border-[#E7E2D8] bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2 text-xs text-[#6B7280]">
            <span class="font-medium text-[#111827]">
                {{ $entry->user?->name ?? 'Usuario eliminado' }}
            </span>
            <span>·</span>
            <span>{{ $entry->created_at?->format('d/m/Y H:i') }}</span>
            <span @class([
                'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium',
                'bg-[#EFF6FF] text-[#2563EB]' => $badgeColor === 'blue',
                'bg-[#F4F1EA] text-[#6B7280]' => $badgeColor === 'gray',
            ])>
                {{ $badgeLabel }}
            </span>
            @if ($entry->isBillable())
                <span class="inline-flex items-center rounded-full bg-[#F0FDF4] px-2 py-0.5 text-[10px] font-medium text-[#16A34A]">
                    Facturable
                </span>
            @endif
        </div>

        @if ($showDescription && $entry->description)
            <p class="mt-1 line-clamp-2 text-sm text-[#111827]">
                {{ $entry->description }}
            </p>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <span class="whitespace-nowrap text-sm font-semibold text-[#111827]">
            {{ $entry->display_minutes }}
        </span>

        @if ($canEdit)
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="{{ $toggleMethod }}({{ $entry->id }})"
                    title="{{ $entry->isBillable() ? 'Quitar marca de facturable' : 'Marcar como facturable' }}"
                    @class([
                        'rounded p-1.5 transition-colors',
                        'bg-[#F0FDF4] text-[#16A34A] hover:bg-[#DCFCE7]' => $entry->isBillable(),
                        'bg-[#F4F1EA] text-[#6B7280] hover:bg-[#E7E2D8] hover:text-[#111827]' => ! $entry->isBillable(),
                    ])
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </button>

                <button
                    type="button"
                    wire:click="{{ $editMethod }}({{ $entry->id }})"
                    class="rounded p-1.5 text-[#6B7280] transition-colors hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Editar"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                </button>

                <button
                    type="button"
                    wire:click="{{ $deleteMethod }}({{ $entry->id }})"
                    wire:confirm="Eliminar esta entrada de tiempo?"
                    class="rounded p-1.5 text-[#6B7280] transition-colors hover:bg-[#FEF2F2] hover:text-[#DC2626]"
                    title="Eliminar"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
</div>
