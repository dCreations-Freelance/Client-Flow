<?php

namespace Tests\Unit\Services\ProjectTemplate;

use App\Enums\DocumentVisibility;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateColumn;
use App\Models\ProjectTemplateDocument;
use App\Models\ProjectTemplateTask;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTemplate\ProjectTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del `ProjectTemplateService`:
 * - `applyToProject` copia columnas, tareas y
 *   documentos del template al proyecto destino
 *   con la metadata correcta.
 * - El mapeo de tareas por `column_position` se
 *   resuelve correctamente.
 * - `categories()` devuelve la lista de categorias
 *   distintas.
 * - Los helpers de `nextXPosition` calculan la
 *   siguiente posicion libre correctamente.
 */
class ProjectTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectTemplateService::class);
    }

    private function projectAndAdmin(): array
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        return [$admin, $project];
    }

    public function test_apply_to_project_copia_columnas_con_color_y_posicion(): void
    {
        [$admin, $project] = $this->projectAndAdmin();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create(['name' => 'Pendiente', 'color' => '#94A3B8']);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(1)->create(['name' => 'En curso', 'color' => '#2563EB']);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(2)->create(['name' => 'Hecho', 'color' => '#16A34A']);

        $result = $this->service->applyToProject($template, $project, $admin);

        $this->assertSame(3, $result['columns']);
        $this->assertDatabaseCount('board_columns', 3);

        $first = $project->columns()->where('position', 0)->first();
        $this->assertSame('Pendiente', $first->name);
        $this->assertSame('#94A3B8', $first->color);
        $this->assertFalse($first->is_default);
    }

    public function test_apply_to_project_copia_tareas_en_la_columna_correcta(): void
    {
        [$admin, $project] = $this->projectAndAdmin();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(1)->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(2)->create();

        // 2 tareas en la columna 0, 1 en la columna 1, ninguna en la 2.
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['title' => 'Tarea A']);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['title' => 'Tarea B']);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(1)->create(['title' => 'Tarea C']);

        $result = $this->service->applyToProject($template, $project, $admin);

        $this->assertSame(3, $result['tasks']);
        $this->assertSame(3, $project->tasks()->count());
        $this->assertSame(2, $project->tasks()->whereHas('column', fn ($q) => $q->where('position', 0))->count());
        $this->assertSame(1, $project->tasks()->whereHas('column', fn ($q) => $q->where('position', 1))->count());
    }

    public function test_apply_to_project_copia_documentos_con_visibilidad(): void
    {
        [$admin, $project] = $this->projectAndAdmin();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        ProjectTemplateDocument::factory()->forTemplate($template)->create([
            'title' => 'Interno',
            'visibility' => DocumentVisibility::Private,
        ]);
        ProjectTemplateDocument::factory()->forTemplate($template)->public()->create([
            'title' => 'Para el cliente',
        ]);

        $result = $this->service->applyToProject($template, $project, $admin);

        $this->assertSame(2, $result['documents']);
        $this->assertDatabaseCount('project_documents', 2);
        $this->assertSame(1, ProjectDocument::where('visibility', DocumentVisibility::Public)->count());
    }

    public function test_apply_to_project_copia_estimated_hours_y_type_de_las_tareas(): void
    {
        [$admin, $project] = $this->projectAndAdmin();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create([
            'title' => 'Tarea con detalle',
            'description' => 'Detalle extenso',
            'type' => \App\Enums\TaskType::Feature,
            'priority' => \App\Enums\TaskPriority::High,
            'estimated_hours' => 8.5,
        ]);

        $this->service->applyToProject($template, $project, $admin);

        $task = $project->tasks()->first();
        $this->assertSame('Tarea con detalle', $task->title);
        $this->assertSame('Detalle extenso', $task->description);
        $this->assertSame(\App\Enums\TaskType::Feature, $task->type);
        $this->assertSame(\App\Enums\TaskPriority::High, $task->priority);
        $this->assertSame(8.5, (float) $task->estimated_hours);
        $this->assertSame($admin->id, $task->created_by);
    }

    public function test_apply_to_project_salta_tareas_que_referencian_columna_inexistente(): void
    {
        [$admin, $project] = $this->projectAndAdmin();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();
        // Tarea que apunta a una columna que no existe (posicion 5).
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(5)->create();

        $result = $this->service->applyToProject($template, $project, $admin);

        $this->assertSame(0, $result['tasks']);
        $this->assertSame(0, $project->tasks()->count());
    }

    public function test_apply_a_proyecto_vacio_no_crea_nada(): void
    {
        [$admin, $project] = $this->projectAndAdmin();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);

        $result = $this->service->applyToProject($template, $project, $admin);

        $this->assertSame(['columns' => 0, 'tasks' => 0, 'documents' => 0], $result);
    }

    public function test_categories_devuelve_lista_unica_ordenada(): void
    {
        $admin = User::factory()->admin()->create();
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'category' => 'web']);
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'category' => 'mobile']);
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'category' => 'web']); // duplicado
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'category' => null]); // excluido
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'category' => '']); // excluido
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'category' => 'design']);

        $categories = $this->service->categories()->all();

        $this->assertSame(['design', 'mobile', 'web'], $categories);
    }

    public function test_query_with_filters_aplica_search_y_categoria(): void
    {
        $admin = User::factory()->admin()->create();
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'name' => 'Lanzamiento web', 'category' => 'web']);
        ProjectTemplate::factory()->create(['created_by' => $admin->id, 'name' => 'App movil', 'category' => 'mobile']);

        $byCategory = $this->service->queryWithFilters(null, 'web')->get();
        $this->assertCount(1, $byCategory);
        $this->assertSame('Lanzamiento web', $byCategory->first()->name);

        $bySearch = $this->service->queryWithFilters('movil', null)->get();
        $this->assertCount(1, $bySearch);
        $this->assertSame('App movil', $bySearch->first()->name);
    }

    public function test_next_column_position_devuelve_siguiente_libre(): void
    {
        $admin = User::factory()->admin()->create();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        $this->assertSame(0, $this->service->nextColumnPosition($template));

        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(1)->create();
        $this->assertSame(2, $this->service->nextColumnPosition($template));
    }

    public function test_next_task_position_por_columna(): void
    {
        $admin = User::factory()->admin()->create();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        // Las factories asignan position=0 por defecto;
        // simulamos posiciones reales (0, 1, 0).
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['position' => 0]);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['position' => 1]);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(1)->create(['position' => 0]);

        $this->assertSame(2, $this->service->nextTaskPosition($template, 0));
        $this->assertSame(1, $this->service->nextTaskPosition($template, 1));
        $this->assertSame(0, $this->service->nextTaskPosition($template, 5));
    }

    public function test_next_document_position_devuelve_siguiente_libre(): void
    {
        $admin = User::factory()->admin()->create();
        $template = ProjectTemplate::factory()->create(['created_by' => $admin->id]);
        $this->assertSame(0, $this->service->nextDocumentPosition($template));

        ProjectTemplateDocument::factory()->forTemplate($template)->create(['position' => 0]);
        ProjectTemplateDocument::factory()->forTemplate($template)->create(['position' => 1]);
        $this->assertSame(2, $this->service->nextDocumentPosition($template));
    }
}
