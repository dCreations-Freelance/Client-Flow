<?php

namespace App\Services\Ai\Contracts;

use App\Models\AiConfig;

/**
 * Respuesta normalizada de un provider de IA.
 *
 * Cualquier provider debe devolver una instancia de esta
 * clase. Asi `AiService` puede persistir y devolver la
 * respuesta sin acoplarse al formato JSON especifico de
 * cada API.
 */
final class AiResponse
{
    /**
     * @param  string  $content  Texto generado por el modelo.
     * @param  string  $model  Identificador del modelo que respondio.
     * @param  int|null  $tokensUsed  Total de tokens consumidos
     *                                (input + output) si el provider
     *                                lo reporta. `null` en caso
     *                                contrario.
     */
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly ?int $tokensUsed = null,
    ) {}

    /**
     * @return array{content: string, model: string, tokens_used: int|null}
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'tokens_used' => $this->tokensUsed,
        ];
    }
}
