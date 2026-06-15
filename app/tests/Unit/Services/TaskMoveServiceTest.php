<?php

namespace Tests\Unit\Services;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMoveServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeProjectWithColumns(int $columns = 4): Project
    {
        $project = Project::factory()->create();
        for ($i = 0; $i < $columns; $i++) {
            BoardColumn::factory()->create([
                'project_id' => $project->id,
                'name' => "Col {$i}",
                'position' => $i,
                'is_default' => true,
            ]);
        }

        return $project;
    }

    public function test_mover_tarea_entre_columnas_recalcula_posiciones(): void
    {
        $project = $this->makeProjectWithColumns(3);
        $columns = $project->columns()->ordered()->get();
        $first = $columns[0];
        $second = $columns[1];

        $task1 = Task::factory()->create(['project_id' => $project->id, 'column_id' => $first->id, 'position' => 0]);
        $task2 = Task::factory()->create(['project_id' => $project->id, 'column_id' => $first->id, 'position' => 1]);
        $task3 = Task::factory()->create(['project_id' => $project->id, 'column_id' => $second->id, 'position' => 0]);

        $mover = app(\App\Services\TaskMoveService::class);
        $mover->move($task1, $second, 0);

        // task1 ahora esta en second columna, position 0.
        $task1->refresh();
        $this->assertSame($second->id, $task1->column_id);
        $this->assertSame(0, $task1->position);

        // task3 se desplaza a position 1 en la columna destino.
        $this->assertSame(1, $task3->fresh()->position);

        // task2 queda en position 0 en la columna origen (compactada).
        $this->assertSame(0, $task2->fresh()->position);
    }

    public function test_mover_a_la_ultima_columna_marca_completada(): void
    {
        $project = $this->makeProjectWithColumns(3);
        $columns = $project->columns()->ordered()->get();
        $first = $columns[0];
        $last = $columns[2];

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $first->id,
            'completed_at' => null,
        ]);

        app(\App\Services\TaskMoveService::class)->move($task, $last, 0);

        $task->refresh();
        $this->assertNotNull($task->completed_at);
    }

    public function test_salir_de_la_ultima_columna_reabre_la_tarea(): void
    {
        $project = $this->makeProjectWithColumns(3);
        $columns = $project->columns()->ordered()->get();
        $first = $columns[0];
        $last = $columns[2];

        $task = Task::factory()->completed()->create([
            'project_id' => $project->id,
            'column_id' => $last->id,
        ]);

        app(\App\Services\TaskMoveService::class)->move($task, $first, 0);

        $task->refresh();
        $this->assertNull($task->completed_at);
    }

    public function test_rechaza_columna_de_otro_proyecto(): void
    {
        $project1 = $this->makeProjectWithColumns(2);
        $project2 = $this->makeProjectWithColumns(2);
        $task = Task::factory()->create(['project_id' => $project1->id]);
        $otherColumn = $project2->columns()->first();

        $this->expectException(\InvalidArgumentException::class);
        app(\App\Services\TaskMoveService::class)->move($task, $otherColumn, 0);
    }
}
