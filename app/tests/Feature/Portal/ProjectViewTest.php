<?php

namespace Tests\Feature\Portal;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_ve_los_proyectos_visibles_de_sus_organizaciones(): void
    {
        $client = User::factory()->client()->create();
        $own = Project::factory()->create(['is_visible_to_client' => true]);
        $own->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.show', $own))
            ->assertOk()
            ->assertSee($own->name);
    }

    public function test_cliente_no_ve_proyectos_ocultos_de_sus_organizaciones(): void
    {
        $client = User::factory()->client()->create();
        $hidden = Project::factory()->hiddenFromClient()->create();
        $hidden->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.show', $hidden))
            ->assertForbidden();
    }

    public function test_cliente_no_ve_proyectos_de_otras_organizaciones(): void
    {
        $client = User::factory()->client()->create();
        $other = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('portal.projects.show', $other))
            ->assertForbidden();
    }

    public function test_cliente_no_ve_proyectos_archivados(): void
    {
        $client = User::factory()->client()->create();
        $archived = Project::factory()->archived()->create();
        $archived->organization->members()->attach($client->id, ['role' => 'member']);

        $this->actingAs($client)
            ->get(route('portal.projects.show', $archived))
            ->assertForbidden();
    }

    public function test_listado_de_proyectos_del_portal_solo_muestra_visibles(): void
    {
        $client = User::factory()->client()->create();

        $visible = Project::factory()->create();
        $visible->organization->members()->attach($client->id, ['role' => 'member']);

        $hidden = Project::factory()->hiddenFromClient()->create();
        $hidden->organization->members()->attach($client->id, ['role' => 'member']);

        $other = Project::factory()->create(); // org sin el cliente

        $this->actingAs($client)
            ->get(route('portal.projects.index'))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee($hidden->name)
            ->assertDontSee($other->name);
    }

    public function test_cliente_puede_ver_el_detalle_de_su_organizacion(): void
    {
        $client = User::factory()->client()->create();
        $org = Organization::factory()->create();
        $org->members()->attach($client->id, ['role' => 'member']);
        $project = Project::factory()->create([
            'organization_id' => $org->id,
            'is_visible_to_client' => true,
        ]);

        $this->actingAs($client)
            ->get(route('portal.organizations.show', $org))
            ->assertOk()
            ->assertSee($org->name)
            ->assertSee($project->name);
    }

    public function test_cliente_no_puede_ver_el_detalle_de_otra_organizacion(): void
    {
        $client = User::factory()->client()->create();
        $other = Organization::factory()->create();

        $this->actingAs($client)
            ->get(route('portal.organizations.show', $other))
            ->assertForbidden();
    }
}
