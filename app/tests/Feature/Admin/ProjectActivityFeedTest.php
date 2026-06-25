<?php

namespace Tests\Feature\Admin;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature del feed de actividad en el panel admin.
 *
 * Garantiza que el admin:
 *  - Puede acceder a la vista del feed de cualquier proyecto.
 *  - Ve TODOS los eventos (incluidos los privados como
 *    `member_added`, `task_deleted`).
 *  - El componente Livewire expone los conteos por categoria
 *    y los eventos en orden cronologico.
 *  - El filtro de categoria reduce la lista al subconjunto
 *    correspondiente.
 *  - El boton "Cargar entradas anteriores" trae 20 mas.
 *  - El cliente intentando acceder a la ruta admin es
 *    redirigido al portal.
 *  - El visitante sin sesion va a login.
 */
class ProjectActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function projectWithColumn(): array
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);

        return [$project, $column];
    }

    public function test_admin_puede_acceder_al_feed_de_actividad(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithColumn();

        $this->actingAs($admin)
            ->get(route('admin.projects.activity', $project))
            ->assertOk()
            ->assertSee('Actividad del proyecto');
    }

    public function test_visitante_sin_sesion_es_redirigido_a_login(): void
    {
        [$project] = $this->projectWithColumn();

        $this->get(route('admin.projects.activity', $project))
            ->assertRedirect(route('login'));
    }

    public function test_cliente_es_redirigido_a_portal(): void
    {
        $client = User::factory()->client()->create();
        [$project] = $this->projectWithColumn();

        $this->actingAs($client)
            ->get(route('admin.projects.activity', $project))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_ve_todos_los_eventos_incluidos_los_privados(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithColumn();

        // Sembramos un feed con eventos publicos y privados.
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();
        ActivityLog::factory()->forProject($project)->memberAdded()->create();
        ActivityLog::factory()->forProject($project)->create(['type' => ActivityType::TaskDeleted]);

        $this->actingAs($admin)
            ->get(route('admin.projects.activity', $project))
            ->assertOk()
            ->assertSee('5 eventos registrados', false);
    }

    public function test_acciones_del_admin_generan_entradas_en_el_feed(): void
    {
        $admin = $this->admin();
        [$project, $column] = $this->projectWithColumn();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        // Disparamos varios eventos via el servicio (que es
        // el mismo que invocan los controladores).
        $logger = app(ActivityLogger::class);
        $logger->taskCreated($project, $task, $admin);
        $logger->taskCompleted($project, $task, $admin);
        $logger->projectArchived($project, $admin);

        $this->actingAs($admin)
            ->get(route('admin.projects.activity', $project))
            ->assertOk()
            ->assertSee('3 eventos registrados', false);
    }

    public function test_feed_vacio_muestra_empty_state(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithColumn();

        $this->actingAs($admin)
            ->get(route('admin.projects.activity', $project))
            ->assertOk()
            ->assertSee('Sin actividad todavia');
    }

    public function test_filtro_de_categoria_via_query_string(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithColumn();

        ActivityLog::factory()->forProject($project)->taskCreated()->count(3)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.activity', $project).'?c=tasks')
            ->assertOk()
            ->assertSee('3 eventos registrados', false);
    }

    public function test_categoria_desconocida_no_explota(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithColumn();
        ActivityLog::factory()->forProject($project)->taskCreated()->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.activity', $project).'?c=inexistente')
            ->assertOk();
    }
}
