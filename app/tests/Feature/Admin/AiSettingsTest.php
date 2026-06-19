<?php

namespace Tests\Feature\Admin;

use App\Enums\AiProvider;
use App\Models\AiConfig;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake();
    }

    public function test_admin_can_view_the_ai_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.ai.config.edit'))
            ->assertOk()
            ->assertSee('Configuracion de IA');
    }

    public function test_client_cannot_view_the_ai_settings_page(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.ai.config.edit'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_can_save_a_global_ai_config(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.ai.config.update'), [
            'project_id' => null,
            'provider' => AiProvider::Openai->value,
            'api_key' => 'sk-test-'.str_repeat('a', 20),
            'model' => 'gpt-4o-mini',
            'system_prompt' => null,
            'is_active' => true,
            'max_messages_per_hour' => 30,
            'max_sessions_per_day' => 5,
        ])->assertRedirect(route('admin.ai.config.edit'));

        $config = AiConfig::whereNull('project_id')->first();
        $this->assertNotNull($config);
        $this->assertSame(AiProvider::Openai, $config->provider);
        $this->assertSame(30, $config->max_messages_per_hour);
    }

    public function test_updating_without_api_key_preserves_the_previous_one(): void
    {
        $admin = User::factory()->admin()->create();
        $original = AiConfig::factory()->create([
            'project_id' => null,
            'api_key' => 'sk-original-key-value-1234',
        ]);

        $this->actingAs($admin)->put(route('admin.ai.config.update'), [
            'project_id' => null,
            'provider' => AiProvider::Openai->value,
            'api_key' => '',
            'model' => 'gpt-4o',
            'system_prompt' => null,
            'is_active' => true,
            'max_messages_per_hour' => 20,
            'max_sessions_per_day' => 10,
        ])->assertRedirect();

        $original->refresh();
        $this->assertSame('sk-original-key-value-1234', $original->api_key);
        $this->assertSame('gpt-4o', $original->model);
    }

    public function test_admin_can_save_a_per_project_ai_config(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)->put(route('admin.ai.config.update', ['project_id' => $project->id]), [
            'project_id' => $project->id,
            'provider' => AiProvider::Anthropic->value,
            'api_key' => 'sk-ant-test-'.str_repeat('b', 20),
            'model' => 'claude-3-5-haiku-latest',
            'system_prompt' => null,
            'is_active' => true,
            'max_messages_per_hour' => 25,
            'max_sessions_per_day' => 8,
        ])->assertRedirect();

        $config = AiConfig::where('project_id', $project->id)->first();
        $this->assertNotNull($config);
        $this->assertSame(AiProvider::Anthropic, $config->provider);
    }

    public function test_admin_can_test_the_connection(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'OK']]],
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        AiConfig::factory()->forProject($project)->withProvider(AiProvider::Openai)->create();

        $this->actingAs($admin)
            ->post(route('admin.ai.config.test', ['project_id' => $project->id]))
            ->assertRedirect();

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.openai.com'));
    }
}
