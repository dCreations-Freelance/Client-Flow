<?php

namespace Tests\Feature\Portal;

use App\Models\AiChatSession;
use App\Models\AiConfig;
use App\Models\Organization;
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

    public function test_client_sees_empty_state_when_no_sessions(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        AiConfig::factory()->forProject($project)->create();

        $this->actingAs($client)
            ->get(route('portal.projects.ai', $project))
            ->assertOk()
            ->assertSee('Aun no tienes conversaciones');
    }

    public function test_client_is_redirected_to_their_most_recent_session(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        AiConfig::factory()->forProject($project)->create();
        $session = AiChatSession::factory()->forProject($project)->forUser($client)->create();

        $this->actingAs($client)
            ->get(route('portal.projects.ai', $project))
            ->assertRedirect(route('portal.projects.ai.show', ['project' => $project->id, 'session' => $session->id]));
    }

    public function test_client_can_create_a_new_session(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        AiConfig::factory()->forProject($project)->create();

        $this->actingAs($client)
            ->post(route('portal.projects.ai.sessions.store', $project), [
                'title' => 'Sobre los entregables',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ai_chat_sessions', [
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Sobre los entregables',
        ]);
    }

    public function test_client_can_open_their_own_session(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        $session = AiChatSession::factory()->forProject($project)->forUser($client)->create();

        $this->actingAs($client)
            ->get(route('portal.projects.ai.show', ['project' => $project->id, 'session' => $session->id]))
            ->assertOk();
    }

    public function test_client_cannot_open_another_user_session(): void
    {
        $client = User::factory()->client()->create();
        $other = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        $session = AiChatSession::factory()->forProject($project)->forUser($other)->create();

        $this->actingAs($client)
            ->get(route('portal.projects.ai.show', ['project' => $project->id, 'session' => $session->id]))
            ->assertForbidden();
    }

    public function test_client_cannot_access_ai_chat_for_a_project_in_another_organization(): void
    {
        $client = User::factory()->client()->create();
        $otherOrg = Organization::factory()->create();
        $otherProject = Project::factory()->create(['organization_id' => $otherOrg->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.ai', $otherProject))
            ->assertForbidden();
    }

    public function test_client_cannot_access_ai_chat_for_a_hidden_project(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create([
            'organization_id' => $org->id,
            'is_visible_to_client' => false,
        ]);

        $this->actingAs($client)
            ->get(route('portal.projects.ai', $project))
            ->assertForbidden();
    }

    public function test_client_can_delete_their_own_session(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        $session = AiChatSession::factory()->forProject($project)->forUser($client)->create();

        $this->actingAs($client)
            ->delete(route('portal.projects.ai.destroy', ['project' => $project->id, 'session' => $session->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('ai_chat_sessions', ['id' => $session->id]);
    }

    public function test_client_cannot_delete_another_user_session(): void
    {
        $client = User::factory()->client()->create();
        $other = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client);
        $project = Project::factory()->create(['organization_id' => $org->id]);
        $session = AiChatSession::factory()->forProject($project)->forUser($other)->create();

        $this->actingAs($client)
            ->delete(route('portal.projects.ai.destroy', ['project' => $project->id, 'session' => $session->id]))
            ->assertForbidden();
    }
}
