<?php

namespace Tests\Feature\Admin;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_listar_proyectos(): void
    {
        $admin = User::factory()->admin()->create();
        Project::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Proyectos');
    }

    public function test_cliente_no_puede_listar_proyectos(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.projects.index'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_puede_crear_un_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.projects.store'), [
            'name' => 'Lanzamiento Web',
            'organization_id' => $org->id,
            'description' => 'Detalle del proyecto',
            'status' => ProjectStatus::Planning->value,
            'is_visible_to_client' => true,
        ]);

        $project = Project::where('name', 'Lanzamiento Web')->first();

        $this->assertNotNull($project);
        $this->assertSame('lanzamiento-web', $project->slug);
        $this->assertSame(0, $project->progress);
        $response->assertRedirect(route('admin.projects.show', $project));
    }

    public function test_cliente_no_puede_crear_proyectos(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();

        $this->actingAs($client)
            ->post(route('admin.projects.store'), [
                'name' => 'Hack',
                'organization_id' => $org->id,
                'status' => ProjectStatus::Planning->value,
            ])->assertRedirect(route('portal.dashboard'));
    }

    public function test_validacion_rechaza_nombre_vacio(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.store'), [
                'name' => '',
                'organization_id' => $org->id,
                'status' => ProjectStatus::Planning->value,
            ])->assertSessionHasErrors('name');
    }

    public function test_validacion_rechaza_organizacion_inexistente(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.store'), [
                'name' => 'OK',
                'organization_id' => 99999,
                'status' => ProjectStatus::Planning->value,
            ])->assertSessionHasErrors('organization_id');
    }

    public function test_validacion_rechaza_status_invalido(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.store'), [
                'name' => 'OK',
                'organization_id' => $org->id,
                'status' => 'no-existe',
            ])->assertSessionHasErrors('status');
    }

    public function test_validacion_rechaza_fecha_fin_anterior_a_inicio(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.store'), [
                'name' => 'OK',
                'organization_id' => $org->id,
                'status' => ProjectStatus::Planning->value,
                'starts_at' => '2026-06-30',
                'estimated_ends_at' => '2026-06-01',
            ])->assertSessionHasErrors('estimated_ends_at');
    }

    public function test_admin_puede_editar_un_proyecto_y_archivar_via_status(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.projects.update', $project), [
                'name' => 'Nuevo nombre',
                'organization_id' => $project->organization_id,
                'description' => 'X',
                'status' => ProjectStatus::Archived->value,
                'progress' => 50,
                'is_visible_to_client' => true,
            ])->assertRedirect(route('admin.projects.show', $project));

        $project->refresh();
        $this->assertSame('Nuevo nombre', $project->name);
        $this->assertNotNull($project->archived_at);
        $this->assertSame(50, $project->progress);
    }

    public function test_admin_puede_eliminar_un_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.projects.destroy', $project))
            ->assertRedirect(route('admin.projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_cliente_no_puede_ver_proyecto_de_otra_organizacion(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('admin.projects.show', $otherProject))
            ->assertRedirect(route('portal.dashboard'));
    }
}
