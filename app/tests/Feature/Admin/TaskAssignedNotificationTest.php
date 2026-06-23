<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationEvent;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests del trigger "tarea asignada".
 *
 * Verifica que `TaskController::store` y `update` disparan la
 * notificacion `TaskAssigned` al assignee (cuando cambia),
 * respetando el opt-out por canal.
 */
class TaskAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithColumnAndMember(): array
    {
        $admin = User::factory()->admin()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $project->members()->attach($assignee->id);

        return [$admin, $assignee, $project, $column];
    }

    public function test_crear_tarea_con_assignee_dispara_notificacion(): void
    {
        Notification::fake();
        [$admin, $assignee, $project, $column] = $this->projectWithColumnAndMember();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Implementar login',
                'assignee_id' => $assignee->id,
                'priority' => 'high',
                'type' => 'feature',
            ])
            ->assertRedirect();

        Notification::assertSentTo($assignee, \App\Notifications\TaskAssigned::class);
    }

    public function test_crear_tarea_sin_assignee_no_dispara_notificacion(): void
    {
        Notification::fake();
        [$admin, , $project, $column] = $this->projectWithColumnAndMember();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Sin asignar',
                'priority' => 'medium',
                'type' => 'task',
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_actor_que_se_asigna_a_si_mismo_no_recibe_notificacion(): void
    {
        Notification::fake();
        [$admin, , $project, $column] = $this->projectWithColumnAndMember();
        $project->members()->attach($admin->id);

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Para mi',
                'assignee_id' => $admin->id,
                'priority' => 'medium',
                'type' => 'task',
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_reasignar_tarea_dispara_notificacion_al_nuevo_assignee(): void
    {
        Notification::fake();
        [$admin, $assignee, $project, $column] = $this->projectWithColumnAndMember();

        // Tarea asignada a un tercero inicialmente.
        $other = User::factory()->create();
        $project->members()->attach($other->id);
        $task = Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Reasignar',
            'priority' => 'medium',
            'type' => 'task',
            'assignee_id' => $other->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.projects.tasks.update', [$project, $task]), [
                'column_id' => $column->id,
                'title' => 'Reasignar',
                'priority' => 'medium',
                'type' => 'task',
                'assignee_id' => $assignee->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($assignee, \App\Notifications\TaskAssigned::class);
    }

    public function test_update_que_no_cambia_assignee_no_re_dispara_notificacion(): void
    {
        Notification::fake();
        [$admin, $assignee, $project, $column] = $this->projectWithColumnAndMember();

        $task = Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Sin cambios de assignee',
            'priority' => 'medium',
            'type' => 'task',
            'assignee_id' => $assignee->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.projects.tasks.update', [$project, $task]), [
                'column_id' => $column->id,
                'title' => 'Titulo actualizado',
                'priority' => 'high',
                'type' => 'task',
                'assignee_id' => $assignee->id,
            ])
            ->assertRedirect();

        Notification::assertNotSentTo($assignee, \App\Notifications\TaskAssigned::class);
    }

    public function test_opt_out_in_app_suprime_el_canal_pero_mantiene_el_email(): void
    {
        Notification::fake();
        [$admin, $assignee, $project, $column] = $this->projectWithColumnAndMember();
        NotificationPreferenceFactory::new()
            ->forUser($assignee)
            ->forEvent(NotificationEvent::TaskAssigned)
            ->emailOnly()
            ->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.store', $project), [
                'column_id' => $column->id,
                'title' => 'Tarea con opt-out',
                'assignee_id' => $assignee->id,
                'priority' => 'high',
                'type' => 'feature',
            ])
            ->assertRedirect();

        Notification::assertSentTo(
            $assignee,
            \App\Notifications\TaskAssigned::class,
            function ($notification, $channels, $notifiable) {
                return ! in_array('database', $channels, true)
                    && in_array('mail', $channels, true);
            },
        );
    }
}
