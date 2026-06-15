<?php

namespace Tests\Feature\Portal;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_muestra_las_organizaciones_del_cliente(): void
    {
        $client = User::factory()->client()->create();

        $ownOrg = Organization::factory()->create(['name' => 'Mi Empresa']);
        $ownOrg->members()->attach($client->id, [
            'role' => OrganizationUserRole::Member->value,
        ]);

        $otherOrg = Organization::factory()->create(['name' => 'Otra Empresa']);

        $this->actingAs($client)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Mi Empresa')
            ->assertDontSee('Otra Empresa');
    }

    public function test_dashboard_muestra_estado_vacio_si_no_tiene_organizaciones(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Aun no perteneces a ninguna organizacion');
    }

    public function test_dashboard_no_es_accesible_por_administrador(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
