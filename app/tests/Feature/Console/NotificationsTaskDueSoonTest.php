<?php

namespace Tests\Feature\Console;

use App\Enums\NotificationEvent;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests del comando `notifications:task-due-soon`.
 *
 * Cubre: deteccion de tareas en ventana, exclusion de
 * completadas/subtareas, respeto del opt-out, sello
 * anti-duplicados y modo dry-run.
 */
class NotificationsTaskDueSoonTest extends TestCase
{
    use RefreshDatabase;

    private function taskWithDueDate(Carbon $dueDate, ?User $assignee = null, bool $completed = false, ?int $parentId = null): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);

        if ($assignee === null) {
            $assignee = User::factory()->create();
        }
        $project->members()->attach($assignee->id);

        $task = Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'parent_id' => $parentId,
            'title' => 'Tarea con deadline',
            'priority' => 'medium',
            'type' => 'task',
            'assignee_id' => $assignee->id,
            'created_by' => $admin->id,
            'due_date' => $dueDate->toDateString(),
        ]);

        if ($completed) {
            $task->markCompleted();
        }

        return [$task, $assignee];
    }

    public function test_detecta_tareas_con_deadline_en_la_ventana(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(Carbon::today()->addDays(2));

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        Notification::assertSentTo($assignee, \App\Notifications\TaskDueSoon::class);
    }

    public function test_ignora_tareas_ya_completadas(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(
            Carbon::today()->addDays(2),
            completed: true,
        );

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        Notification::assertNotSentTo($assignee, \App\Notifications\TaskDueSoon::class);
    }

    public function test_ignora_tareas_sin_assignee(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);

        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Sin asignar',
            'priority' => 'medium',
            'type' => 'task',
            'assignee_id' => null,
            'created_by' => $admin->id,
            'due_date' => Carbon::today()->addDays(1)->toDateString(),
        ]);

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_ignora_tareas_fuera_de_la_ventana(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(Carbon::today()->addDays(10));

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        Notification::assertNotSentTo($assignee, \App\Notifications\TaskDueSoon::class);
    }

    public function test_sella_last_due_notification_at_despues_de_enviar(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(Carbon::today()->addDays(1));

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        $task->refresh();
        $this->assertNotNull($task->last_due_notification_at);
    }

    public function test_no_re_envia_si_y_se_sello_en_las_ultimas_24h(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(Carbon::today()->addDays(1));
        $task->forceFill(['last_due_notification_at' => now()])->save();

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        Notification::assertNotSentTo($assignee, \App\Notifications\TaskDueSoon::class);
    }

    public function test_respeta_opt_out_del_usuario(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(Carbon::today()->addDays(1));
        NotificationPreferenceFactory::new()
            ->forUser($assignee)
            ->forEvent(NotificationEvent::TaskDueSoon)
            ->disabled()
            ->create();

        $this->artisan('notifications:task-due-soon')->assertSuccessful();

        Notification::assertNotSentTo($assignee, \App\Notifications\TaskDueSoon::class);
    }

    public function test_dry_run_no_envia_ni_sella(): void
    {
        Notification::fake();

        [$task, $assignee] = $this->taskWithDueDate(Carbon::today()->addDays(1));

        $this->artisan('notifications:task-due-soon --dry-run')->assertSuccessful();

        Notification::assertNotSentTo($assignee, \App\Notifications\TaskDueSoon::class);

        $task->refresh();
        $this->assertNull($task->last_due_notification_at);
    }
}
