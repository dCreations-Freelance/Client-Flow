<?php

namespace Tests\Feature\Admin;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_ver_el_tablero(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        BoardColumn::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)
            ->get(route('admin.projects.board', $project))
            ->assertOk()
            ->assertSee('Kanban', false);
    }

    public function test_admin_puede_mover_tarea_entre_columnas(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $col1 = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 0]);
        $col2 = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 1]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $col1->id, 'position' => 0]);

        $this->actingAs($admin)
            ->patch(route('admin.projects.tasks.move', [$project, $task]), [
                'column_id' => $col2->id,
                'position' => 0,
            ])->assertRedirect();

        $task->refresh();
        $this->assertSame($col2->id, $task->column_id);
        $this->assertSame(0, $task->position);
    }

    public function test_crear_proyecto_genera_cuatro_columnas_default(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Project::factory()->create()->organization;

        // Usar el controlador para crear el proyecto, que ya invoca
        // el servicio. Como ProjectController necesita organization,
        // lo creamos con un factory directo.
        $response = $this->actingAs($admin)->post(route('admin.projects.store'), [
            'name' => 'Proyecto Kanban',
            'organization_id' => $org->id,
            'status' => 'planning',
            'is_visible_to_client' => true,
        ]);

        $project = \App\Models\Project::where('name', 'Proyecto Kanban')->first();

        $this->assertNotNull($project);
        $this->assertCount(4, $project->columns()->get());
        $this->assertSame(['Por hacer', 'En curso', 'En revision', 'Hecho'], $project->columns()->ordered()->pluck('name')->all());
    }

    public function test_cliente_no_puede_acceder_al_tablero_admin(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('admin.projects.board', $project))
            ->assertRedirect(route('portal.dashboard'));
    }
}
