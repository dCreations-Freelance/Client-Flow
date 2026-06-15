<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_formulario_de_registro_a_visitantes(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Crea tu cuenta');
    }

    public function test_crea_un_usuario_con_rol_client_al_registrarse(): void
    {
        $this->post(route('register'), [
            'name' => 'Cliente Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
        ])->assertRedirect(route('portal.dashboard'));

        $user = User::where('email', 'nuevo@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertTrue(Hash::check('password-seguro', $user->password));
    }

    public function test_rechaza_el_registro_con_email_duplicado(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->post(route('register'), [
            'name' => 'Otro',
            'email' => 'repetido@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
        ])->assertSessionHasErrors('email');
    }

    public function test_rechaza_contrasena_corta(): void
    {
        $this->post(route('register'), [
            'name' => 'Corto',
            'email' => 'corto@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }

    public function test_rechaza_contrasenas_que_no_coinciden(): void
    {
        $this->post(route('register'), [
            'name' => 'Mismatch',
            'email' => 'mismatch@example.com',
            'password' => 'password-seguro',
            'password_confirmation' => 'otra-cosa',
        ])->assertSessionHasErrors('password');
    }

    public function test_redirige_a_su_dashboard_si_un_usuario_autenticado_visita_el_registro(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('home'));
    }
}
