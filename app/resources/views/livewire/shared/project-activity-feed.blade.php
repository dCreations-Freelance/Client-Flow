{{--
    Vista del feed de actividad compartido (admin y portal).

    Estructura:
    - Cabecera con titulo y contador total.
    - Fila de chips para filtrar por categoria (Todas, Tareas,
      Documentos, Eventos, Mensajes, Proyecto, Miembros). Los
      chips muestran el conteo entre parentesis.
    - Lista de items via `<x-partials.activity-item>`. Si el feed
      esta vacio, mostramos un empty state con copy contextual.
    - Boton "Cargar entradas anteriores" al final si hay mas
      de las cargadas en pantalla.

    Reglas:
    - Sin polling: el feed es historico. La parte reactiva
      (mensajes nuevos) la cubre el chat con su propio polling.
    - Sin Alpine: el filtro de categoria y el "Cargar mas" son
      acciones Livewire (`wire:click`).
    - El `wire:key` por item garantiza que Livewire reuse el
      DOM cuando se anaden entradas al final.
--}}
<div class="space-y-4">
    {{-- Cabecera --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[#111827]">Actividad del proyecto</h2>
            <p class="text-sm text-[#6B7280]">
                {{ $total === 0
                    ? 'Aun no hay eventos registrados.'
                    : ($total === 1
                        ? '1 evento registrado.'
                        : $total.' eventos registrados.') }}
            </p>
        </div>
    </div>

    {{-- Chips de filtro por categoria --}}
    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filtro de actividad por categoria">
        @foreach ($categoryLabels as $key => $label)
            @php
                $count = $counts[$key] ?? 0;
                // Ocultamos los chips que no tienen entradas
                // (excepto "Todas") para que la fila no crezca
                // con categorias vacias.
                if ($key !== 'all' && $count === 0) {
                    continue;
                }

                $isActive = $category === $key;
            @endphp
            <button
                type="button"
                wire:click="setCategory('{{ $key }}')"
                wire:key="activity-chip-{{ $key }}"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors {{ $isActive ? 'border-[#2563EB] bg-[#EFF6FF] text-[#2563EB]' : 'border-[#E7E2D8] bg-white text-[#6B7280] hover:border-[#2563EB] hover:text-[#111827]' }}"
            >
                <span>{{ $label }}</span>
                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full {{ $isActive ? 'bg-[#2563EB] text-white' : 'bg-[#F4F1EA] text-[#6B7280]' }} px-1.5 text-[10px] font-semibold">
                    {{ $count }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Lista de entradas --}}
    <div class="space-y-2">
        @if ($entries->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-[#E7E2D8] bg-white px-6 py-10 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F4F1EA]">
                    <svg class="h-6 w-6 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-medium text-[#111827]">Sin actividad todavia</h3>
                <p class="mt-1 max-w-sm text-sm text-[#6B7280]">
                    @if ($portalMode)
                        Cuando haya movimiento en este proyecto lo veras aqui.
                    @else
                        Crea una tarea, sube un documento o envia un mensaje para empezar a registrar actividad.
                    @endif
                </p>
            </div>
        @else
            @foreach ($entries as $entry)
                <x-partials.activity-item :entry="$entry" :portalMode="$portalMode" />
            @endforeach
        @endif
    </div>

    {{-- Boton "Cargar mas" --}}
    @if ($hasMore)
        <div class="flex justify-center">
            <button
                type="button"
                wire:click="loadMore"
                class="rounded-full border border-[#E7E2D8] bg-white px-4 py-1.5 text-xs font-medium text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
            >
                Cargar entradas anteriores
            </button>
        </div>
    @endif
</div>
