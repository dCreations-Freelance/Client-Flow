<x-layouts.admin :title="$template->name">
    <div class="space-y-6">
        <a href="{{ route('admin.agent-templates.index') }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al listado
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

        <x-ui.card>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ $template->name }}</h1>
                        @if ($template->category)
                            <span class="inline-flex items-center rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                                {{ $template->category }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-[#6B7280]">
                        Modelo: {{ $template->model ?? '—' }} · Creado por {{ $template->creator?->name ?? '—' }} el {{ $template->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.agent-templates.edit', $template) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                        Editar
                    </a>
                    <a href="{{ route('admin.agent-templates.export', $template) }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                        Exportar JSON
                    </a>
                    <form method="POST" action="{{ route('admin.agent-templates.destroy', $template) }}" onsubmit="return confirm('Eliminar este template tambien desasignara los proyectos que lo usen. Continuar?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-[#DC2626] bg-white px-4 py-2 text-sm font-medium text-[#DC2626] hover:bg-[#FEF2F2]">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </x-ui.card>

        @if ($template->description)
            <x-ui.card>
                <h2 class="text-sm font-semibold">Descripcion</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-[#111827]">{{ $template->description }}</p>
            </x-ui.card>
        @endif

        <x-ui.card>
            <h2 class="text-sm font-semibold">System prompt</h2>
            <pre class="mt-3 overflow-x-auto whitespace-pre-wrap rounded-lg bg-[#F4F1EA] p-4 font-mono text-xs text-[#111827]">{{ $template->system_prompt }}</pre>
        </x-ui.card>

        @if (! empty($template->tools))
            <x-ui.card>
                <h2 class="text-sm font-semibold">Tools</h2>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-[#F4F1EA] p-4 font-mono text-xs text-[#111827]">{{ json_encode($template->tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </x-ui.card>
        @endif

        <x-ui.card>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Asignado a {{ $assignmentsCount }} {{ $assignmentsCount === 1 ? 'proyecto' : 'proyectos' }}</h2>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-[#E7E2D8]">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Proyecto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Override</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E2D8]">
                        @forelse ($projects as $project)
                            <tr class="hover:bg-[#F4F1EA] transition-colors">
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('admin.projects.show', $project) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                                        {{ $project->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#6B7280]">
                                    @if (! empty($project->pivot->system_prompt_override))
                                        <span class="line-clamp-1">{{ \Illuminate\Support\Str::limit($project->pivot->system_prompt_override, 80) }}</span>
                                    @else
                                        <span class="text-xs text-[#9CA3AF]">Usa el del template</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-6 text-center text-sm text-[#6B7280]">
                                    Este template aun no esta asignado a ningun proyecto.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $projects->links() }}
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
