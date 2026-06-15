<?php

namespace Tests\Unit\Models;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_slug_a_partir_del_nombre_al_crear(): void
    {
        $project = Project::factory()->create(['name' => 'Web Cliente A']);

        $this->assertSame('web-cliente-a', $project->slug);
    }

    public function test_anade_sufijo_numerico_si_el_slug_ya_existe(): void
    {
        Project::factory()->create(['name' => 'Duplicado']);
        $second = Project::factory()->create(['name' => 'Duplicado']);

        $this->assertSame('duplicado-1', $second->slug);
    }

    public function test_relacion_organization_apunta_a_la_organizacion(): void
    {
        $org = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($project->organization->is($org));
    }

    public function test_relacion_members_devuelve_usuarios_asignados(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $project->members()->attach($user->id);

        $this->assertTrue($project->members->contains($user));
    }

    public function test_scope_active_excluye_completados_y_archivados(): void
    {
        $open = Project::factory()->create();
        $completed = Project::factory()->completed()->create();
        $archived = Project::factory()->archived()->create();

        $ids = Project::active()->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($completed->id, $ids);
        $this->assertNotContains($archived->id, $ids);
    }

    public function test_scope_archived_solo_devuelve_archivados(): void
    {
        Project::factory()->create();
        $archived = Project::factory()->archived()->create();

        $ids = Project::archived()->pluck('id')->all();

        $this->assertSame([$archived->id], $ids);
    }

    public function test_scope_visible_to_client_excluye_ocultos_y_archivados(): void
    {
        $visible = Project::factory()->create(['is_visible_to_client' => true]);
        $hidden = Project::factory()->hiddenFromClient()->create();
        $archived = Project::factory()->archived()->create();

        $ids = Project::visibleToClient()->pluck('id')->all();

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertNotContains($archived->id, $ids);
    }

    public function test_scope_for_user_solo_devuelve_proyectos_del_usuario_via_org(): void
    {
        $user = User::factory()->client()->create();
        $own = Project::factory()->create();
        $own->organization->members()->attach($user->id, ['role' => 'member']);
        Project::factory()->create();

        $ids = Project::forUser($user)->pluck('id')->all();

        $this->assertSame([$own->id], $ids);
    }

    public function test_archive_marca_archived_at_y_es_idempotente(): void
    {
        $project = Project::factory()->create();

        $project->archive();
        $firstArchivedAt = $project->fresh()->archived_at;
        $this->assertNotNull($firstArchivedAt);

        // Segunda llamada no modifica la fecha.
        sleep(1);
        $project->archive();
        $this->assertEquals(
            $firstArchivedAt->toDateTimeString(),
            $project->fresh()->archived_at->toDateTimeString(),
        );
    }

    public function test_unarchive_limpia_archived_at(): void
    {
        $project = Project::factory()->archived()->create();

        $project->unarchive();

        $this->assertNull($project->fresh()->archived_at);
    }

    public function test_is_visible_to_client_devuelve_false_si_esta_archivado(): void
    {
        $project = Project::factory()->create(['is_visible_to_client' => true]);
        $project->archive();

        $this->assertFalse($project->fresh()->isVisibleToClient());
    }

    public function test_casts_status_y_flags_correctamente(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'is_visible_to_client' => 0,
        ]);

        $this->assertInstanceOf(ProjectStatus::class, $project->status);
        $this->assertSame(ProjectStatus::InProgress, $project->status);
        $this->assertFalse($project->is_visible_to_client);
    }

    public function test_progreso_calculado_devuelve_cero_sin_tareas(): void
    {
        $project = Project::factory()->create();

        $this->assertSame(0, $project->tasks_progress_percent);
        $this->assertSame(0, $project->total_tasks_count);
        $this->assertSame(0, $project->completed_tasks_count);
    }

    public function test_progreso_calculado_desde_tareas_raiz(): void
    {
        $project = Project::factory()->create();

        // 2 tareas raiz pendientes, 1 raiz completada.
        Task::factory()->count(2)->create(['project_id' => $project->id]);
        Task::factory()->completed()->create(['project_id' => $project->id]);

        // 2 subtareas (no deben contar en el progreso raiz).
        $parent = Task::factory()->create(['project_id' => $project->id]);
        Task::factory()->completed()->create(['project_id' => $project->id, 'parent_id' => $parent->id]);
        Task::factory()->create(['project_id' => $project->id, 'parent_id' => $parent->id]);

        $this->assertSame(4, $project->total_tasks_count);
        $this->assertSame(1, $project->completed_tasks_count);
        $this->assertSame(25, $project->tasks_progress_percent);
    }

    public function test_progreso_devuelve_cien_si_todas_las_raiz_estan_completadas(): void
    {
        $project = Project::factory()->create();
        Task::factory()->completed()->create(['project_id' => $project->id]);
        Task::factory()->completed()->create(['project_id' => $project->id]);

        $this->assertSame(100, $project->tasks_progress_percent);
    }
}
