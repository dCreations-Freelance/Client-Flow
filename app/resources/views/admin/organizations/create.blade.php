<x-layouts.admin title="Nueva organizacion">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.organizations.index') }}" class="mb-4 inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al listado
        </a>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Nueva organizacion</h2>
            <p class="mt-1 text-sm text-[#6B7280]">Crea la ficha de un cliente. Podras anadir miembros despues.</p>

            <form method="POST" action="{{ route('admin.organizations.store') }}" class="mt-6 space-y-4">
                @csrf

                <x-ui.input name="name" label="Nombre" required autofocus />

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-[#111827]">Descripcion</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.organizations.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</a>
                    <x-ui.button type="submit" variant="primary">Crear organizacion</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
