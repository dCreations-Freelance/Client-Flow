<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\TimeTracking\TimeTracker;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests feature del modulo de registro de tiempo en el
 * panel admin.
 *
 * Cubre:
 * - Render del dashboard y de la vista de detalle de tarea.
 * - CRUD HTTP de entradas manuales (create/update/delete).
 * - Componente Livewire `TimeTracker`: start/stop del
 *   cronometro con auto-stop del timer anterior, alta de
 *   entrada manual, edicion, borrado y toggle de facturable.
 * - Sincronizacion automatica de la cache
 *   `tasks.total_logged_minutes`.
 * - Aislamiento entre proyectos: una entrada solo se
 *   manipula desde la URL que coincide con su proyecto y
 *   tarea.
 * - Rechazo a clientes (redirect al portal).
 * - Exportacion CSV del dashboard.
 */
class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function adminAndProject(): array
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

    // -----------------------------------------------------------------
    // Rutas y autorizacion
    // -----------------------------------------------------------------

    public function test_admin_puede_ver_dashboard_de_tiempo(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->get(route('admin.projects.time.index', $project))
            ->assertOk();
    }

    public function test_cliente_no_puede_acceder_al_dashboard_admin(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('admin.projects.time.index', $project))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_puede_ver_detalle_de_tarea_con_componente_time_tracker(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        $this->actingAs($admin)
            ->get(route('admin.projects.tasks.show', [$project, $task]))
            ->assertOk()
            ->assertSeeLivewire(TimeTracker::class);
    }

    // -----------------------------------------------------------------
    // CRUD HTTP de entradas manuales
    // -----------------------------------------------------------------

    public function test_admin_puede_crear_entrada_manual_via_http(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.time-entries.store', [$project, $task]), [
                'description' => 'Refactor del modulo',
                'minutes' => 45,
                'type' => 'manual',
                'entry_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'description' => 'Refactor del modulo',
            'minutes' => 45,
            'type' => 'manual',
        ]);
        $this->assertSame(45, $task->fresh()->total_logged_minutes);
    }

    public function test_validacion_rechaza_minutos_negativos(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.time-entries.store', [$project, $task]), [
                'description' => 'X',
                'minutes' => 0,
                'type' => 'manual',
                'entry_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('minutes');
    }

    public function test_validacion_rechaza_entry_date_futura(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.time-entries.store', [$project, $task]), [
                'description' => 'X',
                'minutes' => 30,
                'type' => 'manual',
                'entry_date' => now()->addDays(2)->toDateString(),
            ])
            ->assertSessionHasErrors('entry_date');
    }

    public function test_cliente_no_puede_crear_entrada_via_http(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $this->actingAs($client)
            ->post(route('admin.projects.tasks.time-entries.store', [$project, $task]), [
                'minutes' => 30,
                'type' => 'manual',
                'entry_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_admin_puede_editar_una_entrada(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        $entry = TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 30,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.projects.tasks.time-entries.update', [$project, $task, $entry]), [
                'description' => 'Detalle nuevo',
                'minutes' => 90,
                'billed' => true,
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame('Detalle nuevo', $entry->description);
        $this->assertSame(90, $entry->minutes);
        $this->assertTrue($entry->billed);
        $this->assertSame(90, $task->fresh()->total_logged_minutes);
    }

    public function test_admin_puede_eliminar_una_entrada_y_cache_se_recalcula(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        $entry = TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 60,
        ]);
        $this->assertSame(60, $task->fresh()->total_logged_minutes);

        $this->actingAs($admin)
            ->delete(route('admin.projects.tasks.time-entries.destroy', [$project, $task, $entry]))
            ->assertRedirect();

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
        $this->assertSame(0, $task->fresh()->total_logged_minutes);
    }

    public function test_cliente_no_puede_editar_ni_eliminar_entradas(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $entry = TimeEntry::factory()->forTask($task)->fromUser($client)->create([
            'project_id' => $project->id,
        ]);

        $this->actingAs($client)
            ->put(route('admin.projects.tasks.time-entries.update', [$project, $task, $entry]), [
                'minutes' => 99,
            ])
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->delete(route('admin.projects.tasks.time-entries.destroy', [$project, $task, $entry]))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertSame($entry->minutes, $entry->fresh()->minutes);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_no_se_puede_manipular_una_entrada_de_otra_tarea_o_proyecto(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        $otherColumn = BoardColumn::factory()->create(['project_id' => $project->id]);
        $otherTask = Task::factory()->create(['project_id' => $project->id, 'column_id' => $otherColumn->id]);
        $entry = TimeEntry::factory()->forTask($otherTask)->fromUser($admin)->create([
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.tasks.time-entries.destroy', [$project, $task, $entry]))
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // Livewire TimeTracker: cronometro
    // -----------------------------------------------------------------

    public function test_admin_puede_arrancar_cronometro_via_livewire(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->assertSet('activeTimerId', null)
            ->call('startTimer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('time_entries', [
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'type' => 'timer',
            'minutes' => 0,
        ]);
    }

    public function test_arrancar_un_timer_cierra_el_anterior_del_mismo_usuario(): void
    {
        [$admin, $project] = $this->adminAndProject();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $taskA = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $taskB = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        $service = app(TimeTrackingService::class);
        $oldTimer = $service->startTimer($taskA, $admin);
        // Forzamos minutos para simular que ya llevaba tiempo.
        $oldTimer->update(['minutes' => 7, 'started_at' => now()->subMinutes(7)]);

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $taskB])
            ->call('startTimer')
            ->assertHasNoErrors();

        $oldTimer->refresh();
        $this->assertSame(7, $oldTimer->minutes, 'El timer anterior mantiene sus minutos.');

        $this->assertSame(1, TimeEntry::where('user_id', $admin->id)->where('minutes', 0)->count());
    }

    public function test_admin_puede_parar_cronometro_y_se_persisten_los_minutos(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        $service = app(TimeTrackingService::class);
        $service->startTimer($task, $admin);
        // Adelantamos el reloj para simular 25 minutos de actividad.
        TimeEntry::query()
            ->where('user_id', $admin->id)
            ->update(['started_at' => now()->subMinutes(25)]);

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('stopTimer')
            ->assertHasNoErrors();

        $entry = TimeEntry::where('user_id', $admin->id)->where('type', 'timer')->first();
        $this->assertNotNull($entry);
        $this->assertSame(25, $entry->minutes);
        $this->assertSame(25, $task->fresh()->total_logged_minutes);
    }

    public function test_cliente_no_puede_arrancar_cronometro(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        Livewire::actingAs($client)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('startTimer')
            ->assertForbidden();

        $this->assertDatabaseCount('time_entries', 0);
    }

    // -----------------------------------------------------------------
    // Livewire TimeTracker: entradas manuales
    // -----------------------------------------------------------------

    public function test_admin_puede_anadir_entrada_manual_via_livewire(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('openManualEntryForm')
            ->set('entryForm.description', 'Revision de PRs')
            ->set('entryForm.minutes', 30)
            ->set('entryForm.billed', true)
            ->call('saveManualEntry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('time_entries', [
            'task_id' => $task->id,
            'description' => 'Revision de PRs',
            'minutes' => 30,
            'billed' => true,
        ]);
        $this->assertSame(30, $task->fresh()->total_logged_minutes);
    }

    public function test_validacion_livewire_rechaza_minutos_vacios(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('openManualEntryForm')
            ->set('entryForm.minutes', null)
            ->call('saveManualEntry')
            ->assertHasErrors(['entryForm.minutes']);
    }

    public function test_admin_puede_editar_una_entrada_desde_el_tracker(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        $entry = TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 30,
            'description' => 'Original',
        ]);

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('openEditForm', $entry->id)
            ->set('entryForm.description', 'Editado')
            ->set('entryForm.minutes', 60)
            ->call('saveManualEntry')
            ->assertHasNoErrors();

        $entry->refresh();
        $this->assertSame('Editado', $entry->description);
        $this->assertSame(60, $entry->minutes);
    }

    public function test_admin_puede_eliminar_una_entrada_desde_el_tracker(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        $entry = TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 60,
        ]);

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('deleteEntry', $entry->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
        $this->assertSame(0, $task->fresh()->total_logged_minutes);
    }

    public function test_admin_puede_toggle_facturable_desde_el_tracker(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        $entry = TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'billed' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(TimeTracker::class, ['project' => $project, 'task' => $task])
            ->call('toggleBilled', $entry->id)
            ->assertHasNoErrors();

        $this->assertTrue($entry->fresh()->billed);
    }

    // -----------------------------------------------------------------
    // Exportacion CSV
    // -----------------------------------------------------------------

    public function test_admin_puede_exportar_csv_de_horas(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        TimeEntry::factory()->count(2)->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 30,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.time.export', $project))
            ->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Fecha;Tarea;Persona;Minutos', $content);
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringContainsString('60', $content);
    }

    public function test_export_csv_aplica_filtros_de_query_string(): void
    {
        [$admin, $project, $task] = $this->adminAndProject();
        TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 30,
            'billed' => true,
        ]);
        TimeEntry::factory()->forTask($task)->fromUser($admin)->create([
            'project_id' => $project->id,
            'minutes' => 60,
            'billed' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.time.export', $project).'?billable=1');

        $content = $response->streamedContent();
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringContainsString('30', $content);
        $this->assertStringNotContainsString('60', $content);
    }
}
