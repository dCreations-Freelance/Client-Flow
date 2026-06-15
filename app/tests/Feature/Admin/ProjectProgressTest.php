<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_actualizar_el_progreso(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['progress' => 0]);

        $this->actingAs($admin)
            ->patch(route('admin.projects.progress.update', $project), [
                'progress' => 75,
            ])->assertRedirect();

        $this->assertSame(75, $project->fresh()->progress);
    }

    public function test_rechaza_progreso_negativo(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.projects.progress.update', $project), [
                'progress' => -1,
            ])->assertSessionHasErrors('progress');
    }

    public function test_rechaza_progreso_mayor_a_100(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.projects.progress.update', $project), [
                'progress' => 150,
            ])->assertSessionHasErrors('progress');
    }

    public function test_cliente_no_puede_actualizar_progreso(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();

        $this->actingAs($client)
            ->patch(route('admin.projects.progress.update', $project), [
                'progress' => 50,
            ])->assertRedirect(route('portal.dashboard'));
    }
}
