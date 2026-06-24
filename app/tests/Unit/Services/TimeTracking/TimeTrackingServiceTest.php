<?php

namespace Tests\Unit\Services\TimeTracking;

use App\Enums\TimeEntryType;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Tests del `TimeTrackingService`: creacion de entradas
 * manuales, arranque y parada del cronometro (con la
 * regla de auto-stop del timer anterior), consulta del
 * timer activo y agregados para el dashboard.
 */
class TimeTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimeTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimeTrackingService::class);
    }

    private function projectWithTask(): array
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        return [$project, $task];
    }

    public function test_create_manual_entry_persiste_con_tipo_manual_y_sin_started_at(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $entry = $this->service->createManualEntry($task, $user, [
            'description' => 'Refactor del modulo de login',
            'minutes' => 45,
            'type' => TimeEntryType::Manual->value,
            'entry_date' => now()->toDateString(),
        ]);

        $this->assertSame($task->id, $entry->task_id);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($task->project_id, $entry->project_id);
        $this->assertSame('Refactor del modulo de login', $entry->description);
        $this->assertSame(45, $entry->minutes);
        $this->assertSame(TimeEntryType::Manual, $entry->type);
        $this->assertNull($entry->started_at);
    }

    public function test_create_manual_entry_normaliza_descripcion_vacia_a_null(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $entry = $this->service->createManualEntry($task, $user, [
            'description' => '   ',
            'minutes' => 15,
            'type' => TimeEntryType::Manual->value,
            'entry_date' => now()->toDateString(),
        ]);

        $this->assertNull($entry->description);
    }

    public function test_create_manual_entry_rechaza_minutos_invalidos(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->service->createManualEntry($task, $user, [
            'minutes' => 0,
            'type' => TimeEntryType::Manual->value,
            'entry_date' => now()->toDateString(),
        ]);
    }

    public function test_create_manual_entry_actualiza_cache_de_la_tarea(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $this->service->createManualEntry($task, $user, [
            'minutes' => 60,
            'type' => TimeEntryType::Manual->value,
            'entry_date' => now()->toDateString(),
        ]);

        $this->assertSame(60, $task->fresh()->total_logged_minutes);
    }

    public function test_update_entry_persiste_cambios_y_recalcula_cache_si_minutos_cambian(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $entry = $this->service->createManualEntry($task, $user, [
            'minutes' => 30,
            'type' => TimeEntryType::Manual->value,
            'entry_date' => now()->toDateString(),
        ]);

        $this->service->updateEntry($entry, [
            'description' => 'Detalle adicional',
            'minutes' => 75,
            'billed' => true,
        ]);

        $entry->refresh();
        $this->assertSame('Detalle adicional', $entry->description);
        $this->assertSame(75, $entry->minutes);
        $this->assertTrue($entry->billed);
        $this->assertSame(75, $task->fresh()->total_logged_minutes);
    }

    public function test_update_entry_sin_cambios_no_persiste_nada(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();
        $entry = $this->service->createManualEntry($task, $user, [
            'description' => 'Original',
            'minutes' => 30,
            'type' => TimeEntryType::Manual->value,
            'entry_date' => now()->toDateString(),
        ]);

        $originalDescription = $entry->description;
        $originalMinutes = $entry->minutes;

        $this->service->updateEntry($entry, []);

        $entry->refresh();
        $this->assertSame($originalDescription, $entry->description);
        $this->assertSame($originalMinutes, $entry->minutes);
    }

    public function test_start_timer_crea_entrada_con_minutes_cero_y_started_at(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $entry = $this->service->startTimer($task, $user);

        $this->assertSame($task->id, $entry->task_id);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame(TimeEntryType::Timer, $entry->type);
        $this->assertSame(0, $entry->minutes);
        $this->assertNotNull($entry->started_at);
    }

    public function test_start_timer_cierra_el_timer_anterior_del_mismo_usuario(): void
    {
        [, $taskA] = $this->projectWithTask();
        $taskB = Task::factory()->create(['project_id' => $taskA->project_id]);
        $user = User::factory()->create();

        $first = $this->service->startTimer($taskA, $user);

        // Forzamos que el primer timer tenga minutos
        // simulados ya calculados para verificar que se
        // conservan tras el auto-stop.
        $first->update(['minutes' => 5, 'started_at' => now()->subMinutes(5)]);

        $second = $this->service->startTimer($taskB, $user);

        $first->refresh();
        $this->assertSame(5, $first->minutes, 'El timer anterior mantiene sus minutos.');
        $this->assertNotNull($first->started_at);
        $this->assertSame($taskB->id, $second->task_id);
        $this->assertSame(0, $second->minutes);
        $this->assertSame(1, TimeEntry::where('user_id', $user->id)->where('minutes', 0)->count());
    }

    public function test_stop_timer_devuelve_null_si_no_hay_timer_activo(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->stopTimer($user));
    }

    public function test_stop_timer_calcula_minutos_y_persiste(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $this->service->startTimer($task, $user);

        // Adelantamos el reloj del timer a 30 minutos en el pasado.
        TimeEntry::query()
            ->where('user_id', $user->id)
            ->where('minutes', 0)
            ->update(['started_at' => now()->subMinutes(30)]);

        $closed = $this->service->stopTimer($user);

        $this->assertNotNull($closed);
        $this->assertSame(30, $closed->minutes);
    }

    public function test_stop_timer_aplica_suelo_de_1_minuto_para_timers_muy_cortos(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $this->service->startTimer($task, $user);
        // Timer que lleva "5 segundos" en curso.
        TimeEntry::query()
            ->where('user_id', $user->id)
            ->update(['started_at' => now()->subSeconds(5)]);

        $closed = $this->service->stopTimer($user);
        $this->assertSame(1, $closed->minutes);
    }

    public function test_get_active_timer_devuelve_el_timer_en_curso(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $this->assertNull($this->service->getActiveTimer($user));

        $this->service->startTimer($task, $user);
        $active = $this->service->getActiveTimer($user);
        $this->assertNotNull($active);
        $this->assertSame($task->id, $active->task_id);
        $this->assertSame(0, $active->minutes);
    }

    public function test_get_active_timer_devuelve_null_despues_de_stop(): void
    {
        [, $task] = $this->projectWithTask();
        $user = User::factory()->create();

        $this->service->startTimer($task, $user);
        $this->service->stopTimer($user);

        $this->assertNull($this->service->getActiveTimer($user));
    }

    public function test_get_project_summary_devuelve_agregados_correctos(): void
    {
        [$project, $task] = $this->projectWithTask();
        $u1 = User::factory()->create(['name' => 'Ana']);
        $u2 = User::factory()->create(['name' => 'Bea']);

        TimeEntry::factory()->forTask($task)->fromUser($u1)->create(['project_id' => $project->id, 'minutes' => 60]);
        TimeEntry::factory()->forTask($task)->fromUser($u1)->create(['project_id' => $project->id, 'minutes' => 30, 'billed' => true]);
        TimeEntry::factory()->forTask($task)->fromUser($u2)->create(['project_id' => $project->id, 'minutes' => 45]);

        $summary = $this->service->getProjectSummary($project);

        $this->assertSame(135, $summary['total_minutes']);
        $this->assertSame(3, $summary['total_entries']);
        $this->assertSame(30, $summary['billable_minutes']);
        $this->assertSame(105, $summary['not_billable_minutes']);
        $this->assertCount(2, $summary['by_member']);
        $this->assertSame('Ana', $summary['by_member'][0]['name']);
        $this->assertSame(90, $summary['by_member'][0]['minutes']);
        $this->assertCount(1, $summary['by_task']);
        $this->assertSame(135, $summary['by_task'][0]['minutes']);
    }

    public function test_get_project_summary_aplica_filtro_por_billable(): void
    {
        [$project, $task] = $this->projectWithTask();
        TimeEntry::factory()->forTask($task)->create(['project_id' => $project->id, 'minutes' => 60, 'billed' => true]);
        TimeEntry::factory()->forTask($task)->create(['project_id' => $project->id, 'minutes' => 30, 'billed' => false]);

        $billable = $this->service->getProjectSummary($project, null, null, true);
        $this->assertSame(60, $billable['total_minutes']);

        $notBillable = $this->service->getProjectSummary($project, null, null, false);
        $this->assertSame(30, $notBillable['total_minutes']);
    }

    public function test_get_project_summary_aplica_filtro_por_rango_de_fechas(): void
    {
        [$project, $task] = $this->projectWithTask();
        TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 60,
            'created_at' => now()->subDays(20),
        ]);
        TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 30,
            'created_at' => now()->subDays(2),
        ]);

        $from = Carbon::now()->subDays(5);
        $to = Carbon::now();

        $summary = $this->service->getProjectSummary($project, $from, $to);
        $this->assertSame(30, $summary['total_minutes']);
    }

    public function test_get_project_summary_vacio_devuelve_ceros(): void
    {
        [$project] = $this->projectWithTask();

        $summary = $this->service->getProjectSummary($project);

        $this->assertSame(0, $summary['total_minutes']);
        $this->assertSame(0, $summary['total_entries']);
        $this->assertSame([], $summary['by_member']);
        $this->assertSame([], $summary['by_task']);
    }
}
