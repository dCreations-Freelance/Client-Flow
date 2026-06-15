<x-layouts.admin :title="'Editar '.$organization->name">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.organizations.show', $organization) }}" class="mb-4 inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al detalle
        </a>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Editar organizacion</h2>

            <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <x-ui.input name="name" label="Nombre" :value="$organization->name" required autofocus />

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-[#111827]">Descripcion</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >{{ old('description', $organization->description) }}</textarea>
                </div>

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-[#111827]">Estado</label>
                    <select
                        name="status"
                        id="status"
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >
                        <option value="active" @selected(old('status', $organization->status->value) === 'active')>Activa</option>
                        <option value="inactive" @selected(old('status', $organization->status->value) === 'inactive')>Inactiva</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.organizations.show', $organization) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</a>
                    <x-ui.button type="submit" variant="primary">Guardar cambios</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
