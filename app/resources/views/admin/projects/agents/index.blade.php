<x-layouts.admin :title="'Agentes IA: '.$project->name">
    <div class="space-y-6">
        <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al proyecto
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

        <div>
            <h1 class="text-2xl font-semibold text-[#111827]">Agentes IA del proyecto</h1>
            <p class="mt-1 text-sm text-[#6B7280]">
                Asigna templates de la biblioteca a este proyecto. Cada asignacion puede
                opcionalmente redefinir el system prompt para este proyecto concreto.
            </p>
        </div>

        @if ($availableTemplates->isNotEmpty())
            <x-ui.card>
                <h2 class="text-sm font-semibold">Asignar nuevo template</h2>
                <form method="POST" action="{{ route('admin.projects.agents.store', $project) }}" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label for="agent_template_id" class="mb-1 block text-sm font-medium text-[#111827]">Template</label>
                        <select
                            name="agent_template_id"
                            id="agent_template_id"
                            required
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >
                            <option value="">Selecciona un template...</option>
                            @foreach ($availableTemplates as $available)
                                <option value="{{ $available->id }}" @selected((int) old('agent_template_id') === $available->id)>
                                    {{ $available->name }}{{ $available->category ? ' — '.$available->category : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('agent_template_id')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="system_prompt_override" class="mb-1 block text-sm font-medium text-[#111827]">Override del system prompt (opcional)</label>
                        <textarea
                            name="system_prompt_override"
                            id="system_prompt_override"
                            rows="6"
                            placeholder="Si lo dejas vacio, el agente usara el system prompt del template."
                            class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 font-mono text-sm placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                        >{{ old('system_prompt_override') }}</textarea>
                        @error('system_prompt_override')
                            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end">
                        <x-ui.button type="submit" variant="primary">Asignar</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card>
            <h2 class="text-sm font-semibold">Asignaciones</h2>

            <div class="mt-4 overflow-x-auto rounded-xl border border-[#E7E2D8]">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[#E7E2D8] bg-[#FAFAF7]">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Template</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Override</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Prompt efectivo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-[#6B7280]">Asignado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-[#6B7280]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E2D8]">
                        @forelse ($assignments as $assignment)
                            @php
                                $isEditing = (int) request('edit') === $assignment->id;
                                $effective = $assignment->effectiveSystemPrompt();
                            @endphp
                            <tr class="hover:bg-[#F4F1EA] transition-colors align-top">
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('admin.agent-templates.show', $assignment->template) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                                        {{ $assignment->template?->name ?? '—' }}
                                    </a>
                                    <p class="text-xs text-[#6B7280]">por {{ $assignment->template?->creator?->name ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#6B7280]">
                                    @if ($isEditing)
                                        <form method="POST" action="{{ route('admin.projects.agents.update', [$project, $assignment]) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <textarea
                                                name="system_prompt_override"
                                                rows="5"
                                                class="w-full rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 font-mono text-xs placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                                            >{{ old('system_prompt_override', $assignment->system_prompt_override) }}</textarea>
                                            @error('system_prompt_override')
                                                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
                                            @enderror
                                            <div class="flex items-center gap-2">
                                                <x-ui.button type="submit" variant="primary">Guardar</x-ui.button>
                                                <a href="{{ route('admin.projects.agents.index', $project) }}" class="rounded-lg px-3 py-2 text-sm font-medium text-[#6B7280] hover:text-[#111827]">Cancelar</a>
                                            </div>
                                        </form>
                                    @elseif (! empty($assignment->system_prompt_override))
                                        <span class="line-clamp-2">{{ \Illuminate\Support\Str::limit($assignment->system_prompt_override, 100) }}</span>
                                    @else
                                        <span class="text-xs text-[#9CA3AF]">Usa el del template</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-[#6B7280]">
                                    <span class="line-clamp-2">{{ \Illuminate\Support\Str::limit((string) $effective, 100) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#6B7280]">
                                    {{ $assignment->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    @if (! $isEditing)
                                        <a href="{{ route('admin.projects.agents.index', [$project, 'edit' => $assignment->id]) }}" class="font-medium text-[#2563EB] hover:text-[#1D4ED8]">Editar override</a>
                                        <form method="POST" action="{{ route('admin.projects.agents.destroy', [$project, $assignment]) }}" class="mt-2 inline-block" onsubmit="return confirm('Desasignar este template del proyecto?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-[#DC2626] hover:text-[#B91C1C]">Desasignar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-[#6B7280]">
                                    Este proyecto aun no tiene agentes asignados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $assignments->links() }}
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
