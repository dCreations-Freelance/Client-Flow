<?php

namespace Tests\Feature\Admin;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardColumnManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_columna_al_final(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 0]);
        BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.projects.columns.store', $project), [
                'name' => 'Bloqueada',
                'color' => '#DC2626',
            ])->assertRedirect();

        $this->assertDatabaseHas('board_columns', [
            'project_id' => $project->id,
            'name' => 'Bloqueada',
            'position' => 2,
            'is_default' => false,
        ]);
    }

    public function test_admin_puede_editar_una_columna(): void
    {
        $admin = User::factory()->admin()->create();
        $column = BoardColumn::factory()->create(['name' => 'Antes']);

        $this->actingAs($admin)
            ->put(route('admin.projects.columns.update', [$column->project, $column]), [
                'name' => 'Despues',
                'color' => '#16A34A',
            ])->assertRedirect();

        $column->refresh();
        $this->assertSame('Despues', $column->name);
        $this->assertSame('#16A34A', $column->color);
    }

    public function test_no_se_puede_eliminar_columna_con_tareas(): void
    {
        $admin = User::factory()->admin()->create();
        $column = BoardColumn::factory()->create();
        Task::factory()->create(['column_id' => $column->id]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.columns.destroy', [$column->project, $column]))
            ->assertRedirect()
            ->assertSessionHasErrors('column');

        $this->assertDatabaseHas('board_columns', ['id' => $column->id]);
    }

    public function test_se_puede_eliminar_columna_vacia_y_se_compactan_posiciones(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $a = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 0]);
        $b = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 1]);
        $c = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 2]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.columns.destroy', [$project, $b]))
            ->assertRedirect();

        $this->assertDatabaseMissing('board_columns', ['id' => $b->id]);
        $this->assertSame(0, $a->fresh()->position);
        $this->assertSame(1, $c->fresh()->position);
    }

    public function test_reorder_actualiza_las_posiciones_segun_el_orden_recibido(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $a = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 0]);
        $b = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 1]);
        $c = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 2]);

        $this->actingAs($admin)
            ->post(route('admin.projects.columns.reorder', $project), [
                'columns' => [$c->id, $a->id, $b->id],
            ])->assertRedirect();

        $this->assertSame(0, $c->fresh()->position);
        $this->assertSame(1, $a->fresh()->position);
        $this->assertSame(2, $b->fresh()->position);
    }

    public function test_cliente_no_puede_crear_editar_eliminar_ni_reordenar(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);

        $this->actingAs($client)
            ->post(route('admin.projects.columns.store', $project), ['name' => 'X'])
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->put(route('admin.projects.columns.update', [$project, $column]), ['name' => 'Y'])
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->delete(route('admin.projects.columns.destroy', [$project, $column]))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->post(route('admin.projects.columns.reorder', $project), ['columns' => [$column->id]])
            ->assertRedirect(route('portal.dashboard'));
    }
}
