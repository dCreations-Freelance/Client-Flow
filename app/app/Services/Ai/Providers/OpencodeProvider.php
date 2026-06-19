<?php

namespace App\Services\Ai\Providers;

use App\Enums\AiProvider as AiProviderEnum;
use App\Services\Ai\Contracts\AiProvider;

/**
 * Provider OpenAI-compatible que reusa el mismo formato JSON
 * que `OpenAiProvider` pero permite apuntar a una URL
 * arbitraria: LM Studio, Ollama (con su adaptador OpenAI),
 * vLLM, un proxy corporativo, etc.
 *
 * Se configura via `config/ai.php` (clave `ai.opencode.base_url`).
 * Si esa URL no esta definida, se usa la API publica de
 * OpenAI como fallback, lo que permite tratar a `Opencode`
 * como un "alias parametrizado" de OpenAI.
 *
 * La logica HTTP es identica a `OpenAiProvider`; se delega
 * en una instancia de este ultimo para evitar duplicar
 * codigo de peticion/respuesta.
 */
class OpencodeProvider extends OpenAiProvider implements AiProvider
{
    /**
     * @return string
     */
    public function name(): string
    {
        return AiProviderEnum::Opencode->value;
    }

    /**
     * Nombre legible del provider. Sobreescribe el de
     * `OpenAiProvider` para que los mensajes de error
     * generados por la logica heredada digan "Opencode"
     * en vez de "OpenAI".
     *
     * @return string
     */
    public function displayName(): string
    {
        return 'Opencode';
    }

    /**
     * Modelo por defecto. Prioriza `config('ai.models.opencode')`
     * (que se puede sobreescribir via `OPENCODE_DEFAULT_MODEL`)
     * y cae al modelo declarado en el enum si no esta
     * configurado.
     *
     * @return string
     */
    public function defaultModel(): string
    {
        $model = config('ai.models.opencode');

        return is_string($model) && $model !== ''
            ? $model
            : AiProviderEnum::Opencode->defaultModel();
    }

    /**
     * Resuelve la URL base del provider. Lee de
     * `config/ai.php` para permitir apuntar a un proxy
     * local (LM Studio, Ollama, vLLM, etc.). Si no esta
     * configurada, cae al endpoint publico de OpenAI.
     *
     * @return string
     */
    protected function resolveBaseUrl(\App\Models\AiConfig $config): string
    {
        $configured = config('ai.opencode.base_url');

        return is_string($configured) && $configured !== ''
            ? $configured
            : parent::resolveBaseUrl($config);
    }
}
