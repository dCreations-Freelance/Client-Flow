<?php

namespace Tests\Unit\Models;

use App\Models\BoardColumn;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_slug_a_partir_del_nombre(): void
    {
        $column = BoardColumn::factory()->create(['name' => 'En Revision']);

        $this->assertSame('en-revision', $column->slug);
    }

    public function test_slug_es_unico_dentro_del_proyecto(): void
    {
        $project = Project::factory()->create();
        BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'Backlog']);
        $second = BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'Backlog']);

        $this->assertSame('backlog-1', $second->slug);
    }

    public function test_slugs_pueden_repetirse_en_distintos_proyectos(): void
    {
        $a = Project::factory()->create();
        $b = Project::factory()->create();

        $colA = BoardColumn::factory()->create(['project_id' => $a->id, 'name' => 'Backlog']);
        $colB = BoardColumn::factory()->create(['project_id' => $b->id, 'name' => 'Backlog']);

        $this->assertSame('backlog', $colA->slug);
        $this->assertSame('backlog', $colB->slug);
    }

    public function test_is_default_devuelve_true_cuando_el_flag_esta_activo(): void
    {
        $column = BoardColumn::factory()->default()->create();

        $this->assertTrue($column->isDefault());
    }

    public function test_scope_ordered_ordena_por_position(): void
    {
        $project = Project::factory()->create();
        BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'C', 'position' => 2]);
        BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'A', 'position' => 0]);
        BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'B', 'position' => 1]);

        $names = $project->columns()->ordered()->pluck('name')->all();

        $this->assertSame(['A', 'B', 'C'], $names);
    }
}
