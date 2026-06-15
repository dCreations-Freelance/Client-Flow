<?php

namespace Tests\Feature\Portal;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_puede_ver_el_tablero_de_su_proyecto(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create(['is_visible_to_client' => true]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.board', $project))
            ->assertOk();
    }

    public function test_cliente_no_ve_tablero_de_proyecto_oculto(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create(['is_visible_to_client' => false]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        // El proyecto existe para el cliente (es miembro de la org)
        // pero no esta visible: la policy devuelve 403.
        $this->actingAs($client)
            ->get(route('portal.projects.board', $project))
            ->assertForbidden();
    }

    public function test_cliente_no_ve_tablero_de_proyecto_archivado(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->archived()->create(['is_visible_to_client' => true]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        // Un proyecto archivado no es visible aunque el flag diga
        // true: 403.
        $this->actingAs($client)
            ->get(route('portal.projects.board', $project))
            ->assertForbidden();
    }

    public function test_cliente_no_ve_tablero_de_proyecto_de_otra_org(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('portal.projects.board', $project))
            ->assertForbidden();
    }

    public function test_cliente_puede_ver_el_detalle_de_una_tarea(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create(['is_visible_to_client' => true]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.tasks.show', [$project, $task]))
            ->assertOk()
            ->assertSee($task->title);
    }
}
