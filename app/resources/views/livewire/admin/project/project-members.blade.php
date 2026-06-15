<x-ui.card>
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold">Miembros del proyecto</h2>
        <span class="text-xs text-[#6B7280]">{{ $members->count() }} en total</span>
    </div>

    <ul class="mt-4 divide-y divide-[#E7E2D8]">
        @forelse ($members as $member)
            <li class="flex items-center justify-between py-3 text-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#2563EB] text-xs font-medium text-white">
                        {{ strtoupper(mb_substr($member->name, 0, 2)) }}
                    </div>
                    <div>
                        <p class="font-medium text-[#111827]">{{ $member->name }}</p>
                        <p class="text-xs text-[#6B7280]">{{ $member->email }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="removeMember({{ $member->id }})"
                    wire:confirm="Estas seguro de quitar a este miembro del proyecto?"
                    class="text-xs font-medium text-[#DC2626] hover:text-[#B91C1C]"
                >
                    Quitar
                </button>
            </li>
        @empty
            <li class="py-4 text-center text-sm text-[#6B7280]">Aun no hay miembros asignados al proyecto.</li>
        @endforelse
    </ul>

    @if ($availableMembers->isNotEmpty())
        <form wire:submit="addMember" class="mt-4 flex flex-col gap-2 border-t border-[#E7E2D8] pt-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="userIdToAdd" class="mb-1 block text-sm font-medium text-[#111827]">Anadir miembro</label>
                <select
                    id="userIdToAdd"
                    wire:model="userIdToAdd"
                    class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                    <option value="">Selecciona un miembro de la organizacion</option>
                    @foreach ($availableMembers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('userIdToAdd')
                    <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                @enderror
            </div>
            <x-ui.button type="submit" variant="primary">Anadir</x-ui.button>
        </form>
    @else
        <p class="mt-4 border-t border-[#E7E2D8] pt-4 text-xs text-[#6B7280]">
            No quedan miembros de la organizacion disponibles para anadir.
        </p>
    @endif
</x-ui.card>
