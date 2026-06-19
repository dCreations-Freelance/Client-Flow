<?php

namespace Tests\Feature\Admin;

use App\Models\AiChatSession;
use App\Models\AiConfig;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake();
    }

    public function test_admin_can_view_the_empty_ai_chat_state(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        AiConfig::factory()->forProject($project)->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.ai', $project))
            ->assertOk()
            ->assertSee('Aun no tienes conversaciones');
    }

    public function test_admin_is_redirected_to_their_most_recent_session(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        AiConfig::factory()->forProject($project)->create();
        $session = AiChatSession::factory()->forProject($project)->forUser($admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.ai', $project))
            ->assertRedirect(route('admin.projects.ai.show', ['project' => $project->id, 'session' => $session->id]));
    }

    public function test_admin_can_open_a_specific_session(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        AiConfig::factory()->forProject($project)->create();
        $session = AiChatSession::factory()->forProject($project)->forUser($admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.ai.show', ['project' => $project->id, 'session' => $session->id]))
            ->assertOk();
    }

    public function test_admin_can_delete_a_session(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $session = AiChatSession::factory()->forProject($project)->forUser($admin)->create();

        $this->actingAs($admin)
            ->delete(route('admin.projects.ai.destroy', ['project' => $project->id, 'session' => $session->id]))
            ->assertRedirect(route('admin.projects.ai', $project));

        $this->assertDatabaseMissing('ai_chat_sessions', ['id' => $session->id]);
    }
}
