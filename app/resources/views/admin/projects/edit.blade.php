<x-layouts.admin :title="'Editar '.$project->name">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.projects.show', $project) }}" class="mb-4 inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al detalle
        </a>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Editar proyecto</h2>

            <form method="POST" action="{{ route('admin.projects.update', $project) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <x-ui.input name="name" label="Nombre" :value="$project->name" required autofocus />

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
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected(old('organization_id', $project->organization_id) == $organization->id)>
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
                    >{{ old('description', $project->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
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
                            <option value="planning" @selected(old('status', $project->status->value) === 'planning')>Planificacion</option>
                            <option value="in_progress" @selected(old('status', $project->status->value) === 'in_progress')>En progreso</option>
                            <option value="on_hold" @selected(old('status', $project->status->value) === 'on_hold')>En pausa</option>
                            <option value="waiting_client" @selected(old('status', $project->status->value) === 'waiting_client')>Esperando cliente</option>
                            <option value="completed" @selected(old('status', $project->status->value) === 'completed')>Completado</option>
                            <option value="archived" @selected(old('status', $project->status->value) === 'archived')>Archivado</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="starts_at" class="mb-1 block text-sm font-medium text-[#111827]">Fecha de inicio</label>
                        <input
                            type="date"
                            name="starts_at"
                            id="starts_at"
                            value="{{ old('starts_at', $project->starts_at?->format('Y-m-d')) }}"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="estimated_ends_at" class="mb-1 block text-sm font-medium text-[#111827]">Fecha estimada de fin</label>
                        <input
                            type="date"
                            name="estimated_ends_at"
                            id="estimated_ends_at"
                            value="{{ old('estimated_ends_at', $project->estimated_ends_at?->format('Y-m-d')) }}"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-[#111827]">
                    <input
                        type="checkbox"
                        name="is_visible_to_client"
                        value="1"
                        @checked(old('is_visible_to_client', $project->is_visible_to_client))
                        class="rounded border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]"
                    >
                    Visible para los clientes del proyecto
                </label>

                <div class="flex items-center justify-between gap-3 pt-4">
                    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Estas seguro de eliminar este proyecto? Esta accion no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-[#DC2626] hover:text-[#B91C1C]">Eliminar proyecto</button>
                    </form>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.projects.show', $project) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</a>
                        <x-ui.button type="submit" variant="primary">Guardar cambios</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
