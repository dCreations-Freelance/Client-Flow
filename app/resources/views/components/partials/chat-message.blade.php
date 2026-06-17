{{--
    Renderiza un mensaje individual del chat.

    Decide la alineacion y el estilo segun el tipo de mensaje:
    - Texto propio: derecha, fondo azul.
    - Texto ajeno: izquierda, fondo blanco.
    - Sistema: centrado, fondo gris claro, sin avatar.

    Variables esperadas:
        $message: App\\Models\\ProjectMessage
        $currentUserId: int|null  (id del usuario que ve el chat)
        $isReadByOther: bool     (true si alguien mas ha leido el mensaje)

    Uso:
        <x-partials.chat-message :message="$message" :currentUserId="$user->id" :isReadByOther="true" />
--}}
@props([
    'message',
    'currentUserId' => null,
    'isReadByOther' => false,
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
    @endphp

    <div class="my-1 flex items-end gap-2 {{ $isMine ? 'flex-row-reverse' : '' }}">
        <x-partials.chat-user-avatar :user="$message->user" />

        <div class="flex max-w-[75%] flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
            <div class="rounded-2xl px-4 py-2.5 text-sm {{ $isMine ? 'rounded-br-sm bg-[#2563EB] text-white' : 'rounded-bl-sm border border-[#E7E2D8] bg-white text-[#111827]' }}">
                @if (! $isMine)
                    <p class="mb-0.5 text-xs font-medium text-[#6B7280]">{{ $message->user?->name }}</p>
                @endif
                <p class="whitespace-pre-line break-words">{{ $message->content }}</p>
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
