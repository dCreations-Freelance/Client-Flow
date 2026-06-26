<?php

namespace Tests\Feature\Auth;

use App\Enums\OrganizationUserRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\OrganizationInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function createInvitationFor(string $email, Organization $organization): string
    {
        $service = app(OrganizationInvitationService::class);
        [$invitation, $rawToken] = $service->create(
            $organization,
            $email,
            OrganizationUserRole::Member,
            User::factory()->admin()->create(),
        );

        $this->assertNotEmpty($rawToken);

        return $rawToken;
    }

    public function test_muestra_formulario_de_aceptacion_a_visitantes(): void
    {
        $org = Organization::factory()->create();
        $token = $this->createInvitationFor('nuevo@example.com', $org);

        $this->get(route('invitation.accept', ['token' => $token]))
            ->assertOk()
            ->assertSee('Te han invitado a '.$org->name);
    }

    public function test_redirige_a_login_con_error_si_token_no_existe(): void
    {
        $this->get(route('invitation.accept', ['token' => 'no-existe']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_acepta_invitacion_y_crea_cuenta_si_usuario_no_existe(): void
    {
        $org = Organization::factory()->create();
        $token = $this->createInvitationFor('nuevo@example.com', $org);

        $this->post(route('invitation.accept', ['token' => $token]), [
            'name' => 'Persona Nueva',
            'password' => 'contrasena-segura',
            'password_confirmation' => 'contrasena-segura',
        ])->assertRedirect(route('portal.dashboard'));

        $user = User::where('email', 'nuevo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertTrue(Hash::check('contrasena-segura', $user->password));

        $this->assertTrue(
            $org->members()
                ->where('users.id', $user->id)
                ->wherePivot('role', OrganizationUserRole::Member->value)
                ->exists()
        );

        $this->assertNotNull(OrganizationInvitation::where('email', 'nuevo@example.com')->first()->accepted_at);
    }

    public function test_usuario_autenticado_con_email_correcto_acepta_sin_formulario(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['email' => 'mismo@example.com']);
        $token = $this->createInvitationFor('mismo@example.com', $org);

        $this->actingAs($user)
            ->get(route('invitation.accept', ['token' => $token]))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertTrue(
            $org->members()
                ->where('users.id', $user->id)
                ->exists()
        );
    }

    public function test_usuario_autenticado_con_otro_email_no_puede_aceptar(): void
    {
        $org = Organization::factory()->create();
        $token = $this->createInvitationFor('real@example.com', $org);

        $other = User::factory()->create(['email' => 'otro@example.com']);

        $this->actingAs($other)
            ->get(route('invitation.accept', ['token' => $token]))
            ->assertSessionHasErrors('email');

        $this->assertFalse(
            $org->members()->where('users.id', $other->id)->exists()
        );
    }

    public function test_no_se_puede_re_aceptar_una_invitacion_expirada(): void
    {
        $org = Organization::factory()->create();
        $service = app(OrganizationInvitationService::class);
        [$invitation, $rawToken] = $service->create(
            $org,
            'vencido@example.com',
            OrganizationUserRole::Member,
            User::factory()->admin()->create(),
        );

        $invitation->update(['expires_at' => now()->subDay()]);

        $this->get(route('invitation.accept', ['token' => $rawToken]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_no_se_puede_re_aceptar_una_invitacion_ya_aceptada(): void
    {
        $org = Organization::factory()->create();
        $token = $this->createInvitationFor('repetido@example.com', $org);

        // Primera aceptacion.
        $this->post(route('invitation.accept', ['token' => $token]), [
            'name' => 'Repetido',
            'password' => 'contrasena-segura',
            'password_confirmation' => 'contrasena-segura',
        ])->assertRedirect(route('portal.dashboard'));

        // Segundo intento.
        $this->get(route('invitation.accept', ['token' => $token]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    /**
     * La URL de invitacion contiene el token en claro. Sin la meta
     * `referrer: no-referrer`, cualquier recurso externo (CSS, JS, fuente,
     * analytics) recibira el token en la cabecera Referer. Auditado en M-05.
     */
    public function test_no_envia_referer_al_renderizar_la_invitacion(): void
    {
        $org = Organization::factory()->create();
        $token = $this->createInvitationFor('seguro@example.com', $org);

        $this->get(route('invitation.accept', ['token' => $token]))
            ->assertOk()
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
    }

    /**
     * El resto de pantallas auth (login, registro, reset) NO deben
     * llevar `no-referrer` para no romper analytics ni enlaces entrantes.
     */
    public function test_otras_pantallas_auth_no_llevan_no_referrer(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('name="referrer" content="no-referrer"', false);
    }
}
