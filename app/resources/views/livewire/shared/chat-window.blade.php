{{--
    Vista del chat compartido (admin y portal).

    Estructura:
    - Cabecera con el nombre del proyecto y el contador de "no
      leidos" (visible solo si > 0).
    - Lista de mensajes scrollable con autoscroll al fondo.
    - Boton "Cargar mensajes anteriores" al inicio si hay mas
      de los cargados en pantalla.
    - Input con textarea, zona de adjuntos pendientes y boton Enviar.
    - Polling cada 5s para refrescar mensajes.

    Reglas:
    - El contenedor lleva `wire:poll.5s="refresh"` para que
      Livewire re-renderice periodicamente.
    - JS minimo inline para autoscroll al fondo y para
      Shift+Enter (salto de linea) vs Enter (enviar).
    - Los mensajes se renderizan via partial `chat-message` que
      decide su alineacion, estilo y los adjuntos.
--}}
<div
    x-data="{
        shouldScroll: true,
        scrollToBottom() {
            const el = $refs.messagesBody;
            if (el) { el.scrollTop = el.scrollHeight; }
        },
    }"
    x-init="scrollToBottom()"
    @chat-message-sent.window="shouldScroll = true; $nextTick(() => scrollToBottom())"
    wire:poll.5s="refresh"
    class="flex h-[calc(100vh-12rem)] flex-col overflow-hidden rounded-xl border border-[#E7E2D8] bg-white"
>
    {{-- Cabecera --}}
    <div class="flex items-center justify-between border-b border-[#E7E2D8] bg-[#FAFAF7] px-4 py-3">
        <div>
            <h2 class="text-sm font-semibold text-[#111827]">Chat del proyecto</h2>
            <p class="text-xs text-[#6B7280]">{{ $project->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if ($unreadCount > 0)
                <span class="inline-flex items-center rounded-full bg-[#DC2626] px-2.5 py-0.5 text-xs font-medium text-white">
                    {{ $unreadCount }} sin leer
                </span>
            @endif
            <span class="inline-flex items-center gap-1 text-xs text-[#9CA3AF]" title="Actualizacion automatica cada 5 segundos">
                <span class="h-2 w-2 rounded-full bg-[#16A34A]"></span>
                En vivo
            </span>
        </div>
    </div>

    {{-- Cuerpo con mensajes --}}
    <div
        x-ref="messagesBody"
        class="flex-1 space-y-1 overflow-y-auto bg-[#FAFAF7] px-4 py-3"
    >
        @if ($totalMessages > $loadedCount)
            <div class="mb-2 flex justify-center">
                <button
                    type="button"
                    wire:click="loadMore"
                    class="rounded-full border border-[#E7E2D8] bg-white px-3 py-1 text-xs font-medium text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                >
                    Cargar mensajes anteriores
                </button>
            </div>
        @endif

        @if ($messages->isEmpty())
            <div class="flex h-full flex-col items-center justify-center text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F4F1EA]">
                    <svg class="h-6 w-6 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                </div>
                <h3 class="text-sm font-medium text-[#111827]">Aun no hay mensajes</h3>
                <p class="mt-1 text-sm text-[#6B7280]">
                    Escribe el primer mensaje o arrastra un archivo para empezar la conversacion.
                </p>
            </div>
        @else
            @foreach ($messages as $message)
                <x-partials.chat-message
                    :message="$message"
                    :currentUserId="$user->id"
                    :isReadByOther="(bool) ($readMessageIds[$message->id] ?? false)"
                    :canDeleteAttachments="$canDeleteAttachments"
                />
            @endforeach
        @endif
    </div>

    {{-- Input de envio --}}
    <form
        wire:submit.prevent="sendMessage"
        class="flex flex-col gap-2 border-t border-[#E7E2D8] bg-white p-3 sm:flex-row sm:items-end"
    >
        <div class="flex-1">
            <label for="chat-input" class="sr-only">Escribe un mensaje</label>

            {{-- Lista de adjuntos pendientes de enviar. Cada uno con
                boton X para quitarlo. --}}
            @if (count($attachments) > 0)
                <div class="mb-2 flex flex-wrap gap-2">
                    @foreach ($attachments as $index => $file)
                        <div class="flex items-center gap-2 rounded-lg border border-[#E7E2D8] bg-[#FAFAF7] px-2.5 py-1.5 text-xs">
                            <svg class="h-4 w-4 text-[#6B7280]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                            <span class="max-w-[180px] truncate text-[#111827]">{{ $file->getClientOriginalName() }}</span>
                            <span class="text-[#9CA3AF]">{{ \App\Models\TaskAttachment::formatBytes($file->getSize()) }}</span>
                            <button
                                type="button"
                                wire:click="removePendingAttachment({{ $index }})"
                                class="ml-1 text-[#6B7280] hover:text-[#DC2626]"
                                title="Quitar"
                            >&times;</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-end gap-2">
                <textarea
                    id="chat-input"
                    wire:model="newMessage"
                    rows="2"
                    maxlength="2000"
                    placeholder="Escribe un mensaje... (Enter para enviar, Shift+Enter para nueva linea)"
                    class="block w-full resize-none rounded-lg border border-[#E7E2D8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent"
                    x-on:keydown.enter.prevent="if (!event.shiftKey) { $wire.call('sendMessage'); }"
                ></textarea>

                {{-- Boton para adjuntar archivos. El input file
                    multiple permite arrastrar o seleccionar varios.
                    Al cambiar, Livewire sube los archivos a
                    livewire-tmp/ y los refleja en $wire.attachments. --}}
                <label
                    class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-[#E7E2D8] bg-white text-[#6B7280] hover:bg-[#F4F1EA] hover:text-[#111827]"
                    title="Adjuntar archivo"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                    </svg>
                    <input
                        type="file"
                        wire:model="attachments"
                        multiple
                        class="hidden"
                    >
                </label>
            </div>

            @error('attachments')
                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
            @enderror
            @error('attachments.*')
                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
            @enderror
            @error('newMessage')
                <p class="mt-1 text-xs text-[#DC2626]">{{ $message }}</p>
            @enderror
        </div>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-medium text-white hover:bg-[#1D4ED8] disabled:opacity-50"
            wire:loading.attr="disabled"
            wire:target="sendMessage"
        >
            <span wire:loading.remove wire:target="sendMessage">Enviar</span>
            <span wire:loading wire:target="sendMessage">Enviando...</span>
        </button>
    </form>
</div>
