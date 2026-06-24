<div>
    {{-- Filtros --}}
    <div class="mb-6 grid grid-cols-1 gap-2 rounded-xl border border-[#E7E2D8] bg-white p-3 sm:flex sm:flex-wrap sm:items-end">
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
        <div class="flex flex-col">
            <label class="mb-1 text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Facturacion</label>
            <select wire:model.live="billableFilter" class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs">
                <option value="">Todas</option>
                <option value="billable">Solo facturables</option>
                <option value="not_billable">Solo no facturables</option>
            </select>
        </div>
        <div class="flex flex-col">
            <label class="mb-1 text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Persona</label>
            <select wire:model.live="userFilter" class="rounded-lg border border-[#E7E2D8] bg-white px-2 py-1 text-xs">
                <option value="">Todas</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
        </div>

        <button
            type="button"
            wire:click="clearFilters"
            class="self-end rounded-lg px-2 py-1 text-xs font-medium text-[#2563EB] hover:text-[#1D4ED8]"
        >
            Limpiar filtros
        </button>

        <a
            href="{{ route('admin.projects.time.export', $project) }}?{{ http_build_query(request()->only(['from', 'to', 'billable', 'user_id'])) }}"
            class="ml-auto inline-flex items-center gap-1 self-end rounded-lg border border-[#E7E2D8] bg-white px-3 py-1 text-xs font-medium text-[#111827] hover:bg-[#F4F1EA]"
        >
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Exportar CSV
        </a>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-[#E7E2D8] bg-white p-4">
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Total tiempo</p>
            <p class="mt-1 text-2xl font-semibold text-[#111827]">
                {{ intdiv($summary['total_minutes'], 60) }}h {{ str_pad((string) ($summary['total_minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
            </p>
            <p class="mt-0.5 text-[10px] text-[#6B7280]">{{ $summary['total_entries'] }} entradas</p>
        </div>
        <div class="rounded-xl border border-[#E7E2D8] bg-white p-4">
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Facturable</p>
            <p class="mt-1 text-2xl font-semibold text-[#16A34A]">
                {{ intdiv($summary['billable_minutes'], 60) }}h {{ str_pad((string) ($summary['billable_minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
            </p>
            <p class="mt-0.5 text-[10px] text-[#6B7280]">
                {{ $summary['total_minutes'] > 0 ? (int) round(($summary['billable_minutes'] / $summary['total_minutes']) * 100) : 0 }}% del total
            </p>
        </div>
        <div class="rounded-xl border border-[#E7E2D8] bg-white p-4">
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">No facturable</p>
            <p class="mt-1 text-2xl font-semibold text-[#6B7280]">
                {{ intdiv($summary['not_billable_minutes'], 60) }}h {{ str_pad((string) ($summary['not_billable_minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
            </p>
        </div>
        <div class="rounded-xl border border-[#E7E2D8] bg-white p-4">
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#6B7280]">Personas</p>
            <p class="mt-1 text-2xl font-semibold text-[#111827]">{{ count($summary['by_member']) }}</p>
            <p class="mt-0.5 text-[10px] text-[#6B7280]">con tiempo registrado</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Breakdown por miembro --}}
        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-[#111827]">Tiempo por persona</h2>
            @if (empty($summary['by_member']))
                <p class="text-xs text-[#6B7280]">Sin entradas en el periodo seleccionado.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($summary['by_member'] as $row)
                        <li class="flex items-center justify-between rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] px-3 py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#2563EB] text-[10px] font-medium text-white">
                                    {{ strtoupper(mb_substr($row['name'], 0, 2)) }}
                                </div>
                                <span class="font-medium text-[#111827]">{{ $row['name'] }}</span>
                            </div>
                            <span class="text-xs text-[#6B7280]">
                                {{ intdiv($row['minutes'], 60) }}h {{ str_pad((string) ($row['minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        {{-- Breakdown por tarea --}}
        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-[#111827]">Tiempo por tarea</h2>
            @if (empty($summary['by_task']))
                <p class="text-xs text-[#6B7280]">Sin entradas en el periodo seleccionado.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($summary['by_task'] as $row)
                        <li class="flex items-center justify-between rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] px-3 py-2 text-sm">
                            <a
                                href="{{ route('admin.projects.tasks.show', [$project, $row['task_id']]) }}"
                                class="truncate font-medium text-[#111827] hover:text-[#2563EB]"
                            >
                                {{ $row['title'] }}
                            </a>
                            <span class="shrink-0 text-xs text-[#6B7280]">
                                {{ intdiv($row['minutes'], 60) }}h {{ str_pad((string) ($row['minutes'] % 60), 2, '0', STR_PAD_LEFT) }}m
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    {{-- Tabla de entradas individuales --}}
    <x-ui.card class="mt-6">
        <h2 class="mb-3 text-sm font-semibold text-[#111827]">Entradas</h2>
        @if ($entries->isEmpty())
            <p class="text-xs text-[#6B7280]">Sin entradas en el periodo seleccionado.</p>
        @else
            <div class="space-y-2">
                @foreach ($entries as $entry)
                    <x-partials.time-entry-row :entry="$entry" :canEdit="true" />
                @endforeach
            </div>
            @if ($entries->count() >= 100)
                <p class="mt-3 text-[10px] text-[#9CA3AF]">
                    Mostrando las primeras 100 entradas. Usa la exportacion CSV para ver todas.
                </p>
            @endif
        @endif
    </x-ui.card>
</div>
