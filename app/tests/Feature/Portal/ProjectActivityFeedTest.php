<?php

namespace Tests\Feature\Portal;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature del feed de actividad en el portal cliente.
 *
 * Garantiza que el cliente:
 *  - Puede acceder al feed de un proyecto visible.
 *  - Solo ve eventos publicos (no `member_added`,
 *    `task_deleted`, `template_applied`, etc).
 *  - Los documentos privados no aparecen en el feed.
 *  - No puede acceder a proyectos de otras organizaciones
 *    ni a proyectos archivados.
 *  - El feed vacio muestra el empty state con copy adaptada
 *    al portal.
 */
class ProjectActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(bool $archived = false, bool $visible = true): array
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create([
            'is_visible_to_client' => $visible,
            'archived_at' => $archived ? now() : null,
        ]);
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        return [$client, $project];
    }

    public function test_cliente_puede_ver_el_feed_de_su_proyecto(): void
    {
        [$client, $project] = $this->clientAndProject();

        $this->actingAs($client)
            ->get(route('portal.projects.activity', $project))
            ->assertOk()
            ->assertSee('Actividad del proyecto');
    }

    public function test_cliente_no_puede_ver_proyecto_de_otra_organizacion(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('portal.projects.activity', $otherProject))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_ver_proyecto_archivado(): void
    {
        [$client, $project] = $this->clientAndProject(archived: true);

        $this->actingAs($client)
            ->get(route('portal.projects.activity', $project))
            ->assertForbidden();
    }

    public function test_visitante_sin_sesion_es_redirigido_a_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('portal.projects.activity', $project))
            ->assertRedirect(route('login'));
    }

    public function test_solo_eventos_publicos_se_muestran_al_cliente(): void
    {
        [$client, $project] = $this->clientAndProject();

        // Eventos publicos: 2
        ActivityLog::factory()->forProject($project)->taskCreated()->count(2)->create();
        ActivityLog::factory()->forProject($project)->messageSent()->create();
        // Eventos privados: 3
        ActivityLog::factory()->forProject($project)->memberAdded()->count(2)->create();
        ActivityLog::factory()->forProject($project)->create(['type' => ActivityType::TaskDeleted]);
        ActivityLog::factory()->forProject($project)->create(['type' => ActivityType::TemplateApplied]);

        $this->actingAs($client)
            ->get(route('portal.projects.activity', $project))
            ->assertOk()
            ->assertSee('3 eventos registrados', false);
    }

    public function test_documentos_privados_no_aparecen_en_el_feed(): void
    {
        [$client, $project] = $this->clientAndProject();

        ActivityLog::factory()->forProject($project)->publicDocumentCreated()->create();
        ActivityLog::factory()->forProject($project)->privateDocumentCreated()->create();

        $this->actingAs($client)
            ->get(route('portal.projects.activity', $project))
            ->assertOk()
            ->assertSee('1 evento registrado', false);
    }

    public function test_feed_vacio_muestra_copy_adaptada_al_portal(): void
    {
        [$client, $project] = $this->clientAndProject();

        $this->actingAs($client)
            ->get(route('portal.projects.activity', $project))
            ->assertOk()
            ->assertSee('Cuando haya movimiento en este proyecto lo veras aqui.');
    }

    public function test_cliente_no_puede_acceder_a_ruta_admin(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('admin.projects.activity', $project))
            ->assertRedirect(route('portal.dashboard'));
    }
}
