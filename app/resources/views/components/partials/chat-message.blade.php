{{--
    Renderiza un mensaje individual del chat.

    Decide la alineacion y el estilo segun el tipo de mensaje:
    - Texto propio: derecha, fondo azul.
    - Texto ajeno: izquierda, fondo blanco.
    - Sistema: centrado, fondo gris claro, sin avatar.
    - Solo adjuntos: el contenido textual se omite y se renderiza
      unicamente la lista de adjuntos (mensaje de tipo File).

    Variables esperadas:
        $message: App\\Models\\ProjectMessage
        $currentUserId: int|null  (id del usuario que ve el chat)
        $isReadByOther: bool     (true si alguien mas ha leido el mensaje)
        $canDeleteAttachments: bool (true si el usuario puede borrar adjuntos)

    Uso:
        <x-partials.chat-message :message="$message" :currentUserId="$user->id" :isReadByOther="true" :canDeleteAttachments="true" />
--}}
@props([
    'message',
    'currentUserId' => null,
    'isReadByOther' => false,
    'canDeleteAttachments' => false,
])

@if ($message->isSystem())
    <div class="my-2 flex justify-center">
        <div class="max-w-[80%] rounded-full bg-[#F4F1EA] px-3 py-1 text-center text-xs text-[#6B7280]">
            {{ $message->content }}
            <span class="ml-1 text-[#9CA3AF]">{{ $message->created_at?->format('d/m H:i') }}</span>
        </div>
    </div>
@else
    @php
        $isMine = $message->isFromUserId($currentUserId);
        $hasAttachments = $message->attachments?->isNotEmpty() ?? false;
        $showText = ! $message->isEmpty();
    @endphp

    <div class="my-1 flex items-end gap-2 {{ $isMine ? 'flex-row-reverse' : '' }}">
        <x-partials.chat-user-avatar :user="$message->user" />

        <div class="flex max-w-[75%] flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
            <div class="{{ $showText ? 'rounded-2xl px-4 py-2.5 text-sm' : 'space-y-1' }} {{ $isMine ? 'rounded-br-sm bg-[#2563EB] text-white' : 'rounded-bl-sm border border-[#E7E2D8] bg-white text-[#111827]' }}">
                @if ($showText)
                    @if (! $isMine)
                        <p class="mb-0.5 text-xs font-medium text-[#6B7280]">{{ $message->user?->name }}</p>
                    @endif
                    <p class="whitespace-pre-line break-words">{{ $message->content }}</p>
                @elseif (! $isMine && $hasAttachments)
                    <p class="mb-0.5 text-xs font-medium text-[#6B7280]">{{ $message->user?->name }}</p>
                @endif

                {{-- Adjuntos: se renderizan dentro de la burbuja. Si
                    el mensaje no tiene texto, son lo unico que se ve. --}}
                @if ($hasAttachments)
                    <div class="{{ $showText ? 'mt-2 space-y-1' : 'space-y-1' }}">
                        @foreach ($message->attachments as $attachment)
                            <x-partials.chat-attachment-row
                                :attachment="$attachment"
                                :isOwnMessage="$isMine"
                                :canDelete="$canDeleteAttachments"
                            />
                        @endforeach
                    </div>
                @endif
            </div>
            <span class="mt-1 flex items-center gap-1 text-[10px] text-[#9CA3AF]">
                {{ $message->created_at?->format('d/m H:i') }}
                @if ($isMine)
                    @if ($isReadByOther)
                        {{-- Doble check: alguien mas ha leido el mensaje. --}}
                        <span title="Visto" class="text-[#2563EB]">✓✓</span>
                    @else
                        {{-- Check simple: mensaje enviado pero aun no visto por nadie mas. --}}
                        <span title="Enviado">✓</span>
                    @endif
                @endif
            </span>
        </div>
    </div>
@endif
