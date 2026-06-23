<?php

namespace Tests\Unit\Models;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del helper `User::preferenceFor` introducido en la
 * fase transversal de Notificaciones.
 *
 * Cubre los tres casos clave:
 * 1. Devuelve la fila persistida si existe.
 * 2. Devuelve una instancia virtual con los defaults del enum si
 *    no existe.
 * 3. La instancia virtual NO queda persistida como efecto
 *    secundario.
 */
class UserNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_la_fila_persistida_si_existe(): void
    {
        $user = User::factory()->create();

        $persisted = NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::NewMessage)
            ->emailOnly()
            ->create();

        $result = $user->preferenceFor(NotificationEvent::NewMessage);

        $this->assertTrue($result->exists);
        $this->assertTrue($result->is($persisted));
        $this->assertFalse($result->in_app);
        $this->assertTrue($result->email);
    }

    public function test_devuelve_defaults_del_enum_si_no_existe_fila(): void
    {
        $user = User::factory()->create();

        $result = $user->preferenceFor(NotificationEvent::NewMessage);

        // Para NewMessage el default es in_app=true, email=true.
        $this->assertFalse($result->exists);
        $this->assertTrue($result->in_app);
        $this->assertTrue($result->email);
    }

    public function test_devuelve_default_especifico_del_evento(): void
    {
        $user = User::factory()->create();

        $org = $user->preferenceFor(NotificationEvent::OrganizationInvitation);
        $this->assertFalse($org->in_app);
        $this->assertTrue($org->email);

        $digest = $user->preferenceFor(NotificationEvent::DailyDigest);
        $this->assertFalse($digest->in_app);
        $this->assertTrue($digest->email);
    }

    public function test_no_persiste_la_preferencia_virtual_como_efecto_secundario(): void
    {
        $user = User::factory()->create();

        // Llamamos al helper dos veces: ninguna debe crear filas
        // en BD porque la instancia es transitoria.
        $user->preferenceFor(NotificationEvent::TaskAssigned);
        $user->preferenceFor(NotificationEvent::TaskAssigned);

        $this->assertSame(0, NotificationPreference::query()->count());
    }

    public function test_la_instancia_virtual_pertenece_al_usuario_consultado(): void
    {
        $user = User::factory()->create();

        $result = $user->preferenceFor(NotificationEvent::EventInvitation);

        $this->assertSame($user->id, $result->user_id);
    }
}
