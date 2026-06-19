<x-layouts.admin :title="'Configuracion de IA'">
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-[#111827]">Configuracion de IA</h1>
            <p class="mt-1 text-sm text-[#6B7280]">
                Configura el provider y la API key que ClientFlow usara para responder a tus clientes
                desde el chat IA de cada proyecto.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg px-4 py-3 text-sm bg-[#F0FDF4] border border-[#16A34A] text-[#166534]">
                {{ session('status') }}
            </div>
        @endif

        @if (session('ai_test_error'))
            <div class="rounded-lg px-4 py-3 text-sm bg-[#FEF2F2] border border-[#DC2626] text-[#991B1B]">
                {{ session('ai_test_error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-[#E7E2D8] p-6">
            <livewire:admin.ai-config.settings-form :project-id="request()->integer('project_id') ?: null" />
        </div>
    </div>
</x-layouts.admin>
