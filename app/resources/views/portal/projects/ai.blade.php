<x-layouts.portal :title="'Asistente IA · '.$project->name">
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('portal.projects.show', $project) }}"
                class="text-sm text-[#6B7280] hover:text-[#111827] transition-colors"
            >
                &larr; Volver al proyecto
            </a>
        </div>

        <div class="bg-white rounded-xl border border-[#E7E2D8] overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] min-h-[600px]">
                <aside class="border-b md:border-b-0 md:border-r border-[#E7E2D8] p-4 bg-[#FAFAF7]">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-[#6B7280] mb-3">
                        Conversaciones
                    </h2>
                    <livewire:portal.ai-chat.session-list
                        :project="$project"
                        :current-session-id="$session->id"
                    />
                </aside>

                <div class="p-6 flex flex-col h-full">
                    <header class="mb-4 pb-4 border-b border-[#E7E2D8]">
                        <h1 class="text-lg font-semibold text-[#111827]">Asistente IA</h1>
                        <p class="text-xs text-[#6B7280] mt-1">
                            Proyecto: <span class="font-medium text-[#111827]">{{ $project->name }}</span>
                        </p>
                    </header>

                    <div class="flex-1">
                        <livewire:shared.ai-chat.chat-window
                            :project="$project"
                            :session="$session"
                            :key="$session->id"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.portal>
