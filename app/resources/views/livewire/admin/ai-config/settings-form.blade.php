<div>
    {{-- Panel resumen: muestra todas las configuraciones existentes --}}
    @php
        $globalConfig = $existingConfigs->firstWhere('project_id', null);
        $projectConfigs = $existingConfigs->filter(fn ($c) => $c->project_id !== null);
        $configuredProjectIds = $existingConfigs->pluck('project_id')->filter()->toArray();
    @endphp

    <div class="mb-6 rounded-xl border border-[#E7E2D8] bg-[#FAFAF8] p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-[#111827]">Configuraciones existentes</h2>
            <span class="text-xs text-[#6B7280]">
                {{ $existingConfigs->count() }} {{ Str::plural('configuracion', $existingConfigs->count()) }}
            </span>
        </div>

        <div class="space-y-2">
            {{-- Config global --}}
            <div class="flex items-center justify-between rounded-lg px-3 py-2 {{ $projectId === null ? 'bg-white ring-1 ring-[#2563EB]' : 'bg-white' }}">
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="selectProject(null)"
                        @if ($this->isDirty()) wire:confirm="Tienes cambios sin guardar. Cambiar de configuracion descartara los cambios. Continuar?" @endif
                        class="text-sm font-medium text-[#111827] hover:text-[#2563EB] transition-colors text-left">
                        Configuracion global (fallback)
                    </button>
                    @if ($globalConfig)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $globalConfig->is_active ? 'bg-[#DCFCE7] text-[#166534]' : 'bg-[#F3F4F6] text-[#6B7280]' }}">
                            {{ $globalConfig->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-[#EFF6FF] px-2 py-0.5 text-xs font-medium text-[#1E40AF]">
                            {{ $globalConfig->provider->label() }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-[#FEF3C7] px-2 py-0.5 text-xs font-medium text-[#92400E]">
                            Sin configurar
                        </span>
                    @endif
                </div>
                @if ($projectId === null)
                    <span class="text-xs text-[#2563EB] font-medium">Editando</span>
                @endif
            </div>

            {{-- Configs por proyecto --}}
            @forelse ($projects as $projectOption)
                @php
                    $cfg = $existingConfigs->firstWhere('project_id', $projectOption->id);
                @endphp
                <div class="flex items-center justify-between rounded-lg px-3 py-2 {{ $projectId === $projectOption->id ? 'bg-white ring-1 ring-[#2563EB]' : 'bg-white' }}">
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="selectProject({{ $projectOption->id }})"
                            @if ($this->isDirty()) wire:confirm="Tienes cambios sin guardar. Cambiar de configuracion descartara los cambios. Continuar?" @endif
                            class="text-sm font-medium text-[#111827] hover:text-[#2563EB] transition-colors text-left">
                            {{ $projectOption->name }}
                        </button>
                        @if ($cfg)
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $cfg->is_active ? 'bg-[#DCFCE7] text-[#166534]' : 'bg-[#F3F4F6] text-[#6B7280]' }}">
                                {{ $cfg->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-[#EFF6FF] px-2 py-0.5 text-xs font-medium text-[#1E40AF]">
                                {{ $cfg->provider->label() }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-[#FEF3C7] px-2 py-0.5 text-xs font-medium text-[#92400E]">
                                Sin configurar
                            </span>
                        @endif
                    </div>
                    @if ($projectId === $projectOption->id)
                        <span class="text-xs text-[#2563EB] font-medium">Editando</span>
                    @endif
                </div>
            @empty
                <p class="text-xs text-[#6B7280] px-3">No hay proyectos registrados.</p>
            @endforelse
        </div>
    </div>

    {{-- Banner de confirmacion post-guardado --}}
    @if ($saved)
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)"
            x-show="show" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="mb-4 rounded-lg px-4 py-3 text-sm bg-[#F0FDF4] border border-[#16A34A] text-[#166534] flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            Configuracion guardada correctamente.
        </div>
    @endif

    {{-- Formulario principal --}}
    <form wire:submit="save" class="space-y-6">
        {{-- Alcance --}}
        <div>
            <label class="block text-sm font-medium text-[#111827] mb-1">Alcance de la configuracion</label>
            <select wire:model.live="projectId"
                @if ($this->isDirty()) wire:confirm="Tienes cambios sin guardar. Cambiar de configuracion descartara los cambios. Continuar?" @endif
                class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                <option value="">Configuracion global (fallback)</option>
                @foreach ($projects as $projectOption)
                    @php
                        $hasConfig = in_array($projectOption->id, $configuredProjectIds);
                    @endphp
                    <option value="{{ $projectOption->id }}">
                        {{ $projectOption->name }}{{ $hasConfig ? ' (configurada)' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-[#6B7280]">
                La configuracion global se usa cuando un proyecto no tiene la suya propia.
            </p>
        </div>

        {{-- Provider + Modelo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#111827] mb-1">Provider</label>
                <select wire:model.live="provider"
                    class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                    @foreach ($providers as $providerOption)
                        <option value="{{ $providerOption->value }}">{{ $providerOption->label() }}</option>
                    @endforeach
                </select>
                @php
                    $providerHints = [
                        'openai' => ['model' => 'gpt-4o-mini', 'key' => 'sk-...'],
                        'anthropic' => ['model' => 'claude-3-5-haiku-latest', 'key' => 'sk-ant-...'],
                        'opencode' => ['model' => 'opencode-go/kimi-k2.6', 'key' => 'oc-... (Opencode Zen)'],
                    ];
                    $hints = $providerHints[$provider] ?? $providerHints['openai'];
                @endphp
                <p class="mt-1 text-xs text-[#6B7280]">
                    @if ($provider === 'opencode')
                        Opencode Zen: API OpenAI-compatible en
                        <code class="text-[#111827]">https://opencode.ai/docs/es/zen#endpoints</code>.
                    @elseif ($provider === 'anthropic')
                        Anthropic Messages API. Cabecera
                        <code class="text-[#111827]">x-api-key</code> y
                        <code class="text-[#111827]">anthropic-version</code>.
                    @else
                        OpenAI Chat Completions. Auth via
                        <code class="text-[#111827]">Authorization: Bearer</code>.
                    @endif
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#111827] mb-1">Modelo (opcional)</label>
                <input type="text" wire:model="model" placeholder="Ej. {{ $hints['model'] }}"
                    class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                <p class="mt-1 text-xs text-[#6B7280]">
                    Vacio = usar el modelo por defecto del provider.
                </p>
            </div>
        </div>

        {{-- API key con badge --}}
        <div>
            <div class="flex items-center gap-2 mb-1">
                <label class="text-sm font-medium text-[#111827]">API key</label>
                @if ($hasApiKey)
                    <span class="inline-flex items-center gap-1 rounded-full bg-[#DCFCE7] px-2 py-0.5 text-xs font-medium text-[#166534]">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        Clave configurada
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-[#FEF3C7] px-2 py-0.5 text-xs font-medium text-[#92400E]">
                        Sin clave
                    </span>
                @endif
            </div>
            <input type="password" wire:model="apiKey" placeholder="Ej. {{ $hints['key'] }}" autocomplete="off"
                class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
            <p class="mt-1 text-xs text-[#6B7280]">
                Se almacena cifrada. Dejala vacia para conservar la clave actual.
            </p>
        </div>

        {{-- System prompt --}}
        <div>
            <label class="block text-sm font-medium text-[#111827] mb-1">System prompt personalizado (opcional)</label>
            <textarea wire:model="systemPrompt" rows="4"
                placeholder="Vacio = el sistema genera uno en castellano con el contexto del proyecto (tareas, docs publicos)."
                class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent resize-y"></textarea>
        </div>

        {{-- Limites + activa --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#111827] mb-1">Max. mensajes / hora</label>
                <input type="number" wire:model="maxMessagesPerHour" min="1" max="10000"
                    class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#111827] mb-1">Max. sesiones / dia</label>
                <input type="number" wire:model="maxSessionsPerDay" min="1" max="10000"
                    class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="isActive"
                        class="w-4 h-4 rounded border-[#E7E2D8] text-[#2563EB] focus:ring-[#2563EB]">
                    <span class="text-sm font-medium text-[#111827]">Configuracion activa</span>
                </label>
            </div>
        </div>

        {{-- Resultado test --}}
        @if ($testResult !== null)
            <div @class([
                'rounded-lg px-4 py-3 text-sm border',
                'bg-[#F0FDF4] border-[#16A34A] text-[#166534]' => $testResult['ok'],
                'bg-[#FEF2F2] border-[#DC2626] text-[#991B1B]' => !$testResult['ok'],
            ])>
                {{ $testResult['message'] }}
            </div>
        @endif

        {{-- Indicador de cambios sin guardar --}}
        @if ($this->isDirty())
            <div class="rounded-lg px-4 py-2 text-xs font-medium bg-[#FEF3C7] border border-[#F59E0B] text-[#92400E] flex items-center gap-2">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                Tienes cambios sin guardar.
            </div>
        @endif

        {{-- Botones --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-[#111827] bg-white border border-[#E7E2D8] rounded-lg hover:bg-[#F4F1EA] transition-colors">
                Probar conexion
            </button>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-[#2563EB] rounded-lg hover:bg-[#1D4ED8] transition-colors">
                Guardar configuracion
            </button>
        </div>
    </form>
</div>
