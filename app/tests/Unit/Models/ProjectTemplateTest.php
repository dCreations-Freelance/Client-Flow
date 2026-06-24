<?php

namespace Tests\Unit\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateColumn;
use App\Models\ProjectTemplateDocument;
use App\Models\ProjectTemplateTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests del modelo `ProjectTemplate`: fillable,
 * generacion automatica de slug, accesors de
 * conteo, scopes (`inCategory`, `search`) y
 * relaciones.
 */
class ProjectTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_crea_plantilla_con_datos_basicos(): void
    {
        $template = ProjectTemplate::factory()->create();

        $this->assertNotNull($template->id);
        $this->assertNotNull($template->name);
        $this->assertNotNull($template->slug);
    }

    public function test_slug_se_genera_automaticamente_al_crear(): void
    {
        $template = ProjectTemplate::factory()->create(['name' => 'Lanzamiento Web 2026', 'slug' => '']);

        $this->assertSame('lanzamiento-web-2026', $template->slug);
    }

    public function test_slug_se_hace_unico_anadiendo_sufijo_numerico(): void
    {
        ProjectTemplate::factory()->create(['name' => 'Plantilla X', 'slug' => 'plantilla-x']);
        $second = ProjectTemplate::factory()->create(['name' => 'Plantilla X', 'slug' => '']);

        $this->assertSame('plantilla-x-1', $second->slug);
    }

    public function test_generate_unique_slug_devuelve_el_slug_base_si_no_existe(): void
    {
        $slug = ProjectTemplate::generateUniqueSlug('Hola Mundo');
        $this->assertSame('hola-mundo', $slug);
    }

    public function test_generate_unique_slug_anade_sufijo_si_hay_colision(): void
    {
        ProjectTemplate::factory()->create(['name' => 'X', 'slug' => 'x']);
        $slug = ProjectTemplate::generateUniqueSlug('X');
        $this->assertSame('x-1', $slug);
    }

    public function test_accesor_categoria_label_devuelve_valor_o_default(): void
    {
        $withCategory = ProjectTemplate::factory()->create(['category' => 'web']);
        $withoutCategory = ProjectTemplate::factory()->create(['category' => null]);
        $withEmpty = ProjectTemplate::factory()->create(['category' => '']);

        $this->assertSame('web', $withCategory->category_label);
        $this->assertSame('Sin categoria', $withoutCategory->category_label);
        $this->assertSame('Sin categoria', $withEmpty->category_label);
    }

    public function test_accesors_de_conteo_devuelven_numeros_correctos(): void
    {
        $template = ProjectTemplate::factory()->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->count(2)->create();
        ProjectTemplateTask::factory()->forTemplate($template)->count(3)->create();
        ProjectTemplateDocument::factory()->forTemplate($template)->count(1)->create();

        $this->assertSame(2, $template->column_count);
        $this->assertSame(3, $template->task_count);
        $this->assertSame(1, $template->document_count);
    }

    public function test_scope_in_category_filtra_por_categoria(): void
    {
        ProjectTemplate::factory()->create(['category' => 'web']);
        ProjectTemplate::factory()->create(['category' => 'mobile']);
        ProjectTemplate::factory()->create(['category' => null]);

        $this->assertCount(1, ProjectTemplate::inCategory('web')->get());
        $this->assertCount(1, ProjectTemplate::inCategory('mobile')->get());
        $this->assertCount(3, ProjectTemplate::inCategory(null)->get());
        $this->assertCount(3, ProjectTemplate::inCategory('')->get());
    }

    public function test_scope_search_busca_en_name_y_description(): void
    {
        ProjectTemplate::factory()->create(['name' => 'Lanzamiento', 'description' => 'Para cliente A']);
        ProjectTemplate::factory()->create(['name' => 'Mantenimiento', 'description' => 'Para cliente B']);

        $this->assertCount(1, ProjectTemplate::search('Lanzamiento')->get());
        $this->assertCount(1, ProjectTemplate::search('cliente B')->get());
        $this->assertCount(2, ProjectTemplate::search('')->get());
    }

    public function test_relaciones_con_creator_columns_tasks_documents(): void
    {
        $admin = User::factory()->admin()->create();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        ProjectTemplateColumn::factory()->forTemplate($template)->create();
        ProjectTemplateTask::factory()->forTemplate($template)->create();
        ProjectTemplateDocument::factory()->forTemplate($template)->create();

        $this->assertSame($admin->id, $template->creator->id);
        $this->assertCount(1, $template->columns);
        $this->assertCount(1, $template->tasks);
        $this->assertCount(1, $template->documents);
    }

    public function test_columns_relacion_devuelve_orden_por_position(): void
    {
        $template = ProjectTemplate::factory()->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(2)->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(1)->create();

        $positions = $template->columns->pluck('position')->all();
        $this->assertSame([0, 1, 2], $positions);
    }

    public function test_tasks_relacion_devuelve_orden_por_column_position_y_position(): void
    {
        $template = ProjectTemplate::factory()->create();
        // Columna 1, posicion 0; columna 0, posicion 1; columna 0, posicion 0.
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(1)->create(['position' => 0]);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['position' => 1]);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['position' => 0]);

        $columnPositions = $template->tasks->pluck('column_position')->all();
        $this->assertSame([0, 0, 1], $columnPositions);
    }

    public function test_enums_type_y_priority_se_castean(): void
    {
        $task = ProjectTemplateTask::factory()->create([
            'type' => TaskType::Bug,
            'priority' => TaskPriority::Critical,
        ]);

        $this->assertInstanceOf(TaskType::class, $task->type);
        $this->assertInstanceOf(TaskPriority::class, $task->priority);
        $this->assertSame(TaskType::Bug, $task->type);
        $this->assertSame(TaskPriority::Critical, $task->priority);
    }

    public function test_document_is_public_devuelve_true_si_visibility_es_public(): void
    {
        $public = ProjectTemplateDocument::factory()->public()->create();
        $private = ProjectTemplateDocument::factory()->create(['visibility' => \App\Enums\DocumentVisibility::Private]);

        $this->assertTrue($public->isPublic());
        $this->assertFalse($private->isPublic());
    }
}
