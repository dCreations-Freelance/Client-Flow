<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Verifica el rate limiting en login y registro (auditoria H-02).
 *
 *  - Login: 5 intentos/min por IP.
 *  - Registro: 3 intentos/hora por IP.
 *
 * Importante: `Cache::flush()` en `setUp` garantiza que el rate
 * limiter arranca de cero en cada test. Sin esto, el array cache
 * en memoria de PHP acumularia los hits de tests previos dentro
 * de la misma corrida de phpunit.
 *
 * En el test de registro usamos contrasena corta para que la
 * validacion falle ANTES de que el controller cree al usuario
 * (y por tanto sin auto-login). Asi el rate limiter es el unico
 * responsable del bloqueo a partir del cuarto intento.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_bloquea_despues_de_5_intentos_en_un_minuto(): void
    {
        $user = User::factory()->client()->create([
            'password' => Hash::make('contrasena-correcta'),
        ]);

        // 5 intentos fallidos son los que el rate limiter permite.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'contrasena-equivocada',
            ])->assertSessionHasErrors('email');
        }

        // El 6 debe quedar bloqueado con 429.
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'contrasena-equivocada',
        ])->assertStatus(429);
    }

    public function test_registro_bloquea_despues_de_3_intentos_en_una_hora(): void
    {
        // 3 intentos fallidos. Usamos contrasena corta (1 caracter) para
        // que la validacion falle ANTES de crear el usuario. Asi no hay
        // auto-login y cada POST consume exactamente 1 hit del limiter.
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('register'), [
                'name' => 'Test '.$i,
                'email' => 'test'.$i.'@example.com',
                'password' => 'x',
                'password_confirmation' => 'x',
            ])->assertSessionHasErrors('password');
        }

        // El 4 debe quedar bloqueado.
        $this->post(route('register'), [
            'name' => 'Test 4',
            'email' => 'test4@example.com',
            'password' => 'x',
            'password_confirmation' => 'x',
        ])->assertStatus(429);
    }

    public function test_login_redisponible_despues_de_flush_de_cache(): void
    {
        $user = User::factory()->client()->create([
            'password' => Hash::make('contrasena-correcta'),
        ]);

        // Agotar el rate limiter.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'equivocada',
            ]);
        }
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'equivocada',
        ])->assertStatus(429);

        // Tras flush (simula paso del tiempo / reinicio), el login
        // vuelve a estar disponible. Esto es un test del mecanismo
        // de cache, no de la ventana de tiempo real.
        Cache::flush();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'contrasena-correcta',
        ])->assertRedirect();
    }

    public function test_get_del_formulario_no_consume_rate_limiter(): void
    {
        // Hacer 10 GETs al formulario de login. Ninguno debe consumir
        // el rate limiter (que solo aplica a POSTs).
        for ($i = 0; $i < 10; $i++) {
            $this->get(route('login'))->assertOk();
        }

        // Despues de los 10 GETs, un POST con credenciales malas debe
        // seguir funcionando (no estar bloqueado por los GETs).
        $user = User::factory()->client()->create([
            'password' => Hash::make('contrasena-correcta'),
        ]);
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'equivocada',
        ])->assertSessionHasErrors('email');
    }
}
