<x-layouts.admin :title="'Nueva plantilla'">
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
            <span class="text-[#111827]">Nueva</span>
        </nav>

        <form method="POST" action="{{ route('admin.project-templates.store') }}" class="space-y-6">
            @csrf

            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-[#111827]">Metadatos de la plantilla</h2>
                <p class="mb-4 text-xs text-[#6B7280]">
                    Empieza con el nombre y la categoria. Podras anadir columnas, tareas y
                    documentos desde la siguiente pantalla.
                </p>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-[#111827]">
                            Nombre <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                            placeholder="Lanzamiento web"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="mb-1 block text-sm font-medium text-[#111827]">
                            Categoria
                        </label>
                        <input
                            id="category"
                            name="category"
                            type="text"
                            value="{{ old('category') }}"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                            placeholder="web, mobile, design..."
                            list="category-suggestions"
                        >
                        <datalist id="category-suggestions">
                            @foreach (\App\Models\ProjectTemplate::query()->whereNotNull('category')->distinct()->pluck('category') as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                        <p class="mt-1 text-[10px] text-[#9CA3AF]">
                            Texto libre. Te servira para filtrar el listado.
                        </p>
                        @error('category')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-[#111827]">
                            Descripcion
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                            placeholder="En que consiste esta plantilla? Para que tipo de proyectos se usa?"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-ui.card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
                <a
                    href="{{ route('admin.project-templates.index') }}"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                >
                    Crear plantilla
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
