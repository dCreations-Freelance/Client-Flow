<x-layouts.admin title="Nuevo proyecto">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.projects.index') }}" class="mb-4 inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al listado
        </a>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Nuevo proyecto</h2>
            <p class="mt-1 text-sm text-[#6B7280]">Crea un proyecto asociado a una organizacion existente.</p>

            <form method="POST" action="{{ route('admin.projects.store') }}" class="mt-6 space-y-4">
                @csrf

                <x-ui.input name="name" label="Nombre" required autofocus />

                <div>
                    <label for="organization_id" class="mb-1 block text-sm font-medium text-[#111827]">
                        Organizacion <span class="text-[#DC2626]">*</span>
                    </label>
                    <select
                        name="organization_id"
                        id="organization_id"
                        required
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >
                        <option value="">Selecciona una organizacion</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected(old('organization_id') == $organization->id)>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('organization_id')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

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

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-[#111827]">
                        Estado <span class="text-[#DC2626]">*</span>
                    </label>
                    <select
                        name="status"
                        id="status"
                        required
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >
                        <option value="planning" @selected(old('status', 'planning') === 'planning')>Planificacion</option>
                        <option value="in_progress" @selected(old('status') === 'in_progress')>En progreso</option>
                        <option value="on_hold" @selected(old('status') === 'on_hold')>En pausa</option>
                        <option value="waiting_client" @selected(old('status') === 'waiting_client')>Esperando cliente</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completado</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="starts_at" class="mb-1 block text-sm font-medium text-[#111827]">Fecha de inicio</label>
                        <input
                            type="date"
                            name="starts_at"
                            id="starts_at"
                            value="{{ old('starts_at') }}"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                        @error('starts_at')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="estimated_ends_at" class="mb-1 block text-sm font-medium text-[#111827]">Fecha estimada de fin</label>
                        <input
                            type="date"
                            name="estimated_ends_at"
                            id="estimated_ends_at"
                            value="{{ old('estimated_ends_at') }}"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                        @error('estimated_ends_at')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-[#111827]">
                    <input
                        type="checkbox"
                        name="is_visible_to_client"
                        value="1"
                        @checked(old('is_visible_to_client', true))
                        class="rounded border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]"
                    >
                    Visible para los clientes del proyecto
                </label>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.projects.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</a>
                    <x-ui.button type="submit" variant="primary">Crear proyecto</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
