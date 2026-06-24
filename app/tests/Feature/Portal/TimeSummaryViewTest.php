<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\TimeTracking\ProjectTimeSummary;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests feature de la vista de resumen de tiempo en
 * el portal del cliente.
 *
 * Cubre:
 * - Render correcto de la pagina de resumen.
 * - Aislamiento: solo clientes miembros de la org
 *   del proyecto pueden verla; el resto recibe
 *   403 (politica `view`), mismo patron que el
 *   resto de modulos del portal.
 * - Privacidad: NO se muestran descripciones
 *   individuales ni el desglose por tarea, solo
 *   el total agregado y el breakdown por miembro.
 * - Las rutas de escritura del modulo de tiempo
 *   no existen en el grupo `portal.*`.
 */
class TimeSummaryViewTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(): array
    {
        $client = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        return [$client, $project, $admin];
    }

    public function test_cliente_puede_ver_el_resumen_de_tiempo_de_su_proyecto(): void
    {
        [$client, $project] = $this->clientAndProject();

        $this->actingAs($client)
            ->get(route('portal.projects.time.index', $project))
            ->assertOk()
            ->assertSeeLivewire(ProjectTimeSummary::class);
    }

    public function test_cliente_no_puede_ver_el_resumen_de_un_proyecto_ajeno(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('portal.projects.time.index', $otherProject))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_ver_resumen_de_proyecto_archivado(): void
    {
        [$client] = $this->clientAndProject();
        $archivedProject = Project::factory()->archived()->create();
        $archivedProject->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.time.index', $archivedProject))
            ->assertForbidden();
    }

    public function test_resumen_muestra_total_y_breakdown_por_miembro(): void
    {
        [$client, $project, $admin] = $this->clientAndProject();
        $other = User::factory()->create(['name' => 'Beatriz']);
        $project->organization->members()->attach($other->id, ['role' => 'member']);

        // 60 min de admin + 30 min de cliente + 90 min de beatriz
        // = 180 min totales, 3 personas con tiempo.
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'minutes' => 60,
        ]);
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'minutes' => 30,
        ]);
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $other->id,
            'minutes' => 90,
        ]);

        // El feature test solo renderiza el shell del
        // componente; para verificar los datos tenemos
        // que ejercitar el componente Livewire
        // directamente.
        Livewire::actingAs($client)
            ->test(ProjectTimeSummary::class, ['project' => $project])
            ->assertSet('summary.total_minutes', 180)
            ->assertSet('summary.total_entries', 3)
            ->assertCount('summary.by_member', 3);
    }

    public function test_resumen_no_muestra_descripciones_individuales(): void
    {
        [$client, $project, $admin] = $this->clientAndProject();

        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'description' => 'Refactor del modulo de autenticacion JWT',
            'minutes' => 30,
        ]);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.time.index', $project))
            ->assertOk();

        // La descripcion interna NO debe aparecer en la
        // vista: es informacion que el equipo prefiere
        // mantener privada frente al cliente.
        $this->assertStringNotContainsString(
            'Refactor del modulo de autenticacion JWT',
            (string) $response->getContent(),
        );
    }

    public function test_resumen_no_muestra_desglose_por_tarea(): void
    {
        [$client, $project, $admin] = $this->clientAndProject();
        \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = \App\Models\Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Implementar checkout con Stripe',
        ]);

        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'minutes' => 60,
        ]);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.time.index', $project))
            ->assertOk();

        // El titulo interno de la tarea NO debe aparecer:
        // la vista de resumen es solo por persona.
        $this->assertStringNotContainsString(
            'Implementar checkout con Stripe',
            (string) $response->getContent(),
        );
    }

    public function test_livewire_resumen_aplica_filtro_de_fechas(): void
    {
        [$client, $project, $admin] = $this->clientAndProject();

        // Entrada vieja: 60 min hace 60 dias (fuera del
        // filtro por defecto de "ultimo mes").
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'minutes' => 60,
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(60),
        ]);
        // Entrada reciente: 30 min hoy (dentro del filtro).
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'minutes' => 30,
        ]);

        // Por defecto: solo el ultimo mes. Total = 30 min.
        Livewire::actingAs($client)
            ->test(ProjectTimeSummary::class, ['project' => $project])
            ->assertSet('summary.total_minutes', 30);

        // Filtro ampliado: ultimos 90 dias. Entran ambas.
        Livewire::actingAs($client)
            ->test(ProjectTimeSummary::class, ['project' => $project])
            ->set('fromDate', now()->subDays(90)->toDateString())
            ->set('toDate', now()->toDateString())
            ->assertSet('summary.total_minutes', 90);
    }

    public function test_no_existen_rutas_de_escritura_de_tiempo_en_el_portal(): void
    {
        // Verificamos directamente en la tabla de
        // rutas: las acciones de escritura de
        // `time_entries` solo deben estar registradas
        // bajo el prefijo `admin.*`.
        $timeWriteRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_contains((string) $route->getName(), 'time-entries'))
            ->map(fn ($route) => (string) $route->getName())
            ->all();

        foreach ($timeWriteRoutes as $name) {
            $this->assertStringStartsWith('admin.', $name, "La ruta {$name} no deberia existir en el portal.");
        }
    }
}
