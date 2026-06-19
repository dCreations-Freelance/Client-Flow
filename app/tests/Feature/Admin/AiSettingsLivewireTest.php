<?php

namespace Tests\Feature\Admin;

use App\Enums\AiProvider;
use App\Livewire\Admin\AiConfig\SettingsForm;
use App\Models\AiConfig;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AiSettingsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake();
    }

    public function test_admin_can_persist_a_global_config_through_livewire(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingsForm::class)
            ->set('provider', AiProvider::Opencode->value)
            ->set('apiKey', 'oc-test-1234567890')
            ->set('model', 'opencode-go/kimi-k2.6')
            ->set('isActive', true)
            ->set('maxMessagesPerHour', 25)
            ->set('maxSessionsPerDay', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ai-config-saved');

        $config = AiConfig::whereNull('project_id')->first();
        $this->assertNotNull($config);
        $this->assertSame(AiProvider::Opencode, $config->provider);
        $this->assertSame('opencode-go/kimi-k2.6', $config->model);
        $this->assertSame(25, $config->max_messages_per_hour);
    }

    public function test_admin_can_persist_a_per_project_config_through_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        Livewire::actingAs($admin)
            ->test(SettingsForm::class, ['projectId' => $project->id])
            ->set('provider', AiProvider::Anthropic->value)
            ->set('apiKey', 'sk-ant-test-1234567890')
            ->set('model', 'claude-3-5-haiku-latest')
            ->set('isActive', true)
            ->set('maxMessagesPerHour', 15)
            ->set('maxSessionsPerDay', 3)
            ->call('save')
            ->assertHasNoErrors();

        $config = AiConfig::where('project_id', $project->id)->first();
        $this->assertNotNull($config);
        $this->assertSame(AiProvider::Anthropic, $config->provider);
    }

    public function test_saving_without_api_key_preserves_the_previous_one_via_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        $original = AiConfig::factory()->create([
            'project_id' => null,
            'api_key' => 'sk-original-key-value-1234',
        ]);

        Livewire::actingAs($admin)
            ->test(SettingsForm::class)
            // El componente parte del `mount` con la config
            // cargada, y `apiKey` se resetea en mount.
            // Aqui lo dejamos vacio a proposito para
            // verificar que el guardado no sobreescribe.
            ->set('apiKey', '')
            ->set('provider', AiProvider::Openai->value)
            ->set('model', 'gpt-4o')
            ->set('isActive', true)
            ->set('maxMessagesPerHour', 20)
            ->set('maxSessionsPerDay', 10)
            ->call('save')
            ->assertHasNoErrors();

        $original->refresh();
        $this->assertSame('sk-original-key-value-1234', $original->api_key);
        $this->assertSame('gpt-4o', $original->model);
    }

    public function test_validation_blocks_invalid_provider_via_livewire(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingsForm::class)
            ->set('provider', 'not-a-real-provider')
            ->set('apiKey', 'oc-test-1234567890')
            ->set('isActive', true)
            ->set('maxMessagesPerHour', 20)
            ->set('maxSessionsPerDay', 10)
            ->call('save')
            ->assertHasErrors(['provider']);
    }
}
