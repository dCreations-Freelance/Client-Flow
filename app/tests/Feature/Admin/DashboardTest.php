<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_muestra_conteos_y_organizaciones_recientes(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->count(3)->create();
        Organization::factory()->inactive()->create();

        OrganizationInvitation::factory()->create([
            'organization_id' => Organization::first()->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel de administracion')
            ->assertSee('Organizaciones recientes')
            ->assertSee('3', false); // total de organizaciones
    }

    public function test_dashboard_no_muestra_organizaciones_a_clientes(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('portal.dashboard'));
    }
}
