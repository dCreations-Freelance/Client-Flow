<div>
    {{-- Encabezado: titulo + total acumulado en la tarea --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-[#111827]">Registro de tiempo</h3>
            <p class="mt-0.5 text-xs text-[#6B7280]">
                Entradas manuales de tiempo para esta tarea.
            </p>
        </div>
        <div class="text-right">
            <p class="text-xs text-[#6B7280]">Total registrado</p>
            <p class="text-base font-semibold text-[#111827]" data-test="task-total-logged">
                {{ $this->task->total_logged_display }}
            </p>
        </div>
    </div>

    {{-- Accion: nueva entrada manual --}}
    <div class="mb-4 flex items-center justify-between">
        <h4 class="text-sm font-semibold text-[#111827]">Entradas</h4>
        <button
            type="button"
            wire:click="openManualEntryForm"
            class="inline-flex items-center gap-1 rounded-lg border border-[#E7E2D8] bg-white px-3 py-1.5 text-xs font-medium text-[#111827] hover:bg-[#F4F1EA]"
            data-test="open-manual-entry"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Anadir entrada manual
        </button>
    </div>

    {{-- Lista de entradas existentes --}}
    @if ($entries->isEmpty())
        <p class="rounded-lg border border-dashed border-[#E7E2D8] bg-[#FAFAF7] p-4 text-center text-xs text-[#6B7280]">
            Aun no has registrado tiempo en esta tarea.
        </p>
    @else
        <div class="space-y-2" data-test="entry-list">
            @foreach ($entries as $entry)
                <x-partials.time-entry-row :entry="$entry" :canEdit="true" />
            @endforeach
        </div>
    @endif

    {{-- Modal de entrada manual: creacion y edicion --}}
    @if ($entryForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="closeForm">
            <div class="w-full max-w-md rounded-[28px] border border-[#E7E2D8] bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold">
                    {{ $entryForm['mode'] === 'create' ? 'Nueva entrada de tiempo' : 'Editar entrada' }}
                </h3>

                <form wire:submit="saveManualEntry" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#111827]">Descripcion</label>
                        <textarea
                            wire:model="entryForm.description"
                            rows="2"
                            placeholder="En que has trabajado?"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#111827]">
                            Minutos <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            type="number"
                            wire:model="entryForm.minutes"
                            min="1"
                            max="60000"
                            step="1"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                            required
                        >
                        <p class="mt-1 text-[10px] text-[#9CA3AF]">
                            Minutos dedicados a esta tarea.
                        </p>
                        @error('entryForm.minutes')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-[#111827]">
                        <input
                            type="checkbox"
                            wire:model="entryForm.billed"
                            class="h-4 w-4 rounded border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]"
                        >
                        <span>Marcar como facturable</span>
                    </label>

                    <div class="flex flex-col-reverse gap-2 pt-4 sm:flex-row sm:items-center sm:justify-end">
                        <button
                            type="button"
                            wire:click="closeForm"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                        >
                            {{ $entryForm['mode'] === 'create' ? 'Guardar entrada' : 'Guardar cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
