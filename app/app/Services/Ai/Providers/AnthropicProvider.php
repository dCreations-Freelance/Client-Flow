<?php

namespace App\Services\Ai\Providers;

use App\Enums\AiProvider as AiProviderEnum;
use App\Models\AiConfig;
use App\Services\Ai\Contracts\AiMessage;
use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Contracts\AiProviderException;
use App\Services\Ai\Contracts\AiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Provider para la API Messages de Anthropic.
 *
 * Diferencias clave respecto al formato OpenAI:
 * - El system prompt va en un campo top-level `system`, no
 *   como mensaje de la lista.
 * - La lista de mensajes solo admite roles `user` y
 *   `assistant`. El mensaje `system` se extrae al preparar
 *   la peticion.
 * - La respuesta relevante es `content[0].text`.
 * - La autenticacion se hace con la cabecera `x-api-key`
 *   y la version se anade con la cabecera `anthropic-version`.
 */
class AnthropicProvider implements AiProvider
{
    /**
     * Version de la API que esperamos. Anthropic requiere
     * esta cabecera en cada peticion; en el MVP la fijamos
     * aqui y se cambiara cuando Anthropic la retire.
     */
    private const API_VERSION = '2023-06-01';

    /**
     * @param  HttpFactory  $http
     */
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return string
     */
    public function name(): string
    {
        return AiProviderEnum::Anthropic->value;
    }

    /**
     * @return string
     */
    public function displayName(): string
    {
        return 'Anthropic';
    }

    /**
     * @return string
     */
    public function defaultModel(): string
    {
        return AiProviderEnum::Anthropic->defaultModel();
    }

    /**
     * @param  array<int, AiMessage>  $messages
     *
     * @throws AiProviderException
     */
    public function send(AiConfig $config, array $messages, ?string $modelOverride = null): AiResponse
    {
        $model = $modelOverride ?: $config->effectiveModel();
        $url = rtrim($config->provider->baseUrl(), '/').'/v1/messages';
        $displayName = $this->displayName();

        [$system, $conversation] = $this->splitSystemPrompt($messages);

        $body = [
            'model' => $model,
            'max_tokens' => 1024,
            'messages' => $conversation,
        ];

        if ($system !== null) {
            $body['system'] = $system;
        }

        try {
            $response = $this->http
                ->withHeaders([
                    'x-api-key' => $config->api_key,
                    'anthropic-version' => self::API_VERSION,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('ai.http_timeout', 30))
                ->connectTimeout((int) config('ai.http_connect_timeout', 10))
                ->post($url, $body);
        } catch (ConnectionException $e) {
            throw new AiProviderException(
                "No se pudo conectar con el provider {$displayName}: ".$e->getMessage(),
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new AiProviderException(
                "Error de transporte con {$displayName}: ".$e->getMessage(),
                previous: $e,
            );
        }

        return $this->parseResponse($response, $model);
    }

    /**
     * Separa el system prompt (si existe) del resto de
     * mensajes. Anthropic exige que `system` vaya en un
     * campo dedicado; los mensajes `system` se concatenan
     * separados por doble salto de linea.
     *
     * @param  array<int, AiMessage>  $messages
     * @return array{0: string|null, 1: array<int, array{role: string, content: string}>}
     */
    private function splitSystemPrompt(array $messages): array
    {
        $systemParts = [];
        $conversation = [];

        foreach ($messages as $message) {
            if ($message->role === 'system') {
                $systemParts[] = $message->content;
                continue;
            }

            $conversation[] = $message->toArray();
        }

        $system = $systemParts === []
            ? null
            : implode("\n\n", $systemParts);

        return [$system, $conversation];
    }

    /**
     * Traduce la respuesta JSON del provider a `AiResponse`.
     *
     * @throws AiProviderException
     */
    private function parseResponse(Response $response, string $requestedModel): AiResponse
    {
        $displayName = $this->displayName();

        if (! $response->successful()) {
            throw new AiProviderException(
                sprintf('%s devolvio HTTP %d: %s', $displayName, $response->status(), $response->body()),
            );
        }

        $payload = $response->json();

        $content = null;
        if (isset($payload['content']) && is_array($payload['content'])) {
            foreach ($payload['content'] as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                    $content = $block['text'];
                    break;
                }
            }
        }

        if (! is_string($content) || $content === '') {
            throw new AiProviderException("La respuesta de {$displayName} no incluye contenido utilizable.");
        }

        $model = is_string($payload['model'] ?? null) ? $payload['model'] : $requestedModel;

        $inputTokens = isset($payload['usage']['input_tokens']) ? (int) $payload['usage']['input_tokens'] : 0;
        $outputTokens = isset($payload['usage']['output_tokens']) ? (int) $payload['usage']['output_tokens'] : 0;
        $tokens = ($inputTokens + $outputTokens) > 0 ? $inputTokens + $outputTokens : null;

        return new AiResponse(
            content: $content,
            model: $model,
            tokensUsed: $tokens,
        );
    }
}
