<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirige_a_login_cuando_un_visitante_intenta_acceder_al_panel_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_redirige_al_portal_cuando_un_cliente_intenta_acceder_al_panel_admin(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_permite_al_administrador_acceder_al_panel_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_redirige_a_login_cuando_un_visitante_intenta_acceder_al_portal_cliente(): void
    {
        $this->get(route('portal.dashboard'))->assertRedirect(route('login'));
    }

    public function test_redirige_al_panel_admin_cuando_un_admin_intenta_acceder_al_portal(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_permite_al_cliente_acceder_al_portal(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('portal.dashboard'))
            ->assertOk();
    }

    public function test_redirige_al_home_cuando_un_usuario_autenticado_visita_la_raiz(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
