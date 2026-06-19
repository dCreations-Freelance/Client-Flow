<?php

namespace App\Enums;

/**
 * Proveedor de IA soportado por ClientFlow.
 *
 * - `Openai`: API Chat Completions de OpenAI.
 * - `Anthropic`: API Messages de Anthropic.
 * - `Opencode`: provider OpenAI-compatible con `base_url`
 *   configurable. Pensado para apuntar a un proxy local
 *   (LM Studio, Ollama, vLLM, etc.) o a un endpoint
 *   corporativo que reusa el formato de OpenAI. Si la URL
 *   no se configura, cae al endpoint publico por defecto.
 *
 * Cada provider expone su modelo por defecto y la URL base
 * de su API. El provider concreto se resuelve en runtime
 * dentro de `AiService` a partir de la `AiConfig` activa.
 */
enum AiProvider: string
{
    case Openai = 'openai';

    case Anthropic = 'anthropic';

    case Opencode = 'opencode';

    /**
     * Etiqueta legible en castellano para mostrar en la UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Openai => 'OpenAI',
            self::Anthropic => 'Anthropic',
            self::Opencode => 'Opencode (OpenAI compatible)',
        };
    }

    /**
     * Color semantico para badges y separadores en la UI.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Openai => 'green',
            self::Anthropic => 'orange',
            self::Opencode => 'blue',
        };
    }

    /**
     * Modelo por defecto que `AiService` usara si la
     * `AiConfig` activa no especifica uno.
     *
     * @return string
     */
    public function defaultModel(): string
    {
        return match ($this) {
            self::Openai => 'gpt-4o-mini',
            self::Anthropic => 'claude-3-5-haiku-latest',
            // Opencode Zen namespacia los modelos con el
            // tier como prefijo. El admin puede cambiarlo
            // desde la UI de settings por AiConfig.
            self::Opencode => 'opencode-go/kimi-k2.6',
        };
    }

    /**
     * URL base de la API del provider. Se combina con los
     * paths especificos de cada provider en sus clases
     * correspondientes.
     *
     * Para `Opencode` el provider lee `config/ai.php` en
     * runtime (no aqui) para mantener el enum puro y
     * testeable sin bootstrap de Laravel. El valor de
     * fallback coincide con la URL por defecto del config.
     *
     * @return string
     */
    public function baseUrl(): string
    {
        return match ($this) {
            self::Openai => 'https://api.openai.com',
            self::Anthropic => 'https://api.anthropic.com',
            self::Opencode => 'https://opencode.ai/zen/go',
        };
    }

    /**
     * Indica si el provider reusa el formato JSON de
     * OpenAI Chat Completions. Lo es `Openai` por
     * definicion y `Opencode` por diseno; `Anthropic`
     * tiene un esquema propio.
     *
     * @return bool
     */
    public function isOpenaiCompatible(): bool
    {
        return $this === self::Openai || $this === self::Opencode;
    }
}
