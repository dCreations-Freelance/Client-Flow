<?php

namespace Tests\Unit\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_root_solo_devuelve_tareas_sin_padre(): void
    {
        $project = Project::factory()->create();
        $root = Task::factory()->create(['project_id' => $project->id]);
        $sub = Task::factory()->create(['project_id' => $project->id, 'parent_id' => $root->id]);

        $ids = Task::root()->pluck('id')->all();

        $this->assertContains($root->id, $ids);
        $this->assertNotContains($sub->id, $ids);
    }

    public function test_scope_completed_y_pending(): void
    {
        $done = Task::factory()->completed()->create();
        $pending = Task::factory()->create();

        $this->assertContains($done->id, Task::completed()->pluck('id')->all());
        $this->assertNotContains($done->id, Task::pending()->pluck('id')->all());
        $this->assertContains($pending->id, Task::pending()->pluck('id')->all());
    }

    public function test_scope_overdue_solo_tareas_no_completadas_con_due_date_pasado(): void
    {
        $overdue = Task::factory()->overdue()->create();
        $futureTask = Task::factory()->create(['due_date' => now()->addDays(5)->toDateString()]);
        $completedOverdue = Task::factory()->completed()->create(['due_date' => now()->subDay()->toDateString()]);
        $noDue = Task::factory()->create(['due_date' => null]);

        $ids = Task::overdue()->pluck('id')->all();

        $this->assertContains($overdue->id, $ids);
        $this->assertNotContains($futureTask->id, $ids);
        $this->assertNotContains($completedOverdue->id, $ids);
        $this->assertNotContains($noDue->id, $ids);
    }

    public function test_mark_completed_y_mark_pending_son_idempotentes(): void
    {
        $task = Task::factory()->create();

        $task->markCompleted();
        $first = $task->fresh()->completed_at;
        $this->assertNotNull($first);

        $task->markCompleted();
        $this->assertEquals(
            $first->toDateTimeString(),
            $task->fresh()->completed_at->toDateTimeString(),
        );

        $task->markPending();
        $this->assertNull($task->fresh()->completed_at);

        $task->markPending();
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_is_overdue_devuelve_false_si_no_hay_due_date(): void
    {
        $task = Task::factory()->create(['due_date' => null]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_is_overdue_devuelve_false_si_esta_completada(): void
    {
        $task = Task::factory()->completed()->create(['due_date' => now()->subDay()->toDateString()]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_subtasks_count_y_subtasks_completed_count(): void
    {
        $parent = Task::factory()->create();
        Task::factory()->count(2)->create(['parent_id' => $parent->id]);
        Task::factory()->completed()->create(['parent_id' => $parent->id]);

        $this->assertSame(3, $parent->subtasks_count);
        $this->assertSame(1, $parent->subtasks_completed_count);
    }

    public function test_casts_de_enums_y_fechas(): void
    {
        $task = Task::factory()->create([
            'priority' => TaskPriority::Critical,
            'type' => TaskType::Bug,
            'estimated_hours' => 5.5,
        ]);

        $this->assertInstanceOf(TaskPriority::class, $task->priority);
        $this->assertSame(TaskPriority::Critical, $task->priority);
        $this->assertInstanceOf(TaskType::class, $task->type);
        $this->assertSame(TaskType::Bug, $task->type);
        $this->assertSame('5.50', (string) $task->estimated_hours);
    }

    public function test_relacion_assignee_apunta_al_usuario(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create();

        $this->assertTrue($task->assignee->is($user));
    }
}
