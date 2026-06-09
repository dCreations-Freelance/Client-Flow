<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Notifications\ProjectUpdatePublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectUpdateTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_public_project_update(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.projects.updates.store', $project), [
            'title' => 'Demo publicada',
            'content' => 'Ya puedes revisar la primera demo del proyecto.',
            'visibility' => 'public',
            'notify_client' => '1',
        ]);

        $response->assertRedirect(route('admin.projects.timeline', $project));
        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'author_id' => $admin->id,
            'title' => 'Demo publicada',
            'visibility' => ProjectUpdate::VISIBILITY_PUBLIC,
            'notify_client' => true,
        ]);
        Notification::assertSentOnDemand(ProjectUpdatePublished::class);
    }

    public function test_internal_updates_are_hidden_from_client_timeline(): void
    {
        $user = User::factory()->client()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['client_id' => $client->id, 'is_visible_to_client' => true]);

        ProjectUpdate::factory()->create([
            'project_id' => $project->id,
            'title' => 'Avance visible',
            'visibility' => ProjectUpdate::VISIBILITY_PUBLIC,
        ]);
        ProjectUpdate::factory()->internal()->create([
            'project_id' => $project->id,
            'title' => 'Nota interna',
        ]);

        $response = $this->actingAs($user)->get(route('portal.projects.timeline', $project));

        $response->assertOk();
        $response->assertSee('Avance visible');
        $response->assertDontSee('Nota interna');
    }

    public function test_client_cannot_open_another_clients_project_timeline(): void
    {
        $user = User::factory()->client()->create();
        Client::factory()->create(['user_id' => $user->id]);
        $otherProject = Project::factory()->create(['is_visible_to_client' => true]);

        $response = $this->actingAs($user)->get(route('portal.projects.timeline', $otherProject));

        $response->assertNotFound();
    }
}
