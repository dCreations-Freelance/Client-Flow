<?php

namespace App\Services\Ai\Contracts;

/**
 * Mensaje individual que se envia a un provider de IA.
 *
 * Es una estructura neutra (DTO implicito) que `AiService`
 * produce a partir de `AiChatMessage` y que las clases de
 * provider traducen al formato concreto (OpenAI Chat
 * Completions, Anthropic Messages, etc.).
 *
 * Se modela como `readonly class` para que las
 * transformaciones del provider no muten los datos
 * originales.
 */
final class AiMessage
{
    /**
     * @param  string  $role  'user' | 'assistant' | 'system'
     * @param  string  $content
     */
    public function __construct(
        public readonly string $role,
        public readonly string $content,
    ) {}

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
