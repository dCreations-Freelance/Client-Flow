<div class="flex flex-col h-full">
    @if ($error)
        <div
            x-data="{ show: true }"
            x-show="show"
            class="rounded-lg px-4 py-3 text-sm mb-4 bg-[#FEF2F2] border border-[#DC2626] text-[#991B1B] flex items-start justify-between gap-3"
        >
            <span>{{ $error }}</span>
            <button type="button" x-on:click="show = false" class="text-[#991B1B] hover:text-[#7F1D1D]">
                <span class="text-xs font-medium">Cerrar</span>
            </button>
        </div>
    @endif

    <div
        class="flex-1 overflow-y-auto space-y-4 pr-2"
        x-data
        x-ref="messagesContainer"
        x-init="$nextTick(() => { $refs.messagesContainer.scrollTop = $refs.messagesContainer.scrollHeight; })"
    >
        @forelse ($messages as $message)
            @if ($message->isUser())
                <div class="flex justify-end">
                    <div class="max-w-[80%] bg-[#2563EB] text-white rounded-2xl rounded-br-sm px-4 py-2.5">
                        <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                        <span class="text-[10px] text-blue-200 mt-1 block">{{ $message->created_at?->format('H:i') }}</span>
                    </div>
                </div>
            @else
                <div class="flex justify-start">
                    <div class="max-w-[80%] bg-white border border-[#E7E2D8] rounded-2xl rounded-bl-sm px-4 py-2.5">
                        <p class="text-sm text-[#111827] whitespace-pre-wrap">{{ $message->content }}</p>
                        <span class="text-[10px] text-[#9CA3AF] mt-1 block">{{ $message->created_at?->format('H:i') }}</span>
                    </div>
                </div>
            @endif
        @empty
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-12 h-12 rounded-full bg-[#F5F3FF] flex items-center justify-center mb-4 text-[#8B5CF6]">
                    <span class="text-xl">✨</span>
                </div>
                <h3 class="text-sm font-medium text-[#111827] mb-1">Empieza una conversacion</h3>
                <p class="text-sm text-[#6B7280] max-w-sm">
                    Pregunta al asistente IA sobre el estado del proyecto, las tareas en curso o los documentos publicos.
                </p>
            </div>
        @endforelse
    </div>

    <form
        wire:submit="sendMessage"
        class="mt-4 pt-4 border-t border-[#E7E2D8]"
        x-data
        x-on:ai-chat-message-sent.window="$nextTick(() => { $refs.messagesContainer.scrollTop = $refs.messagesContainer.scrollHeight; })"
    >
        <div class="flex items-end gap-2">
            <textarea
                wire:model="newMessage"
                rows="2"
                placeholder="Escribe tu mensaje..."
                x-on:keydown.enter.prevent="$wire.call('sendMessage')"
                class="flex-1 px-3 py-2 text-sm border border-[#E7E2D8] rounded-lg bg-white placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent resize-y"
            ></textarea>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-[#2563EB] rounded-lg hover:bg-[#1D4ED8] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="sendMessage">Enviar</span>
                <span wire:loading wire:target="sendMessage">Enviando...</span>
            </button>
        </div>
        @error('newMessage')
            <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
        @enderror
    </form>
</div>
