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
 * Provider para la API Chat Completions de OpenAI.
 *
 * Endpoint: `POST {base}/v1/chat/completions`.
 * El cuerpo sigue el formato estandar de OpenAI: lista de
 * mensajes `{role, content}` y campo `model`. La respuesta
 * relevante es `choices[0].message.content`.
 *
 * No requiere SDK externo: usamos el cliente HTTP de Laravel
 * (Guzzle bajo el capot). Esto mantiene la superficie de
 * dependencias del proyecto pequena y simplifica los tests
 * con `Http::fake()`.
 */
class OpenAiProvider implements AiProvider
{
    /**
     * @param  HttpFactory  $http
     */
    public function __construct(private readonly HttpFactory $http)
    {
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return AiProviderEnum::Openai->value;
    }

    /**
     * @return string
     */
    public function displayName(): string
    {
        return 'OpenAI';
    }

    /**
     * @return string
     */
    public function defaultModel(): string
    {
        return AiProviderEnum::Openai->defaultModel();
    }

    /**
     * @param  array<int, AiMessage>  $messages
     *
     * @throws AiProviderException
     */
    public function send(AiConfig $config, array $messages, ?string $modelOverride = null): AiResponse
    {
        $model = $modelOverride ?: $config->effectiveModel();
        $url = rtrim($this->resolveBaseUrl($config), '/') . '/v1/chat/completions';
        $displayName = $this->displayName();

        try {
            $response = $this->http
                ->withToken($config->api_key)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('ai.http_timeout', 30))
                ->connectTimeout((int) config('ai.http_connect_timeout', 10))
                ->post($url, [
                    'model' => $model,
                    'messages' => array_map(
                        static fn(AiMessage $message): array => $message->toArray(),
                        array_values($messages),
                    ),
                ]);
        } catch (ConnectionException $e) {
            throw new AiProviderException(
                "No se pudo conectar con el provider {$displayName}: " . $e->getMessage(),
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new AiProviderException(
                "Error de transporte con {$displayName}: " . $e->getMessage(),
                previous: $e,
            );
        }

        return $this->parseResponse($response, $model);
    }

    /**
     * Traduce la respuesta JSON del provider a `AiResponse`.
     *
     * @throws AiProviderException
     */
    private function parseResponse(Response $response, string $requestedModel): AiResponse
    {
        $displayName = $this->displayName();

        if (!$response->successful()) {
            throw new AiProviderException(
                sprintf('%s devolvio HTTP %d: %s', $displayName, $response->status(), $response->body()),
            );
        }

        $payload = $response->json();

        $content = $payload['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            throw new AiProviderException("La respuesta de {$displayName} no incluye contenido utilizable.");
        }

        $model = is_string($payload['model'] ?? null) ? $payload['model'] : $requestedModel;
        $tokens = isset($payload['usage']['total_tokens']) ? (int) $payload['usage']['total_tokens'] : null;

        return new AiResponse(
            content: $content,
            model: $model,
            tokensUsed: $tokens,
        );
    }

    /**
     * Resuelve la URL base del provider. Por defecto usa la
     * declarada en el enum. `OpencodeProvider` sobreescribe
     * este metodo para leer la URL de `config/ai.php`.
     *
     * @return string
     */
    protected function resolveBaseUrl(AiConfig $config): string
    {
        return $config->provider->baseUrl();
    }
}
