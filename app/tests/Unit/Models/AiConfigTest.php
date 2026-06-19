<?php

namespace Tests\Unit\Models;

use App\Enums\AiProvider;
use App\Models\AiConfig;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_is_encrypted_at_rest(): void
    {
        $plain = 'sk-test-'.str_repeat('a', 20);

        $config = AiConfig::factory()->create(['api_key' => $plain]);

        $this->assertSame($plain, $config->api_key);

        $raw = \DB::table('ai_configs')->where('id', $config->id)->value('api_key');
        $this->assertNotSame($plain, $raw);
        $this->assertStringNotContainsString($plain, (string) $raw);
    }

    public function test_provider_is_cast_to_ai_provider_enum(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Anthropic)->create();

        $this->assertSame(AiProvider::Anthropic, $config->provider);
    }

    public function test_is_global_reflects_whether_project_id_is_null(): void
    {
        $global = AiConfig::factory()->create(['project_id' => null]);
        $project = AiConfig::factory()->forProject(Project::factory()->create())->create();

        $this->assertTrue($global->isGlobal());
        $this->assertFalse($project->isGlobal());
    }

    public function test_effective_model_falls_back_to_provider_default(): void
    {
        $config = AiConfig::factory()->withProvider(AiProvider::Openai)->create(['model' => null]);
        $this->assertSame(AiProvider::Openai->defaultModel(), $config->effectiveModel());

        $config->model = 'gpt-4o';
        $this->assertSame('gpt-4o', $config->effectiveModel());
    }

    public function test_effective_system_prompt_returns_null_when_blank(): void
    {
        $config = AiConfig::factory()->create(['system_prompt' => null]);
        $this->assertNull($config->effectiveSystemPrompt());

        $config->system_prompt = '   ';
        $this->assertNull($config->effectiveSystemPrompt());

        $config->system_prompt = 'Eres un asistente';
        $this->assertSame('Eres un asistente', $config->effectiveSystemPrompt());
    }

    public function test_active_scope_excludes_inactive_configs(): void
    {
        $active = AiConfig::factory()->create(['is_active' => true]);
        $inactive = AiConfig::factory()->inactive()->create();

        $ids = AiConfig::active()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }
}
