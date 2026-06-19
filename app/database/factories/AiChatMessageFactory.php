<?php

namespace Database\Factories;

use App\Enums\AiChatRole;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiChatMessage>
 */
class AiChatMessageFactory extends Factory
{
    /**
     * Estado por defecto: mensaje de usuario en una sesion
     * recien creada, con timestamp actual. La mayoria de los
     * tests de chat necesitan mensajes en orden, asi que el
     * `created_at` se fija al momento de creacion.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_chat_session_id' => AiChatSession::factory(),
            'role' => AiChatRole::User,
            'content' => fake()->sentence(),
            'tokens_used' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Marca el mensaje como del asistente.
     *
     * @return static
     */
    public function assistant(): static
    {
        return $this->state(fn (): array => [
            'role' => AiChatRole::Assistant,
        ]);
    }

    /**
     * Marca el mensaje como system prompt (no se muestra en
     * la UI; se persiste para depuracion y para reenviar
     * el contexto al provider).
     *
     * @return static
     */
    public function system(): static
    {
        return $this->state(fn (): array => [
            'role' => AiChatRole::System,
        ]);
    }
}
