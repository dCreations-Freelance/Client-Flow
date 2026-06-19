<div>
    @if ($error)
        <div class="rounded-lg px-3 py-2 text-xs mb-3 bg-[#FEF2F2] border border-[#DC2626] text-[#991B1B]">
            {{ $error }}
        </div>
    @endif

    <button
        type="button"
        wire:click="createSession"
        class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white bg-[#2563EB] rounded-lg hover:bg-[#1D4ED8] transition-colors"
    >
        <span>+ Nueva conversacion</span>
    </button>

    <ul class="mt-4 space-y-1">
        @forelse ($sessions as $sessionItem)
            <li
                @class([
                    'group flex items-center gap-2 rounded-lg px-3 py-2 text-sm',
                    'bg-[#EFF6FF] text-[#2563EB] font-medium' => $currentSessionId === $sessionItem->id,
                    'text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827] transition-colors' => $currentSessionId !== $sessionItem->id,
                ])
            >
                <a
                    href="{{ route('portal.projects.ai.show', ['project' => $project->id, 'session' => $sessionItem->id]) }}"
                    class="flex-1 truncate"
                    title="{{ $sessionItem->displayTitle() }}"
                >
                    {{ $sessionItem->displayTitle() }}
                </a>
                <button
                    type="button"
                    wire:click="deleteSession({{ $sessionItem->id }})"
                    wire:confirm="¿Eliminar esta conversacion? No se puede deshacer."
                    class="opacity-0 group-hover:opacity-100 text-[#9CA3AF] hover:text-[#DC2626] transition-all"
                    title="Eliminar"
                >
                    <span class="text-xs">×</span>
                </button>
            </li>
        @empty
            <li class="px-3 py-2 text-xs text-[#9CA3AF] text-center">
                Aun no tienes conversaciones.
            </li>
        @endforelse
    </ul>
</div>
