<x-layouts.portal :title="'Chat: '.$project->name">
    <div class="space-y-4">
        <div>
            <a href="{{ route('portal.projects.show', $project) }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
                &larr; Volver al detalle
            </a>
            <h1 class="mt-2 text-2xl font-semibold">Chat</h1>
            <p class="text-sm text-[#6B7280]">
                {{ $project->name }} · {{ $project->organization?->name }}
            </p>
        </div>

        <p class="text-sm text-[#6B7280]">
            Conversacion directa con el equipo del proyecto. Cualquier cambio relevante (tareas creadas, completadas, documentos publicados) aparecera tambien aqui como mensajes automaticos.
        </p>

        <livewire:shared.chat-window :project="$project" />
    </div>
</x-layouts.portal>
