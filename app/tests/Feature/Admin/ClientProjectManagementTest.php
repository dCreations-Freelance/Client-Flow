<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_client(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/clients', [
            'name' => 'Marta Lopez',
            'company' => 'Clinica Norte',
            'email' => 'marta@example.com',
            'phone' => '600000000',
            'notes' => 'Cliente prioritario',
            'status' => 'active',
        ]);

        $client = Client::where('email', 'marta@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.clients.show', $client));
        $this->assertDatabaseHas('clients', [
            'name' => 'Marta Lopez',
            'email' => 'marta@example.com',
        ]);
    }

    public function test_admin_can_create_a_project_for_a_client(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/projects', [
            'client_id' => $client->id,
            'name' => 'Web clinica dental',
            'description' => 'Nueva web corporativa',
            'goal' => 'Captar solicitudes de cita',
            'status' => 'in_progress',
            'progress' => 35,
            'current_phase' => 'Diseno visual',
            'next_milestone' => 'Revision de home',
            'is_visible_to_client' => '1',
        ]);

        $project = Project::where('name', 'Web clinica dental')->firstOrFail();

        $response->assertRedirect(route('admin.projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'client_id' => $client->id,
            'slug' => 'web-clinica-dental',
            'progress' => 35,
            'is_visible_to_client' => true,
        ]);
    }

    public function test_admin_can_create_a_client_invitation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/clients/invite', [
            'name' => 'Javier Martin',
            'email' => 'javier@example.com',
            'company' => 'JM Reformas',
        ]);

        $client = Client::where('email', 'javier@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.clients.show', $client));
        $this->assertDatabaseHas('client_invitations', [
            'client_id' => $client->id,
            'email' => 'javier@example.com',
            'created_by' => $admin->id,
        ]);
        $this->assertSame('sent', $client->fresh()->invitation_status);
    }

    public function test_client_dashboard_only_shows_visible_projects_for_their_client_record(): void
    {
        $user = User::factory()->client()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $otherClient = Client::factory()->create();

        Project::factory()->create([
            'client_id' => $client->id,
            'name' => 'Proyecto visible',
            'is_visible_to_client' => true,
        ]);
        Project::factory()->hiddenFromClient()->create([
            'client_id' => $client->id,
            'name' => 'Proyecto oculto',
        ]);
        Project::factory()->create([
            'client_id' => $otherClient->id,
            'name' => 'Proyecto ajeno',
        ]);

        $response = $this->actingAs($user)->get('/portal/dashboard');

        $response->assertOk();
        $response->assertSee('Proyecto visible');
        $response->assertDontSee('Proyecto oculto');
        $response->assertDontSee('Proyecto ajeno');
    }
}
