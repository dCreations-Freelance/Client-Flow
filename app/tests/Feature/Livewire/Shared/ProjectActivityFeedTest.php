<?php

namespace Tests\Feature\Livewire\Shared;

use App\Enums\ActivityType;
use App\Livewire\Shared\ProjectActivityFeed;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests del componente Livewire `Shared\ProjectActivityFeed`.
 *
 * Verifican la interaccion del feed: cambios de filtro,
 * paginacion, y comportamiento admin vs portal.
 */
class ProjectActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        return Project::factory()->create();
    }

    public function test_admin_ve_todas_las_entradas_y_conteos_por_categoria(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->project();

        ActivityLog::factory()->forProject($project)->taskCreated()->count(3)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();
        ActivityLog::factory()->forProject($project)->memberAdded()->create();

        Livewire::actingAs($admin)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false])
            ->assertSet('category', 'all')
            ->assertSet('loadedCount', 20)
            ->assertSet('portalMode', false);

        // El total del admin incluye los privados.
        $component = Livewire::actingAs($admin)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false]);
        $this->assertSame(5, $component->get('total'));
        $this->assertSame(3, $component->get('counts')['tasks']);
        $this->assertSame(1, $component->get('counts')['messages']);
        $this->assertSame(1, $component->get('counts')['members']);
    }

    public function test_cliente_solo_ve_eventos_publicos(): void
    {
        $client = User::factory()->client()->create();
        $project = $this->project();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        // Publico
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        // Privado
        ActivityLog::factory()->forProject($project)->memberAdded()->create();
        ActivityLog::factory()->forProject($project)->create(['type' => ActivityType::TaskDeleted]);

        $component = Livewire::actingAs($client)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => true]);

        $this->assertSame(2, $component->get('total'));
        $this->assertSame(0, $component->get('counts')['members']);
    }

    public function test_set_category_cambia_filtro_y_resetea_paginacion(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->project();

        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();

        Livewire::actingAs($admin)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false])
            // Cargamos mas para que el reset sea visible.
            ->call('loadMore')
            ->assertSet('loadedCount', 40)
            // Cambiamos a la categoria "messages".
            ->call('setCategory', 'messages')
            ->assertSet('category', 'messages')
            // El reset pone loadedCount a 20 (DEFAULT_PAGE_SIZE).
            ->assertSet('loadedCount', 20);
    }

    public function test_set_category_con_valor_invalido_cae_a_all(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->project();

        Livewire::actingAs($admin)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false])
            ->call('setCategory', 'cualquier-cosa')
            ->assertSet('category', 'all');
    }

    public function test_load_more_incrementa_loaded_count_en_20(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->project();
        ActivityLog::factory()->forProject($project)->count(25)->create();

        Livewire::actingAs($admin)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false])
            ->assertSet('loadedCount', 20)
            ->call('loadMore')
            ->assertSet('loadedCount', 40);
    }

    public function test_url_query_string_persiste_la_categoria(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->project();

        // Componente sin query string: categoria por defecto.
        Livewire::actingAs($admin)
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false])
            ->assertSet('category', 'all');

        // Con query string, Livewire la aplica al montar.
        Livewire::actingAs($admin)
            ->withQueryParams(['c' => 'tasks'])
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false])
            ->assertSet('category', 'tasks');
    }

    public function test_categoria_desconocida_no_aplica_filtro_y_devuelve_query_vacia(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->project();
        ActivityLog::factory()->forProject($project)->count(3)->create();

        $component = Livewire::actingAs($admin)
            ->withQueryParams(['c' => 'inexistente'])
            ->test(ProjectActivityFeed::class, ['project' => $project, 'portalMode' => false]);

        // La categoria se acepta literalmente, pero el scope
        // `inCategory` devuelve query vacia para categoria
        // desconocida (defensa contra manipulacion de URL).
        $this->assertSame(0, $component->get('total'));
    }

    public function test_cliente_no_puede_montar_componente_para_proyecto_ajeno(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();

        Livewire::actingAs($client)
            ->test(ProjectActivityFeed::class, ['project' => $otherProject, 'portalMode' => true])
            ->assertForbidden();
    }
}
