<?php

namespace Tests\Unit\Services\Project;

use App\Enums\DocumentVisibility;
use App\Enums\ProjectStatus;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Task;
use App\Models\User;
use App\Services\Project\ProjectSummaryService;
use App\Services\DefaultBoardColumnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProjectSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_for_admin_incluye_documentos_privados_en_el_preview(): void
    {
        $project = Project::factory()->create();
        ProjectDocument::factory()->public()->create(['project_id' => $project->id, 'title' => 'Doc publico']);
        ProjectDocument::factory()->private()->create(['project_id' => $project->id, 'title' => 'Doc privado']);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertSame(2, $summary->totalDocuments);
        $this->assertCount(2, $summary->previewDocuments);
    }

    public function test_load_for_portal_excluye_documentos_privados_del_preview_y_del_total(): void
    {
        $project = Project::factory()->create();
        ProjectDocument::factory()->public()->create(['project_id' => $project->id, 'title' => 'Doc publico']);
        ProjectDocument::factory()->private()->create(['project_id' => $project->id, 'title' => 'Doc privado']);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForPortal($project, User::factory()->client()->create());

        $this->assertSame(1, $summary->totalDocuments);
        $this->assertCount(1, $summary->previewDocuments);
        $this->assertSame('Doc publico', $summary->previewDocuments->first()->title);
    }

    public function test_next_delivery_marca_como_retrasada_con_dias_positivos_cuando_esta_vencida(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'estimated_ends_at' => Carbon::today()->subDays(5),
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertSame('Retrasada 5 dias', $summary->nextDeliveryLabel);
        $this->assertSame('danger', $summary->nextDeliveryTone);
    }

    public function test_next_delivery_muestra_un_dia_singular_cuando_es_justo_ayer(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'estimated_ends_at' => Carbon::today()->subDay(),
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertSame('Retrasada 1 dia', $summary->nextDeliveryLabel);
    }

    public function test_next_delivery_usa_para_hoy_cuando_la_fecha_es_igual_a_hoy(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'estimated_ends_at' => Carbon::today(),
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertSame('Para hoy', $summary->nextDeliveryLabel);
        $this->assertSame('warning', $summary->nextDeliveryTone);
    }

    public function test_next_delivery_muestra_en_n_dias_cuando_es_futuro(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'estimated_ends_at' => Carbon::today()->addDays(7),
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertSame('En 7 dias', $summary->nextDeliveryLabel);
        $this->assertSame('warning', $summary->nextDeliveryTone);
    }

    public function test_next_delivery_devuelve_sin_fecha_cuando_no_hay_estimated_ends_at(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'estimated_ends_at' => null,
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertNull($summary->nextDelivery);
        $this->assertSame('Sin fecha definida', $summary->nextDeliveryLabel);
        $this->assertSame('neutral', $summary->nextDeliveryTone);
    }

    public function test_next_delivery_muestra_entregado_el_cuando_el_proyecto_esta_completado(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Completed,
            'estimated_ends_at' => Carbon::today()->subDays(2),
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $this->assertSame('Entregado el '.$project->estimated_ends_at->format('d/m/Y'), $summary->nextDeliveryLabel);
        $this->assertSame('success', $summary->nextDeliveryTone);
    }

    public function test_board_preview_limita_las_tareas_a_tres_por_columna_pero_el_count_refleja_el_total(): void
    {
        $project = Project::factory()->create();
        app(DefaultBoardColumnsService::class)->create($project);

        $firstColumn = $project->columns()->ordered()->first();
        Task::factory()->count(5)->create([
            'project_id' => $project->id,
            'column_id' => $firstColumn->id,
            'parent_id' => null,
        ]);

        $service = app(ProjectSummaryService::class);
        $summary = $service->loadForAdmin($project, User::factory()->admin()->create());

        $preview = $summary->boardPreview->firstWhere('column.id', $firstColumn->id);
        $this->assertNotNull($preview);
        $this->assertCount(3, $preview->previewTasks);
        $this->assertSame(5, $summary->columnCounts[$firstColumn->slug]);
    }
}
