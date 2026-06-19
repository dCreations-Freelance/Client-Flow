<?php

namespace Tests\Unit\Services\Ai;

use App\Enums\AiProvider;
use App\Models\AiConfig;
use App\Services\Ai\Contracts\AiMessage;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\OpencodeProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvidersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_openai_posts_to_chat_completions_with_bearer_auth(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create([
            'api_key' => 'sk-test-1234567890',
            'model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-test',
                'model' => 'gpt-4o-mini',
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Hola!']],
                ],
                'usage' => ['total_tokens' => 42],
            ], 200),
        ]);

        $provider = new OpenAiProvider(app(HttpFactory::class));
        $response = $provider->send($config, [
            new AiMessage('system', 'Eres un asistente.'),
            new AiMessage('user', 'Hola'),
        ]);

        Http::assertSent(function ($request) use ($config): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer '.$config->api_key)
                && $request['model'] === 'gpt-4o-mini'
                && $request['messages'][0]['role'] === 'system'
                && $request['messages'][1]['role'] === 'user'
                && $request['messages'][1]['content'] === 'Hola';
        });

        $this->assertSame('Hola!', $response->content);
        $this->assertSame('gpt-4o-mini', $response->model);
        $this->assertSame(42, $response->tokensUsed);
    }

    public function test_openai_throws_on_http_error(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create();

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $provider = new OpenAiProvider(app(HttpFactory::class));

        $this->expectException(\App\Services\Ai\Contracts\AiProviderException::class);
        $provider->send($config, [new AiMessage('user', 'Hola')]);
    }

    public function test_openai_throws_on_malformed_response(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create();

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $provider = new OpenAiProvider(app(HttpFactory::class));

        $this->expectException(\App\Services\Ai\Contracts\AiProviderException::class);
        $provider->send($config, [new AiMessage('user', 'Hola')]);
    }

    public function test_anthropic_splits_system_prompt_from_messages(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Anthropic)->create([
            'api_key' => 'sk-ant-test-1234567890',
            'model' => 'claude-3-5-haiku-latest',
        ]);

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'id' => 'msg_test',
                'model' => 'claude-3-5-haiku-latest',
                'content' => [
                    ['type' => 'text', 'text' => 'Hola desde Anthropic!'],
                ],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
            ], 200),
        ]);

        $provider = new AnthropicProvider(app(HttpFactory::class));
        $response = $provider->send($config, [
            new AiMessage('system', 'Eres un asistente.'),
            new AiMessage('user', 'Hola'),
        ]);

        Http::assertSent(function ($request) use ($config): bool {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', $config->api_key)
                && $request->hasHeader('anthropic-version')
                && $request['system'] === 'Eres un asistente.'
                && count($request['messages']) === 1
                && $request['messages'][0]['role'] === 'user';
        });

        $this->assertSame('Hola desde Anthropic!', $response->content);
        $this->assertSame(15, $response->tokensUsed);
    }

    public function test_anthropic_concatenates_multiple_system_messages(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Anthropic)->create();

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'ok']],
            ], 200),
        ]);

        $provider = new AnthropicProvider(app(HttpFactory::class));
        $provider->send($config, [
            new AiMessage('system', 'Linea 1'),
            new AiMessage('system', 'Linea 2'),
            new AiMessage('user', 'Pregunta'),
        ]);

        Http::assertSent(fn ($request): bool => $request['system'] === "Linea 1\n\nLinea 2");
    }

    public function test_opencode_uses_custom_base_url(): void
    {
        config()->set('ai.opencode.base_url', 'https://proxy.example.com');

        $config = AiConfig::factory()->withProvider(AiProvider::Opencode)->create([
            'api_key' => 'sk-test-1234567890',
            'model' => 'custom-model',
        ]);

        Http::fake([
            'proxy.example.com/v1/chat/completions' => Http::response([
                'model' => 'custom-model',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'respuesta']]],
            ], 200),
        ]);

        $provider = new OpencodeProvider(app(HttpFactory::class));
        $response = $provider->send($config, [new AiMessage('user', 'ping')]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://proxy.example.com/v1/chat/completions');

        $this->assertSame('respuesta', $response->content);
    }

    public function test_opencode_defaults_to_zen_base_url_when_config_is_unset(): void
    {
        // Forzamos que `ai.opencode.base_url` no este
        // configurado para verificar que el fallback del
        // provider apunta a la API real de Opencode Zen.
        config()->offsetUnset('ai.opencode.base_url');

        $config = AiConfig::factory()->withProvider(AiProvider::Opencode)->create([
            'api_key' => 'sk-test-1234567890',
            'model' => 'opencode-go/kimi-k2.6',
        ]);

        Http::fake([
            'opencode.ai/zen/go/v1/chat/completions' => Http::response([
                'model' => 'opencode-go/kimi-k2.6',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'respuesta desde zen']]],
            ], 200),
        ]);

        $provider = new OpencodeProvider(app(HttpFactory::class));
        $response = $provider->send($config, [new AiMessage('user', 'ping')]);

        Http::assertSent(
            fn ($request): bool => $request->url() === 'https://opencode.ai/zen/go/v1/chat/completions'
                && $request['model'] === 'opencode-go/kimi-k2.6',
        );

        $this->assertSame('respuesta desde zen', $response->content);
        $this->assertSame('opencode-go/kimi-k2.6', $response->model);
    }

    public function test_opencode_uses_namespaced_model_in_request_body(): void
    {
        config()->set('ai.opencode.base_url', 'https://opencode.ai/zen/go');

        $config = AiConfig::factory()->withProvider(AiProvider::Opencode)->create([
            'api_key' => 'oc-test-key-12345678',
            'model' => 'opencode-go/kimi-k2.6',
        ]);

        Http::fake([
            '*' => Http::response([
                'model' => 'opencode-go/kimi-k2.6',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'ok']]],
            ], 200),
        ]);

        $provider = new OpencodeProvider(app(HttpFactory::class));
        $provider->send($config, [new AiMessage('user', 'hola')]);

        Http::assertSent(function ($request): bool {
            // El modelo se envia tal cual con el
            // namespace `opencode-go/` que usa la API de
            // Opencode Zen para rutear.
            return $request->hasHeader('Authorization', 'Bearer oc-test-key-12345678')
                && $request['model'] === 'opencode-go/kimi-k2.6';
        });
    }

    public function test_opencode_error_messages_use_its_own_display_name_not_openai(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Opencode)->create();

        Http::fake([
            '*' => Http::response('{"error":"unauthorized"}', 401),
        ]);

        $provider = new OpencodeProvider(app(HttpFactory::class));

        try {
            $provider->send($config, [new AiMessage('user', 'ping')]);
            $this->fail('Se esperaba AiProviderException.');
        } catch (\App\Services\Ai\Contracts\AiProviderException $e) {
            $this->assertStringContainsString('Opencode', $e->getMessage());
            $this->assertStringNotContainsString('OpenAI', $e->getMessage());
        }
    }

    public function test_openai_error_messages_use_its_own_display_name(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create();

        Http::fake([
            '*' => Http::response('{"error":"unauthorized"}', 401),
        ]);

        $provider = new OpenAiProvider(app(HttpFactory::class));

        try {
            $provider->send($config, [new AiMessage('user', 'ping')]);
            $this->fail('Se esperaba AiProviderException.');
        } catch (\App\Services\Ai\Contracts\AiProviderException $e) {
            $this->assertStringContainsString('OpenAI', $e->getMessage());
        }
    }

    public function test_anthropic_error_messages_use_its_own_display_name(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Anthropic)->create();

        Http::fake([
            '*' => Http::response('{"error":"unauthorized"}', 401),
        ]);

        $provider = new AnthropicProvider(app(HttpFactory::class));

        try {
            $provider->send($config, [new AiMessage('user', 'ping')]);
            $this->fail('Se esperaba AiProviderException.');
        } catch (\App\Services\Ai\Contracts\AiProviderException $e) {
            $this->assertStringContainsString('Anthropic', $e->getMessage());
            $this->assertStringNotContainsString('OpenAI', $e->getMessage());
        }
    }
}
