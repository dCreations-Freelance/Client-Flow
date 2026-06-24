<div>
    {{-- Filtro: rango de fechas (sin facturable ni por persona en portal) --}}
    <div class="mb-6 flex flex-wrap items-end gap-2 rounded-xl border border-[#E7E2D8] bg-white p-3">
        <div class="flex flex-col">
            <label class="mb-1 text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Desde</label>
            <input
                type="date"
                wire:model.live="fromDate"
                class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs"
            >
        </div>
        <div class="flex flex-col">
            <label class="mb-1 text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Hasta</label>
            <input
                type="date"
                wire:model.live="toDate"
                class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs"
            >
        </div>
        <button
            type="button"
            wire:click="clearFilters"
            class="ml-auto rounded-lg px-2 py-1 text-xs font-medium text-[#2563EB] hover:text-[#1D4ED8]"
        >
            Ultimo mes
        </button>
    </div>

    {{-- Resumen: una sola tarjeta principal + lista por miembro --}}
    <x-ui.card class="mb-6">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wider text-[#6B7280]">
                Horas dedicadas en este periodo
            </p>
            <p class="text-3xl font-semibold text-[#111827]">
                {{ intdiv($summary['total_minutes'], 60) }}h
                {{ str_pad((string) ($summary['total_minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
            </p>
            <p class="mt-1 text-xs text-[#6B7280]">
                Distribuidas en {{ count($summary['by_member']) }}
                {{ count($summary['by_member']) === 1 ? 'persona' : 'personas' }}
                del equipo.
            </p>
        </div>
    </x-ui.card>

    <x-ui.card>
        <h2 class="mb-3 text-sm font-semibold text-[#111827]">Distribucion por persona</h2>
        @if (empty($summary['by_member']))
            <p class="text-sm text-[#6B7280]">
                Tu equipo aun no ha registrado tiempo en este periodo. Vuelve mas tarde.
            </p>
        @else
            <ul class="space-y-2">
                @foreach ($summary['by_member'] as $row)
                    @php
                        $percent = $summary['total_minutes'] > 0
                            ? (int) round(($row['minutes'] / $summary['total_minutes']) * 100)
                            : 0;
                    @endphp
                    <li class="rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-[#111827]">{{ $row['name'] }}</span>
                            <span class="text-xs text-[#6B7280]">
                                {{ intdiv($row['minutes'], 60) }}h
                                {{ str_pad((string) ($row['minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
                                <span class="ml-1 text-[#9CA3AF]">({{ $percent }}%)</span>
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#E7E2D8]">
                            <div
                                class="h-full rounded-full bg-[#2563EB]"
                                style="width: {{ $percent }}%"
                            ></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <p class="mt-4 text-center text-[10px] text-[#9CA3AF]">
        Solo se muestran totales agregados. Los detalles de cada entrada son privados del equipo.
    </p>
</div>
