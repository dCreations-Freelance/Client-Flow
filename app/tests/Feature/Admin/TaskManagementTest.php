<?php

namespace Tests\Feature\Admin;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithColumnsAndMember(): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id, 'position' => 0]);
        $member = User::factory()->create();
        $project->members()->attach($member->id);

        return [$admin, $project, $column, $member];
    }

    public function test_admin_puede_crear_una_tarea(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Implementar login',
                'priority' => 'high',
                'type' => 'feature',
            ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Implementar login',
            'priority' => 'high',
            'type' => 'feature',
            'created_by' => $admin->id,
        ]);
    }

    public function test_validacion_rechaza_titulo_vacio(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => '',
                'priority' => 'medium',
                'type' => 'task',
            ])->assertSessionHasErrors('title');
    }

    public function test_validacion_rechaza_columna_inexistente(): void
    {
        [$admin, $project, , ] = $this->projectWithColumnsAndMember();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => 99999,
                'title' => 'Test',
                'priority' => 'medium',
                'type' => 'task',
            ])->assertSessionHasErrors('column_id');
    }

    public function test_rechaza_asignar_usuario_que_no_es_miembro_del_proyecto(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();
        $outsider = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Tarea',
                'priority' => 'medium',
                'type' => 'task',
                'assignee_id' => $outsider->id,
            ])->assertSessionHasErrors('assignee_id');
    }

    public function test_rechaza_parent_de_otro_proyecto(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();
        $otherProject = Project::factory()->create();
        $otherColumn = BoardColumn::factory()->create(['project_id' => $otherProject->id]);
        $otherTask = Task::factory()->create(['project_id' => $otherProject->id, 'column_id' => $otherColumn->id]);

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'parent_id' => $otherTask->id,
                'title' => 'Subtarea',
                'priority' => 'low',
                'type' => 'task',
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_admin_puede_editar_y_asignar_tarea_a_miembro(): void
    {
        [$admin, $project, $column, $member] = $this->projectWithColumnsAndMember();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($admin)
            ->put(route('admin.projects.tasks.update', [$project, $task]), [
                'title' => 'Titulo actualizado',
                'priority' => 'critical',
                'type' => 'bug',
                'assignee_id' => $member->id,
            ])->assertRedirect();

        $task->refresh();
        $this->assertSame('Titulo actualizado', $task->title);
        $this->assertSame('critical', $task->priority->value);
        $this->assertSame('bug', $task->type->value);
        $this->assertSame($member->id, $task->assignee_id);
    }

    public function test_admin_puede_completar_y_reabrir_tarea(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.complete', [$project, $task]))
            ->assertRedirect();
        $this->assertNotNull($task->fresh()->completed_at);

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.reopen', [$project, $task]))
            ->assertRedirect();
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_admin_puede_eliminar_tarea_y_se_compacta_posicion(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();
        $task1 = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id, 'position' => 0]);
        $task2 = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id, 'position' => 1]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.tasks.destroy', [$project, $task1]))
            ->assertRedirect();

        $this->assertSame(0, $task2->fresh()->position);
    }

    public function test_subtareas_se_eliminan_con_el_padre(): void
    {
        [$admin, $project, $column, ] = $this->projectWithColumnsAndMember();
        $parent = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id, 'parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.tasks.destroy', [$project, $parent]))
            ->assertRedirect();

        $this->assertDatabaseMissing('tasks', ['parent_id' => $parent->id]);
    }

    public function test_cliente_no_puede_crear_editar_ni_eliminar_tareas(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($client)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'X',
                'priority' => 'medium',
                'type' => 'task',
            ])->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->put(route('admin.projects.tasks.update', [$project, $task]), [
                'title' => 'Y',
                'priority' => 'low',
                'type' => 'task',
            ])->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->delete(route('admin.projects.tasks.destroy', [$project, $task]))
            ->assertRedirect(route('portal.dashboard'));
    }
}
