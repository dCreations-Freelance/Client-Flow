<x-layouts.admin :title="'Miembros de '.$organization->name">
    <div class="space-y-6">
        <a href="{{ route('admin.organizations.show', $organization) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al detalle
        </a>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <x-ui.card>
                <h2 class="text-lg font-semibold">Miembros actuales</h2>
                <ul class="mt-4 divide-y divide-[#E7E2D8]">
                    @forelse ($organization->members as $member)
                        <li class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium text-[#111827]">{{ $member->name }}</p>
                                <p class="text-xs text-[#6B7280]">{{ $member->email }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                                    {{ ucfirst($member->pivot->role) }}
                                </span>
                                <form method="POST" action="{{ route('admin.organizations.members.destroy', [$organization, $member->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-[#DC2626] hover:text-[#B91C1C]">Eliminar</button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-[#6B7280]">Aun no hay miembros.</li>
                    @endforelse
                </ul>
            </x-ui.card>

            <x-ui.card>
                <h2 class="text-lg font-semibold">Invitar miembro</h2>
                <p class="mt-1 text-sm text-[#6B7280]">Le enviaremos un enlace por email.</p>

                <form method="POST" action="{{ route('admin.organizations.members.store', $organization) }}" class="mt-4 space-y-4">
                    @csrf

                    <x-ui.input name="email" type="email" label="Email" required />

                    <div>
                        <label for="role" class="mb-1 block text-sm font-medium text-[#111827]">Rol</label>
                        <select
                            name="role"
                            id="role"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                            <option value="member" @selected(old('role', 'member') === 'member')>Miembro</option>
                            <option value="owner" @selected(old('role') === 'owner')>Responsable</option>
                        </select>
                    </div>

                    <x-ui.button type="submit" variant="primary" class="w-full">Enviar invitacion</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.admin>
