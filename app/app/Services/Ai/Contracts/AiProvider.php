<?php

namespace App\Services\Ai\Contracts;

use App\Models\AiConfig;

/**
 * Contrato que todo provider de IA debe cumplir.
 *
 * `AiService` despacha la peticion al provider concreto en
 * funcion de `AiConfig::provider`. Anadir un nuevo provider
 * se reduce a crear una clase que implemente esta interfaz
 * y registrarla en `AiService::resolveProvider()`.
 */
interface AiProvider
{
    /**
     * Identificador del provider (debe coincidir con
     * `AiProvider::value`).
     *
     * @return string
     */
    public function name(): string;

    /**
     * Nombre legible del provider. Se usa en los mensajes
     * de error y de log para que el usuario vea el nombre
     * real del provider que ha configurado (no siempre
     * coincide con el identificador: `OpencodeProvider`
     * extiende `OpenAiProvider` y debe decir "Opencode"
     * en sus errores, no "OpenAI").
     *
     * @return string
     */
    public function displayName(): string;

    /**
     * Modelo por defecto que el provider usara cuando la
     * `AiConfig` activa no especifique uno.
     *
     * @return string
     */
    public function defaultModel(): string;

    /**
     * Envia la conversacion al provider y devuelve la
     * respuesta normalizada.
     *
     * @param  AiConfig  $config  Configuracion con la API
     *                            key y parametros relevantes.
     * @param  array<int, AiMessage>  $messages  Historial de
     *                            la conversacion (incluye
     *                            system prompt + mensajes
     *                            user/assistant previos + el
     *                            ultimo mensaje del usuario).
     * @param  string|null  $modelOverride  Modelo explicito
     *                            para esta peticion. Si es
     *                            `null`, se usa
     *                            `AiConfig::effectiveModel()`.
     * @return AiResponse
     *
     * @throws \App\Services\Ai\Contracts\AiProviderException
     */
    public function send(AiConfig $config, array $messages, ?string $modelOverride = null): AiResponse;
}
