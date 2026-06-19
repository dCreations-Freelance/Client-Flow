<x-layouts.admin :title="'Editar '.$template->name">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.agent-templates.show', $template) }}" class="mb-4 inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al detalle
        </a>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Editar template</h2>
            <p class="mt-1 text-sm text-[#6B7280]">
                Los cambios afectan a los proyectos donde esta asignado, salvo que tengan un override propio.
            </p>

            <form method="POST" action="{{ route('admin.agent-templates.update', $template) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <x-ui.input name="name" label="Nombre" :value="$template->name" required autofocus />

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-[#111827]">Categoria</label>
                    <input
                        type="text"
                        name="category"
                        id="category"
                        list="agent-template-categories"
                        value="{{ old('category', $template->category) }}"
                        maxlength="60"
                        placeholder="frontend, backend, review..."
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >
                    <datalist id="agent-template-categories">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                    @error('category')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-[#111827]">Descripcion</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="3"
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >{{ old('description', $template->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="system_prompt" class="mb-1 block text-sm font-medium text-[#111827]">
                        System prompt <span class="text-[#DC2626]">*</span>
                    </label>
                    <textarea
                        name="system_prompt"
                        id="system_prompt"
                        rows="12"
                        required
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 font-mono text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >{{ old('system_prompt', $template->system_prompt) }}</textarea>
                    @error('system_prompt')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tools" class="mb-1 block text-sm font-medium text-[#111827]">Tools (JSON)</label>
                    <textarea
                        name="tools"
                        id="tools"
                        rows="5"
                        placeholder='[{"name": "web_search", "description": "Busca en la web"}]'
                        class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 font-mono text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    >{{ old('tools', is_array($template->tools) ? json_encode($template->tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null) }}</textarea>
                    @error('tools')
                        <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input name="model" label="Modelo" :value="$template->model" placeholder="gpt-4o, claude-3-5-sonnet-latest..." />

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.agent-templates.show', $template) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</a>
                    <x-ui.button type="submit" variant="primary">Guardar cambios</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
