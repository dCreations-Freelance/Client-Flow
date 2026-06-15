<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_formulario_de_login_a_visitantes(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bienvenido de vuelta');
    }

    public function test_redirige_a_admin_al_login_si_el_usuario_es_administrador(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_redirige_a_portal_al_login_si_el_usuario_es_cliente(): void
    {
        $client = User::factory()->client()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $client->email,
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard'));
    }

    public function test_muestra_error_con_credenciales_invalidas(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_redirige_a_su_dashboard_si_el_usuario_autenticado_visita_el_formulario_de_login(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_cierra_sesion_y_redirige_al_home(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertFalse(auth()->check());
    }

    public function test_asigna_el_rol_client_por_defecto_al_crear_usuarios_via_factory(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Client, $user->role);
    }
}
