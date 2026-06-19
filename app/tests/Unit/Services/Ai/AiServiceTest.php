<?php

namespace Tests\Unit\Services\Ai;

use App\Enums\AiProvider;
use App\Models\AiConfig;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiRateLimiter;
use App\Services\Ai\AiService;
use App\Services\Ai\Contracts\AiMessage;
use App\Services\Ai\Contracts\AiProvider as AiProviderContract;
use App\Services\Ai\Contracts\AiResponse;
use App\Services\Ai\ProjectContextBuilder;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\OpencodeProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Provider falso usado en los tests del orquestador. Asi
 * no dependemos de `Http::fake()` para validar la logica
 * de negocio de `AiService`.
 */
class FakeAiProvider implements AiProviderContract
{
    public string $lastModel = '';

    /** @var array<int, AiMessage> */
    public array $lastMessages = [];

    public function name(): string
    {
        return 'openai';
    }

    public function displayName(): string
    {
        return 'OpenAI';
    }

    public function defaultModel(): string
    {
        return 'fake-model';
    }

    public function send(AiConfig $config, array $messages, ?string $modelOverride = null): AiResponse
    {
        $this->lastMessages = $messages;
        $this->lastModel = $modelOverride ?: $config->effectiveModel();

        return new AiResponse('Respuesta fake del asistente.', $this->lastModel, 100);
    }
}

class AiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function buildService(?FakeAiProvider $fake = null): array
    {
        $fake ??= new FakeAiProvider();
        $service = new AiService(
            rateLimiter: app(AiRateLimiter::class),
            contextBuilder: app(ProjectContextBuilder::class),
            providers: [
                AiProvider::Openai->value => $fake,
                AiProvider::Anthropic->value => new AnthropicProvider(app(HttpFactory::class)),
                AiProvider::Opencode->value => new OpencodeProvider(app(HttpFactory::class)),
            ],
        );

        return [$service, $fake];
    }

    public function test_send_message_persists_user_and_assistant_messages(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create();
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service] = $this->buildService();
        $assistant = $service->sendMessage($project, $user, $session, '¿Cual es el estado?');

        $this->assertTrue($assistant->isAssistant());
        $this->assertSame('Respuesta fake del asistente.', $assistant->content);
        $this->assertSame(100, $assistant->tokens_used);

        $messages = $session->messages()->orderBy('id')->get();
        $this->assertCount(2, $messages);
        $this->assertTrue($messages[0]->isUser());
        $this->assertSame('¿Cual es el estado?', $messages[0]->content);
        $this->assertTrue($messages[1]->isAssistant());
    }

    public function test_send_message_injects_system_prompt_with_project_context(): void
    {
        $project = Project::factory()->create(['name' => 'Proyecto Test']);
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create();
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service, $fake] = $this->buildService();
        $service->sendMessage($project, $user, $session, 'Hola');

        $this->assertSame('system', $fake->lastMessages[0]->role);
        $this->assertStringContainsString('Proyecto Test', $fake->lastMessages[0]->content);
        $last = end($fake->lastMessages);
        $this->assertSame('user', $last->role);
        $this->assertSame('Hola', $last->content);
    }

    public function test_send_message_uses_custom_system_prompt_when_set(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create([
            'system_prompt' => 'Eres un bot que solo dice "beep".',
        ]);
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service, $fake] = $this->buildService();
        $service->sendMessage($project, $user, $session, 'hola');

        $this->assertSame('Eres un bot que solo dice "beep".', $fake->lastMessages[0]->content);
    }

    public function test_send_message_reuses_system_prompt_across_turns(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create();
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service, $fake] = $this->buildService();
        $service->sendMessage($project, $user, $session, 'turno 1');
        $service->sendMessage($project, $user, $session, 'turno 2');

        $systemCount = count(array_filter(
            $fake->lastMessages,
            static fn (AiMessage $m): bool => $m->role === 'system',
        ));
        $this->assertSame(1, $systemCount);
    }

    public function test_send_message_throws_when_no_ai_config_exists(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service] = $this->buildService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay ninguna configuracion de IA activa');
        $service->sendMessage($project, $user, $session, 'hola');
    }

    public function test_send_message_falls_back_to_global_config(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->create(['project_id' => null]);
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service] = $this->buildService();
        $assistant = $service->sendMessage($project, $user, $session, 'hola');

        $this->assertTrue($assistant->isAssistant());
    }

    public function test_send_message_rejects_empty_messages(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create();
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service] = $this->buildService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('El mensaje no puede estar vacio');
        $service->sendMessage($project, $user, $session, '   ');
    }

    public function test_send_message_blocks_when_message_rate_limit_is_reached(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create(['max_messages_per_hour' => 1]);
        $session = \App\Models\AiChatSession::factory()->forProject($project)->forUser($user)->create();

        [$service] = $this->buildService();
        $service->sendMessage($project, $user, $session, 'primero');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Has alcanzado el limite de mensajes por hora');
        $service->sendMessage($project, $user, $session, 'segundo');
    }

    public function test_create_session_blocks_when_daily_session_limit_is_reached(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        AiConfig::factory()->forProject($project)->create(['max_sessions_per_day' => 1]);

        [$service] = $this->buildService();
        $service->createSession($project, $user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Has alcanzado el limite diario de sesiones');
        $service->createSession($project, $user);
    }

    public function test_test_connection_returns_ok_on_successful_response(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'OK']]],
            ], 200),
        ]);

        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create();
        [$service] = $this->buildService();
        $result = $service->testConnection($config);

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('gpt-4o-mini', $result['message']);
    }

    public function test_test_connection_surfaces_failure_on_http_error(): void
    {
        Http::fake([
            '*api.openai.com/v1/chat/completions' => Http::response('{"error":{"message":"Incorrect API key"}}', 401),
        ]);

        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create();

        // Para este test usamos el OpenAiProvider real
        // (no el FakeAiProvider) para verificar que el
        // orquestador maneja correctamente los errores HTTP.
        $service = new AiService(
            rateLimiter: app(AiRateLimiter::class),
            contextBuilder: app(ProjectContextBuilder::class),
            providers: [
                AiProvider::Openai->value => new OpenAiProvider(app(HttpFactory::class)),
                AiProvider::Anthropic->value => new AnthropicProvider(app(HttpFactory::class)),
                AiProvider::Opencode->value => new OpencodeProvider(app(HttpFactory::class)),
            ],
        );

        $result = $service->testConnection($config);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('401', $result['message']);
    }

    public function test_resolve_config_prefers_project_config_over_global(): void
    {
        $project = Project::factory()->create();

        $global = AiConfig::factory()->withProvider(AiProvider::Openai)->create(['project_id' => null]);
        $projectConfig = AiConfig::factory()->withProvider(AiProvider::Anthropic)->forProject($project)->create();

        [$service] = $this->buildService();
        $resolved = $service->resolveConfig($project);

        $this->assertSame($projectConfig->id, $resolved->id);
        $this->assertSame(AiProvider::Anthropic, $resolved->provider);
    }
}
