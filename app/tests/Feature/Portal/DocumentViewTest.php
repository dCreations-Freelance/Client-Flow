<?php

namespace Tests\Feature\Portal;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature del portal cliente: documentos publicos.
 *
 * Garantiza el aislamiento: el cliente solo ve documentos
 * `public` de proyectos a los que pertenece (visibles y no
 * archivados), nunca documentos `private` aunque conozca la URL.
 */
class DocumentViewTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(): array
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        return [$client, $project];
    }

    public function test_cliente_ve_solo_documentos_publicos(): void
    {
        [$client, $project] = $this->clientAndProject();
        $public = ProjectDocument::factory()->public()->create(['project_id' => $project->id, 'title' => 'Manual']);
        ProjectDocument::factory()->private()->create(['project_id' => $project->id, 'title' => 'Notas internas']);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.documents.index', $project));

        $response->assertOk();
        $response->assertSee('Manual');
        $response->assertDontSee('Notas internas');
    }

    public function test_cliente_no_puede_ver_documento_privado_por_url(): void
    {
        [$client, $project] = $this->clientAndProject();
        $private = ProjectDocument::factory()->private()->create(['project_id' => $project->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.documents.show', [$project, $private]))
            ->assertForbidden();
    }

    public function test_cliente_puede_ver_documento_publico_por_url(): void
    {
        [$client, $project] = $this->clientAndProject();
        $public = ProjectDocument::factory()->public()->create([
            'project_id' => $project->id,
            'title' => 'Manual',
            'content' => '# Bienvenido',
        ]);

        $this->actingAs($client)
            ->get(route('portal.projects.documents.show', [$project, $public]))
            ->assertOk()
            ->assertSee('Manual')
            ->assertSee('Bienvenido');
    }

    public function test_cliente_puede_buscar_entre_los_publicos(): void
    {
        [$client, $project] = $this->clientAndProject();
        ProjectDocument::factory()->public()->create([
            'project_id' => $project->id,
            'title' => 'Manual de instalacion',
            'content' => 'Pasos para arrancar',
        ]);
        ProjectDocument::factory()->public()->create([
            'project_id' => $project->id,
            'title' => 'Guia de uso',
            'content' => 'Otras cosas',
        ]);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.documents.index', ['project' => $project, 'search' => 'instalacion']));

        $response->assertOk();
        $response->assertSee('Manual de instalacion');
        $response->assertDontSee('Guia de uso');
    }

    public function test_cliente_no_puede_acceder_a_proyecto_de_otra_organizacion(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();
        $document = ProjectDocument::factory()->public()->create(['project_id' => $otherProject->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.documents.index', $otherProject))
            ->assertForbidden();

        $this->actingAs($client)
            ->get(route('portal.projects.documents.show', [$otherProject, $document]))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_documentos_de_proyecto_archivado(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->archived()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $document = ProjectDocument::factory()->public()->create(['project_id' => $project->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.documents.index', $project))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_documentos_de_proyecto_oculto(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->hiddenFromClient()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $document = ProjectDocument::factory()->public()->create(['project_id' => $project->id]);

        $this->actingAs($client)
            ->get(route('portal.projects.documents.index', $project))
            ->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_rutas_admin_de_documentos(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $document = ProjectDocument::factory()->create(['project_id' => $project->id]);

        // Todas las rutas admin redirigen al portal dashboard porque
        // el middleware `admin` no permite clientes.
        $this->actingAs($client)
            ->get(route('admin.projects.documents.create', $project))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->get(route('admin.projects.documents.show', [$project, $document]))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->get(route('admin.projects.documents.edit', [$project, $document]))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_listado_vacio_si_no_hay_documentos_publicos(): void
    {
        [$client, $project] = $this->clientAndProject();
        ProjectDocument::factory()->private()->create(['project_id' => $project->id, 'title' => 'Privado']);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.documents.index', $project));

        $response->assertOk();
        $response->assertSee('Aun no hay documentos publicos');
        $response->assertDontSee('Privado');
    }
}
