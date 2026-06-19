<x-layouts.admin title="Templates de agentes IA">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-[#111827]">Templates de agentes IA</h1>
            <p class="mt-1 text-sm text-[#6B7280]">
                Biblioteca de prompts y herramientas que tu equipo puede exportar a sus IDEs
                y asignar a los proyectos para personalizar las respuestas del asistente.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Buscar por nombre o descripcion..."
                    class="w-full max-w-xs rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                <select
                    name="category"
                    class="rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                >
                    <option value="">Todas las categorias</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-[#F4F1EA] px-3 py-2 text-sm font-medium text-[#111827] hover:bg-[#EDE9DE]">Filtrar</button>
                @if ($search !== '' || $category !== '')
                    <a href="{{ route('admin.agent-templates.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Limpiar</a>
                @endif
            </form>

            @if (Route::has('admin.agent-templates.create'))
                <a href="{{ route('admin.agent-templates.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8]">
                    Nuevo template
                </a>
            @endif
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="overflow-x-auto rounded-xl border border-[#E7E2D8] bg-white">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Categoria</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Modelo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">System prompt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Proyectos</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Creado por</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-[#6B7280]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E2D8]">
                    @forelse ($templates as $template)
                        <tr class="hover:bg-[#F4F1EA] transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.agent-templates.show', $template) }}" class="font-medium text-[#111827] hover:text-[#2563EB]">
                                    {{ $template->name }}
                                </a>
                                @if ($template->description)
                                    <p class="text-xs text-[#6B7280] line-clamp-1">{{ $template->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($template->category)
                                    <span class="inline-flex items-center rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                                        {{ $template->category }}
                                    </span>
                                @else
                                    <span class="text-xs text-[#9CA3AF]">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">{{ $template->model ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">
                                <span class="line-clamp-1">{{ \Illuminate\Support\Str::limit($template->system_prompt, 80) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">
                                {{ $template->projects_count ?? $template->projects()->count() }}
                            </td>
                            <td class="px-4 py-3 text-sm text-[#6B7280]">{{ $template->creator?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.agent-templates.show', $template) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-[#6B7280]">
                                @if ($search !== '' || $category !== '')
                                    No hay templates que coincidan con el filtro.
                                @else
                                    Aun no hay templates. Crea el primero.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $templates->links() }}
        </div>
    </div>
</x-layouts.admin>
