<x-layouts.admin :title="'Crear proyecto desde plantilla'">
    <div class="space-y-6">
        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <nav class="flex flex-wrap items-center gap-2 text-sm text-[#6B7280]">
            <a href="{{ route('admin.project-templates.index') }}" class="hover:text-[#111827]">Plantillas</a>
            <span>/</span>
            <a href="{{ route('admin.project-templates.show', $template) }}" class="hover:text-[#111827]">{{ $template->name }}</a>
            <span>/</span>
            <span class="text-[#111827]">Crear proyecto</span>
        </nav>

        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-[#111827]">Plantilla seleccionada</h2>
            <p class="mb-2 text-base font-semibold text-[#111827]">{{ $template->name }}</p>
            @if ($template->description)
                <p class="mb-3 text-sm text-[#6B7280]">{{ $template->description }}</p>
            @endif
            <p class="text-xs text-[#6B7280]">
                Se copiaran <strong>{{ $template->columns->count() }}</strong> columnas,
                <strong>{{ $template->tasks->count() }}</strong> tareas y
                <strong>{{ $template->documents->count() }}</strong> documentos.
            </p>
        </x-ui.card>

        <form method="POST" action="{{ route('admin.projects.store-from-template', $template) }}" class="space-y-6">
            @csrf

            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-[#111827]">Datos del proyecto</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-[#111827]">
                            Nombre del proyecto <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $suggestedName) }}"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="organization_id" class="mb-1 block text-sm font-medium text-[#111827]">
                            Organizacion <span class="text-[#DC2626]">*</span>
                        </label>
                        <select
                            id="organization_id"
                            name="organization_id"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm"
                        >
                            <option value="">Selecciona una organizacion</option>
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>
                                    {{ $org->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('organization_id')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-[#111827]">
                            Estado inicial
                        </label>
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm"
                        >
                            @foreach (\App\Enums\ProjectStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'planning') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-[#111827]">
                            Descripcion
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >{{ old('description') }}</textarea>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-[#111827]">
                        <input
                            type="checkbox"
                            name="is_visible_to_client"
                            value="1"
                            checked
                            class="h-4 w-4 rounded border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]"
                        >
                        <span>Visible para el cliente del portal</span>
                    </label>
                </div>
            </x-ui.card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
                <a
                    href="{{ route('admin.project-templates.show', $template) }}"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                >
                    Crear proyecto desde plantilla
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
