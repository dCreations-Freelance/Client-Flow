<?php

namespace Tests\Unit\Models;

use App\Enums\TimeEntryType;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del modelo `TimeEntry`: fillable, casts, accesors
 * (`displayMinutes`, `hours`, `liveElapsedSeconds`),
 * scopes (`forProject`, `forUser`, `forTask`, `billable`,
 * `notBillable`, `manual`, `timer`, `inDateRange`,
 * `recent`), helpers (`isManual`, `isTimer`, `isBillable`,
 * `markAsBilled`, `markAsUnbilled`) y la sincronizacion
 * automatica de la cache `tasks.total_logged_minutes`.
 */
class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithTaskAndUser(): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        return [$admin, $project, $task];
    }

    public function test_factory_crea_entrada_con_datos_basicos(): void
    {
        $entry = TimeEntry::factory()->create();

        $this->assertNotNull($entry->id);
        $this->assertSame(TimeEntryType::Manual, $entry->type);
        $this->assertNull($entry->started_at);
        $this->assertFalse($entry->billed);
        $this->assertGreaterThan(0, $entry->minutes);
    }

    public function test_minutes_y_billed_se_castean_a_integer_y_boolean(): void
    {
        $entry = TimeEntry::factory()->create([
            'minutes' => '120',
            'billed' => 1,
        ]);

        $this->assertSame(120, $entry->minutes);
        $this->assertTrue($entry->billed);
    }

    public function test_type_se_castea_a_enum(): void
    {
        $entry = TimeEntry::factory()->timer()->create();

        $this->assertInstanceOf(TimeEntryType::class, $entry->type);
        $this->assertSame(TimeEntryType::Timer, $entry->type);
    }

    public function test_started_at_se_castea_a_datetime(): void
    {
        $entry = TimeEntry::factory()->timer()->create();

        $this->assertNotNull($entry->started_at);
    }

    public function test_display_minutes_formatea_en_horas_y_minutos(): void
    {
        $entry = TimeEntry::factory()->minutes(30)->make();
        $this->assertSame('30m', $entry->display_minutes);

        $entry->minutes = 60;
        $this->assertSame('1h', $entry->display_minutes);

        $entry->minutes = 90;
        $this->assertSame('1h 30m', $entry->display_minutes);

        $entry->minutes = 125;
        $this->assertSame('2h 05m', $entry->display_minutes);
    }

    public function test_hours_devuelve_minutos_en_horas_decimales(): void
    {
        $entry = TimeEntry::factory()->minutes(90)->make();
        $this->assertSame(1.5, $entry->hours);

        $entry->minutes = 30;
        $this->assertSame(0.5, $entry->hours);
    }

    public function test_live_elapsed_seconds_devuelve_0_para_entradas_manuales(): void
    {
        $entry = TimeEntry::factory()->create(['minutes' => 45]);
        $this->assertSame(0, $entry->liveElapsedSeconds());
    }

    public function test_live_elapsed_seconds_calcula_diferencia_con_started_at(): void
    {
        $entry = TimeEntry::factory()->timer()->create([
            'started_at' => now()->subSeconds(120),
            'minutes' => 0,
        ]);

        $this->assertGreaterThanOrEqual(119, $entry->liveElapsedSeconds());
        $this->assertLessThanOrEqual(121, $entry->liveElapsedSeconds());
    }

    public function test_helpers_de_tipo(): void
    {
        $manual = TimeEntry::factory()->create();
        $timer = TimeEntry::factory()->timer()->create();

        $this->assertTrue($manual->isManual());
        $this->assertFalse($manual->isTimer());

        $this->assertTrue($timer->isTimer());
        $this->assertFalse($timer->isManual());
    }

    public function test_is_billable_y_mark_as_billed_son_idempotentes(): void
    {
        $entry = TimeEntry::factory()->create();

        $this->assertFalse($entry->isBillable());

        $entry->markAsBilled();
        $this->assertTrue($entry->fresh()->isBillable());

        // Idempotente: marcar de nuevo no duplica notificaciones
        // ni dispara observers raros.
        $entry->markAsBilled();
        $this->assertTrue($entry->fresh()->isBillable());

        $entry->markAsUnbilled();
        $this->assertFalse($entry->fresh()->isBillable());
    }

    public function test_scope_for_project_filtra_por_proyecto(): void
    {
        [$admin, $project, $task] = $this->projectWithTaskAndUser();
        TimeEntry::factory()->count(2)->forTask($task)->create(['project_id' => $project->id]);

        $otherProject = Project::factory()->create();
        $otherColumn = BoardColumn::factory()->create(['project_id' => $otherProject->id]);
        $otherTask = Task::factory()->create(['project_id' => $otherProject->id, 'column_id' => $otherColumn->id]);
        TimeEntry::factory()->count(3)->forTask($otherTask)->create(['project_id' => $otherProject->id]);

        $this->assertCount(2, TimeEntry::forProject($project->id)->get());
        $this->assertCount(3, TimeEntry::forProject($otherProject->id)->get());
    }

    public function test_scope_for_user_filtra_por_autor(): void
    {
        [, , $task] = $this->projectWithTaskAndUser();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        TimeEntry::factory()->count(2)->forTask($task)->fromUser($u1)->create();
        TimeEntry::factory()->count(3)->forTask($task)->fromUser($u2)->create();

        $this->assertCount(2, TimeEntry::forUser($u1->id)->get());
        $this->assertCount(3, TimeEntry::forUser($u2->id)->get());
    }

    public function test_scope_billable_filtra_por_facturable(): void
    {
        TimeEntry::factory()->count(2)->billable()->create();
        TimeEntry::factory()->count(3)->create(['billed' => false]);

        $this->assertCount(2, TimeEntry::billable()->get());
        $this->assertCount(3, TimeEntry::notBillable()->get());
    }

    public function test_scope_manual_y_timer_separan_por_tipo(): void
    {
        TimeEntry::factory()->count(2)->create();
        TimeEntry::factory()->count(3)->timer()->create();

        $this->assertCount(2, TimeEntry::manual()->get());
        $this->assertCount(3, TimeEntry::timer()->get());
    }

    public function test_scope_in_date_range_aplica_limites(): void
    {
        TimeEntry::factory()->daysAgo(20)->create();
        TimeEntry::factory()->daysAgo(10)->create();
        TimeEntry::factory()->today()->create();

        // Sin `to`: todo lo posterior o igual a hace 7 dias.
        $this->assertCount(1, TimeEntry::inDateRange(now()->subDays(7), null)->get());
        // Rango cerrado: ultimos 7 dias incluyendo hoy.
        $this->assertCount(1, TimeEntry::inDateRange(now()->subDays(7), now())->get());
        // Rango mas amplio: ultimos 15 dias.
        $this->assertCount(2, TimeEntry::inDateRange(now()->subDays(15), now())->get());
        // Sin limites: todas.
        $this->assertCount(3, TimeEntry::inDateRange(null, null)->get());
    }

    public function test_scope_recent_ordena_por_mas_reciente(): void
    {
        $old = TimeEntry::factory()->daysAgo(5)->create();
        $new = TimeEntry::factory()->today()->create();

        $ordered = TimeEntry::query()->recent()->get();
        $this->assertSame($new->id, $ordered->first()->id);
        $this->assertSame($old->id, $ordered->last()->id);
    }

    public function test_relaciones_con_task_user_y_project(): void
    {
        [, $project, $task] = $this->projectWithTaskAndUser();
        $user = User::factory()->create();

        $entry = TimeEntry::factory()->forTask($task)->fromUser($user)->create([
            'project_id' => $project->id,
        ]);

        $this->assertSame($task->id, $entry->task->id);
        $this->assertSame($user->id, $entry->user->id);
        $this->assertSame($project->id, $entry->project->id);
    }

    public function test_crear_entrada_actualiza_cache_de_total_logged_minutes(): void
    {
        [, $project, $task] = $this->projectWithTaskAndUser();

        $this->assertSame(0, $task->fresh()->total_logged_minutes);

        TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 30,
        ]);
        $this->assertSame(30, $task->fresh()->total_logged_minutes);

        TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 45,
        ]);
        $this->assertSame(75, $task->fresh()->total_logged_minutes);
    }

    public function test_eliminar_entrada_decrementa_cache_de_total_logged_minutes(): void
    {
        [, $project, $task] = $this->projectWithTaskAndUser();
        $entry = TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 60,
        ]);

        $this->assertSame(60, $task->fresh()->total_logged_minutes);

        $entry->delete();
        $this->assertSame(0, $task->fresh()->total_logged_minutes);
    }

    public function test_actualizar_minutos_recalcula_cache(): void
    {
        [, $project, $task] = $this->projectWithTaskAndUser();
        $entry = TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 30,
        ]);

        $entry->update(['minutes' => 90]);
        $this->assertSame(90, $task->fresh()->total_logged_minutes);
    }

    public function test_actualizar_billed_no_recalcula_cache(): void
    {
        [, $project, $task] = $this->projectWithTaskAndUser();
        TimeEntry::factory()->forTask($task)->create([
            'project_id' => $project->id,
            'minutes' => 30,
        ]);

        // El observer de `updated` solo recalcula cuando
        // cambian `task_id` o `minutes`. Verificamos
        // que el campo `billed` se persiste y la cache
        // permanece inalterada.
        $entry = $task->timeEntries()->first();
        $entry->update(['billed' => true]);
        $this->assertSame(30, $task->fresh()->total_logged_minutes);
        $this->assertTrue($entry->fresh()->billed);
    }
}
