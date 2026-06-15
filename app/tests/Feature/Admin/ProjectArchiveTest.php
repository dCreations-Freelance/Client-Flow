<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_archivar_un_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.archive', $project))
            ->assertRedirect();

        $this->assertNotNull($project->fresh()->archived_at);
    }

    public function test_admin_puede_desarchivar_un_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->archived()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.unarchive', $project))
            ->assertRedirect();

        $this->assertNull($project->fresh()->archived_at);
    }

    public function test_cliente_no_puede_archivar_proyectos(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();

        $this->actingAs($client)
            ->post(route('admin.projects.archive', $project))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertNull($project->fresh()->archived_at);
    }

    public function test_archivar_es_idempotente(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)->post(route('admin.projects.archive', $project));
        $firstAt = $project->fresh()->archived_at;

        sleep(1);
        $this->actingAs($admin)->post(route('admin.projects.archive', $project));
        $this->assertEquals(
            $firstAt->toDateTimeString(),
            $project->fresh()->archived_at->toDateTimeString(),
        );
    }
}
