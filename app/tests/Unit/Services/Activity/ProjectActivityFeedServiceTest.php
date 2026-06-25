<?php

namespace Tests\Unit\Services\Activity;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Services\Activity\ProjectActivityFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del servicio `ProjectActivityFeedService`.
 *
 * Cubre los dos metodos publicos:
 *  - `countsByCategory`: conteo por categoria con la suma
 *    de "all". El modo portal filtra eventos privados.
 *  - `load`: query con filtro de categoria + modo +
 *    paginacion keyset (`beforeId`).
 *  - `totalCount`: cuenta sin limite de paginacion.
 */
class ProjectActivityFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ProjectActivityFeedService
    {
        return app(ProjectActivityFeedService::class);
    }

    public function test_counts_by_category_devuelve_conteos_por_categoria(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->taskCompleted()->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();
        ActivityLog::factory()->forProject($project)->memberAdded()->create();

        $counts = $this->service()->countsByCategory($project, portalMode: false);

        $this->assertSame(3, $counts['tasks']);
        $this->assertSame(1, $counts['messages']);
        $this->assertSame(0, $counts['documents']);
        $this->assertSame(0, $counts['events']);
        $this->assertSame(0, $counts['project']);
        $this->assertSame(1, $counts['members']);
    }

    public function test_counts_all_es_suma_de_las_demas_categorias(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();

        $counts = $this->service()->countsByCategory($project, portalMode: false);

        $this->assertSame(3, $counts['all']);
    }

    public function test_counts_en_modo_portal_excluye_eventos_privados(): void
    {
        $project = Project::factory()->create();
        // Publico
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();
        // Privado
        ActivityLog::factory()->forProject($project)->memberAdded()->count(3)->create();
        ActivityLog::factory()->forProject($project)->create(['type' => ActivityType::TaskDeleted]);

        $counts = $this->service()->countsByCategory($project, portalMode: true);

        $this->assertSame(2, $counts['tasks']);
        $this->assertSame(1, $counts['messages']);
        $this->assertSame(0, $counts['members']);
        $this->assertSame(3, $counts['all']);
    }

    public function test_load_devuelve_entradas_orden_cronologico_descendente(): void
    {
        $project = Project::factory()->create();
        $a = ActivityLog::factory()->forProject($project)->create();
        $b = ActivityLog::factory()->forProject($project)->create();
        $c = ActivityLog::factory()->forProject($project)->create();

        $entries = $this->service()->load($project, portalMode: false, category: 'all', limit: 20);

        $this->assertSame([$c->id, $b->id, $a->id], $entries->pluck('id')->all());
    }

    public function test_load_con_limite_devuelve_solo_n_entradas(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->count(5)->create();

        $entries = $this->service()->load($project, portalMode: false, category: 'all', limit: 2);

        $this->assertCount(2, $entries);
    }

    public function test_load_con_categoria_filtra_los_tipos_de_esa_categoria(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();

        $entries = $this->service()->load($project, portalMode: false, category: 'tasks', limit: 20);

        $this->assertCount(2, $entries);
        $this->assertSame('task_created', $entries->first()->type->value);
    }

    public function test_load_en_modo_portal_excluye_eventos_privados(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->taskCreated()->create();
        ActivityLog::factory()->forProject($project)->memberAdded()->create();

        $entries = $this->service()->load($project, portalMode: true, category: 'all', limit: 20);

        $this->assertCount(1, $entries);
        $this->assertSame('task_created', $entries->first()->type->value);
    }

    public function test_load_con_before_id_pagina_hacia_atras(): void
    {
        $project = Project::factory()->create();
        $a = ActivityLog::factory()->forProject($project)->create();
        $b = ActivityLog::factory()->forProject($project)->create();
        $c = ActivityLog::factory()->forProject($project)->create();
        $d = ActivityLog::factory()->forProject($project)->create();

        $entries = $this->service()->load(
            $project,
            portalMode: false,
            category: 'all',
            limit: 2,
            beforeId: $c->id,
        );

        // Esperamos los dos anteriores a $c: $b y $a.
        $this->assertCount(2, $entries);
        $this->assertSame([$b->id, $a->id], $entries->pluck('id')->all());
    }

    public function test_total_count_devuelve_total_sin_limite(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->count(7)->create();

        $total = $this->service()->totalCount($project, portalMode: false, category: 'all');

        $this->assertSame(7, $total);
    }

    public function test_total_count_respeta_modo_y_categoria(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->taskCreated()->count(3)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->count(2)->create();
        ActivityLog::factory()->forProject($project)->memberAdded()->count(4)->create();

        // Admin ve todo.
        $this->assertSame(9, $this->service()->totalCount($project, false, 'all'));
        // Portal solo publicos.
        $this->assertSame(5, $this->service()->totalCount($project, true, 'all'));
        // Solo tareas en admin.
        $this->assertSame(3, $this->service()->totalCount($project, false, 'tasks'));
    }

    public function test_categoria_all_no_aplica_filtro(): void
    {
        $project = Project::factory()->create();
        ActivityLog::factory()->forProject($project)->count(3)->create();

        $total = $this->service()->totalCount($project, portalMode: false, category: 'all');
        $totalEmpty = $this->service()->totalCount($project, portalMode: false, category: '');

        $this->assertSame(3, $total);
        $this->assertSame(3, $totalEmpty);
    }
}
