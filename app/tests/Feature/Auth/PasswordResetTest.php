<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_formulario_para_pedir_el_enlace_de_recuperacion(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar contrasena');
    }

    public function test_envia_el_enlace_de_recuperacion_si_el_email_existe(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_no_filtra_si_el_email_existe_o_no(): void
    {
        Notification::fake();

        // Tanto si el email existe como si no, la respuesta debe ser la misma
        // para no filtrar que cuentas estan registradas en la aplicacion.
        $this->post(route('password.email'), [
            'email' => 'no-existe@example.com',
        ])->assertSessionHas('status');

        $user = User::factory()->create();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status');
    }

    public function test_muestra_el_formulario_de_nueva_contrasena_con_token(): void
    {
        $user = User::factory()->create();

        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Restablecer contrasena');
    }

    public function test_cambia_la_contrasena_con_un_token_valido(): void
    {
        $user = User::factory()->create();

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva-segura',
            'password_confirmation' => 'nueva-segura',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('nueva-segura', $user->password));
    }

    public function test_rechaza_un_token_invalido(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'token-invalido',
            'email' => $user->email,
            'password' => 'nueva-segura',
            'password_confirmation' => 'nueva-segura',
        ])->assertSessionHasErrors('email');
    }
}
