<div>
    <div class="flex items-center gap-3">
        <input
            type="range"
            min="0"
            max="100"
            step="1"
            wire:model.live="progress"
            class="flex-1 accent-[#2563EB]"
        >
        <span class="min-w-[3rem] text-right text-sm font-medium text-[#111827]">{{ $progress }}%</span>
        <button
            type="button"
            wire:click="save"
            class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#1D4ED8] disabled:opacity-50"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>Guardar</span>
            <span wire:loading>Guardando...</span>
        </button>
    </div>

    <x-partials.progress-bar :value="$progress" :showPercent="false" class="mt-2" />

    @error('progress')
        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
    @enderror
</div>
