<?php

namespace App\Services\Ai;

use App\Enums\AiChatRole;
use App\Enums\AiProvider as AiProviderEnum;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiConfig;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\Contracts\AiMessage;
use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Contracts\AiProviderException;
use App\Services\Ai\Contracts\AiResponse;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\OpencodeProvider;
use RuntimeException;

/**
 * Orquestador del modulo de IA.
 *
 * Responsabilidades:
 * 1. Resolver la `AiConfig` activa para un proyecto,
 *    cayendo a la global si el proyecto no tiene la suya.
 * 2. Aplicar rate limit antes de invocar al provider.
 * 3. Construir el system prompt con `ProjectContextBuilder`.
 * 4. Traducir el historial de `AiChatMessage` a `AiMessage`
 *    (DTO neutro) y despachar al provider correspondiente.
 * 5. Persistir el mensaje del usuario y la respuesta del
 *    asistente, ambos en la sesion indicada.
 * 6. Proveer un `testConnection()` para que el admin valide
 *    la configuracion desde `/admin/configuracion/ia`.
 *
 * Los providers se resuelven en `resolveProvider()` segun
 * `AiConfig::provider`. Anadir un provider nuevo = crear
 * la clase, implementar `AiProvider` y mapearla en
 * `resolveProvider()`.
 */
class AiService
{
    /**
     * @param  array<string, AiProvider>  $providers
     */
    public function __construct(
        private readonly AiRateLimiter $rateLimiter,
        private readonly ProjectContextBuilder $contextBuilder,
        private readonly array $providers,
    ) {
    }

    /**
     * Envia un mensaje del usuario a la IA dentro de la
     * sesion indicada y devuelve el mensaje de respuesta
     * del asistente (ya persistido).
     *
     * @return AiChatMessage
     *
     * @throws AiProviderException
     * @throws RuntimeException Si el rate limit bloquea la
     *                          operacion o no hay config.
     */
    public function sendMessage(
        Project $project,
        User $user,
        AiChatSession $session,
        string $userMessage,
    ): AiChatMessage {
        $config = $this->resolveConfig($project);

        if (!$this->rateLimiter->canSendMessage($config, $user->id, $project->id)) {
            $seconds = $this->rateLimiter->secondsUntilMessageSlot($config, $user->id, $project->id);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw new RuntimeException(
                "Has alcanzado el limite de mensajes por hora. Intentalo de nuevo en {$minutes} minutos."
            );
        }

        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            throw new RuntimeException('El mensaje no puede estar vacio.');
        }

        $provider = $this->resolveProvider($config->provider);
        $messages = $this->buildMessageHistory($config, $project, $session, $userMessage);

        $response = $provider->send($config, $messages);

        $this->persistUserMessage($session, $userMessage);
        $assistantMessage = $this->persistAssistantMessage($session, $response);

        $this->rateLimiter->hitMessage($config, $user->id, $project->id);
        $session->touch();

        return $assistantMessage;
    }

    /**
     * Crea una nueva sesion de chat para el par (user, project).
     * Aplica el rate limit diario de sesiones.
     *
     * @return AiChatSession
     *
     * @throws RuntimeException Si se supera el limite diario.
     */
    public function createSession(Project $project, User $user, ?string $title = null): AiChatSession
    {
        $config = $this->resolveConfig($project);

        if (!$this->rateLimiter->canCreateSession($config, $user->id, $project->id)) {
            throw new RuntimeException(
                'Has alcanzado el limite diario de sesiones nuevas. Vuelve mañana o pide al admin que aumente el limite.'
            );
        }

        $session = AiChatSession::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'title' => $title !== null && trim($title) !== '' ? trim($title) : null,
        ]);

        $this->rateLimiter->hitSession($config, $user->id, $project->id);

        return $session;
    }

    /**
     * Ejecuta una peticion de prueba contra el provider para
     * validar la API key. El admin lo lanza desde el boton
     * "Probar conexion" en `/admin/configuracion/ia`.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(AiConfig $config): array
    {
        $provider = $this->resolveProvider($config->provider);

        try {
            $response = $provider->send(
                $config,
                [
                    new AiMessage('system', 'Eres un asistente que responde de forma muy breve.'),
                    new AiMessage('user', 'Responde unicamente con la palabra OK.'),
                ],
                $config->effectiveModel(),
            );
        } catch (AiProviderException $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        if (trim($response->content) === '') {
            return [
                'ok' => false,
                'message' => 'El provider respondio con contenido vacio.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'Conexion correcta. Modelo: ' . $response->model,
        ];
    }

    /**
     * Resuelve la `AiConfig` aplicable al proyecto: primero
     * la del proyecto, luego la global. Devuelve la primera
     * fila activa. Si no hay ninguna, lanza excepcion para
     * que la UI muestre un mensaje claro.
     *
     * @throws RuntimeException
     */
    public function resolveConfig(Project $project): AiConfig
    {
        $config = AiConfig::active()
            ->forProject($project->id)
            ->first();

        if ($config !== null) {
            return $config;
        }

        $config = AiConfig::active()->global()->first();

        if ($config === null) {
            throw new RuntimeException(
                'No hay ninguna configuracion de IA activa. El admin debe crear una en /admin/configuracion/ia.'
            );
        }

        return $config;
    }

    /**
     * Resuelve el provider concreto en funcion del enum
     * almacenado en la `AiConfig`. Si en el futuro se anade
     * un provider nuevo, basta con mapearlo aqui.
     *
     * @throws RuntimeException
     */
    private function resolveProvider(AiProviderEnum $provider): AiProvider
    {
        $instance = $this->providers[$provider->value] ?? null;

        if (!$instance instanceof AiProvider) {
            throw new RuntimeException(
                sprintf('No hay provider registrado para "%s".', $provider->value)
            );
        }

        return $instance;
    }

    /**
     * Construye la secuencia completa de mensajes que se
     * envia al provider:
     * 1. System prompt (custom o generado).
     * 2. Mensajes previos de la sesion en orden
     *    cronologico, sin los `system` (esos solo se
     *    usan internamente para regenerar el system
     *    prompt en cada turno).
     * 3. Mensaje nuevo del usuario.
     *
     * @return array<int, AiMessage>
     */
    private function buildMessageHistory(
        AiConfig $config,
        Project $project,
        AiChatSession $session,
        string $userMessage,
    ): array {
        $systemPrompt = $config->effectiveSystemPrompt() ?? $this->contextBuilder->build($project);

        $messages = [new AiMessage('system', $systemPrompt)];

        $previous = $session->messages()->orderBy('id')->get();
        foreach ($previous as $previousMessage) {
            $role = $previousMessage->role instanceof AiChatRole ? $previousMessage->role->value : 'user';
            // Nunca reenviamos system como mensaje del
            // historial: ya lo hemos inyectado arriba.
            if ($role === 'system') {
                continue;
            }
            $messages[] = new AiMessage($role, $previousMessage->content);
        }

        $messages[] = new AiMessage('user', $userMessage);

        return $messages;
    }

    /**
     * Persiste el mensaje del usuario en la sesion. Se
     * hace dentro de la misma transaccion logica que la
     * respuesta, aunque Laravel los persiste por separado
     * (no son atomicos respecto al provider). Si la llamada
     * al provider falla, el mensaje del usuario queda
     * persistido igualmente para que el usuario lo vea y
     * pueda reintentar.
     *
     * @return AiChatMessage
     */
    private function persistUserMessage(AiChatSession $session, string $content): AiChatMessage
    {
        return AiChatMessage::create([
            'ai_chat_session_id' => $session->id,
            'role' => AiChatRole::User,
            'content' => $content,
            'tokens_used' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Persiste la respuesta del asistente. Si el provider
     * reporto tokens, se almacenan para una fase futura en
     * la que se mostrara un dashboard de consumo.
     *
     * @return AiChatMessage
     */
    private function persistAssistantMessage(AiChatSession $session, AiResponse $response): AiChatMessage
    {
        return AiChatMessage::create([
            'ai_chat_session_id' => $session->id,
            'role' => AiChatRole::Assistant,
            'content' => $response->content,
            'tokens_used' => $response->tokensUsed,
            'created_at' => now(),
        ]);
    }

    /**
     * Registra los providers por defecto del contenedor.
     * Se usa como binding en `AppServiceProvider` para que
     * `AiService` reciba el mapa `provider => instancia`.
     *
     * @return array<string, AiProvider>
     */
    public static function defaultProviders(
        OpenAiProvider $openai,
        AnthropicProvider $anthropic,
        OpencodeProvider $opencode,
    ): array {
        return [
            AiProviderEnum::Openai->value => $openai,
            AiProviderEnum::Anthropic->value => $anthropic,
            AiProviderEnum::Opencode->value => $opencode,
        ];
    }
}
