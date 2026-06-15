<?php

namespace Tests\Unit\Services;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultBoardColumnsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_las_cuatro_columnas_default_con_is_default_y_posiciones(): void
    {
        $project = Project::factory()->create();

        app(\App\Services\DefaultBoardColumnsService::class)->create($project);

        $columns = $project->columns()->ordered()->get();

        $this->assertCount(4, $columns);
        $this->assertSame(['Por hacer', 'En curso', 'En revision', 'Hecho'], $columns->pluck('name')->all());
        $columns->each(fn (BoardColumn $c) => $this->assertTrue($c->isDefault()));
        $this->assertSame([0, 1, 2, 3], $columns->pluck('position')->all());
    }

    public function test_ensure_es_idempotente(): void
    {
        $project = Project::factory()->create();

        $service = app(\App\Services\DefaultBoardColumnsService::class);
        $service->ensure($project);
        $service->ensure($project);

        $this->assertCount(4, $project->columns()->get());
    }

    public function test_create_forzado_duplica_columnas(): void
    {
        $project = Project::factory()->create();

        $service = app(\App\Services\DefaultBoardColumnsService::class);
        $service->create($project);
        $service->create($project);

        // La segunda llamada crea 4 mas (8 total) porque no es
        // idempotente. Esto esta documentado en PHPDoc del metodo.
        $this->assertCount(8, $project->columns()->get());
    }
}
