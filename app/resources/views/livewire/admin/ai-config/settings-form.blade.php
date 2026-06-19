<div>
    <form wire:submit="save" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-[#111827] mb-1">Alcance de la configuracion</label>
            <select wire:model.live="projectId"
                class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                <option value="">Configuracion global (fallback)</option>
                @foreach ($projects as $projectOption)
                    <option value="{{ $projectOption->id }}">Solo para: {{ $projectOption->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-[#6B7280]">
                La configuracion global se usa cuando un proyecto no tiene la suya propia.
            </p>
        </div>

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

        <div>
            <label class="block text-sm font-medium text-[#111827] mb-1">API key</label>
            <input type="password" wire:model="apiKey" placeholder="Ej. {{ $hints['key'] }}" autocomplete="off"
                class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
            <p class="mt-1 text-xs text-[#6B7280]">
                Se almacena cifrada. Dejala vacia para conservar la clave actual.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#111827] mb-1">System prompt personalizado (opcional)</label>
            <textarea wire:model="systemPrompt" rows="4"
                placeholder="Vacio = el sistema genera uno en castellano con el contexto del proyecto (tareas, docs publicos)."
                class="w-full px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent resize-y"></textarea>
        </div>

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

        @if ($testResult !== null)
            <div @class([
                'rounded-lg px-4 py-3 text-sm border',
                'bg-[#F0FDF4] border-[#16A34A] text-[#166534]' => $testResult['ok'],
                'bg-[#FEF2F2] border-[#DC2626] text-[#991B1B]' => !$testResult['ok'],
            ])>
                {{ $testResult['message'] }}
            </div>
        @endif

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