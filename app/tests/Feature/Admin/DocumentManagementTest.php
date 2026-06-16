<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature del CRUD de documentos en el panel admin.
 *
 * Cubre: alta, baja, edicion, validacion, busqueda, filtro de
 * visibilidad y aislamiento de clientes (que no pueden acceder a
 * ninguna ruta admin).
 */
class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminAndProject(): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        return [$admin, $project];
    }

    public function test_admin_puede_crear_documento_privado(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'Notas internas',
                'content' => '# Privado\n\nEsto no lo ve el cliente.',
                'visibility' => 'private',
            ])->assertRedirect();

        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'title' => 'Notas internas',
            'visibility' => 'private',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_puede_crear_documento_publico(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'Manual de uso',
                'content' => '# Manual\n\nBienvenido al proyecto.',
                'visibility' => 'public',
            ])->assertRedirect();

        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'title' => 'Manual de uso',
            'visibility' => 'public',
        ]);
    }

    public function test_admin_puede_editar_y_cambiar_visibilidad(): void
    {
        [$admin, $project] = $this->adminAndProject();
        $document = ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'visibility' => 'private',
            'title' => 'Original',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.projects.documents.update', [$project, $document]), [
                'title' => 'Editado',
                'content' => 'Nuevo contenido',
                'visibility' => 'public',
            ])->assertRedirect();

        $document->refresh();
        $this->assertSame('Editado', $document->title);
        $this->assertSame('Nuevo contenido', $document->content);
        $this->assertSame('public', $document->visibility->value);
    }

    public function test_admin_puede_eliminar_documento(): void
    {
        [$admin, $project] = $this->adminAndProject();
        $document = ProjectDocument::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.documents.destroy', [$project, $document]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_documents', ['id' => $document->id]);
    }

    public function test_validacion_rechaza_titulo_vacio(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => '',
                'content' => 'Algo',
                'visibility' => 'private',
            ])->assertSessionHasErrors('title');
    }

    public function test_validacion_rechaza_content_vacio(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'Algo',
                'content' => '',
                'visibility' => 'private',
            ])->assertSessionHasErrors('content');
    }

    public function test_validacion_rechaza_visibilidad_invalida(): void
    {
        [$admin, $project] = $this->adminAndProject();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'Algo',
                'content' => 'Algo',
                'visibility' => 'draft',
            ])->assertSessionHasErrors('visibility');
    }

    public function test_busqueda_por_titulo(): void
    {
        [$admin, $project] = $this->adminAndProject();
        ProjectDocument::factory()->create(['project_id' => $project->id, 'title' => 'Manual de instalacion']);
        ProjectDocument::factory()->create(['project_id' => $project->id, 'title' => 'Guia de uso']);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.documents.index', ['project' => $project, 'search' => 'instalacion']));

        $response->assertOk();
        $response->assertSee('Manual de instalacion');
        $response->assertDontSee('Guia de uso');
    }

    public function test_busqueda_por_contenido(): void
    {
        [$admin, $project] = $this->adminAndProject();
        ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'title' => 'Doc 1',
            'content' => 'Aqui hablamos de tokens JWT',
        ]);
        ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'title' => 'Doc 2',
            'content' => 'Hablamos de otra cosa',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.documents.index', ['project' => $project, 'search' => 'JWT']));

        $response->assertOk();
        $response->assertSee('Doc 1');
        $response->assertDontSee('Doc 2');
    }

    public function test_filtro_por_visibilidad(): void
    {
        [$admin, $project] = $this->adminAndProject();
        ProjectDocument::factory()->public()->create(['project_id' => $project->id, 'title' => 'Doc publico']);
        ProjectDocument::factory()->private()->create(['project_id' => $project->id, 'title' => 'Doc privado']);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.documents.index', ['project' => $project, 'visibility' => 'public']));

        $response->assertOk();
        $response->assertSee('Doc publico');
        $response->assertDontSee('Doc privado');
    }

    public function test_cliente_no_puede_listar_documentos_admin(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('admin.projects.documents.index', $project))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_cliente_no_puede_crear_documentos(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->post(route('admin.projects.documents.store', $project), [
                'title' => 'X',
                'content' => 'Y',
                'visibility' => 'public',
            ])->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseCount('project_documents', 0);
    }

    public function test_cliente_no_puede_editar_documentos(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $document = ProjectDocument::factory()->create(['project_id' => $project->id]);

        $this->actingAs($client)
            ->put(route('admin.projects.documents.update', [$project, $document]), [
                'title' => 'Nuevo',
                'content' => 'Nuevo',
                'visibility' => 'public',
            ])->assertRedirect(route('portal.dashboard'));

        $document->refresh();
        $this->assertNotSame('Nuevo', $document->title);
    }

    public function test_cliente_no_puede_eliminar_documentos(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $document = ProjectDocument::factory()->create(['project_id' => $project->id]);

        $this->actingAs($client)
            ->delete(route('admin.projects.documents.destroy', [$project, $document]))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('project_documents', ['id' => $document->id]);
    }

    public function test_listado_muestra_tanto_privados_como_publicos(): void
    {
        [$admin, $project] = $this->adminAndProject();
        ProjectDocument::factory()->public()->create(['project_id' => $project->id, 'title' => 'Pub']);
        ProjectDocument::factory()->private()->create(['project_id' => $project->id, 'title' => 'Priv']);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.documents.index', $project));

        $response->assertOk();
        $response->assertSee('Pub');
        $response->assertSee('Priv');
    }
}
