<?php

namespace Tests\Unit\Listeners;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del listener `CreateDefaultNotificationPreferences`.
 *
 * Verifica que cuando un usuario se registra se crean las seis
 * filas de preferencias con los defaults del enum. La accion la
 * dispara el evento `Registered` de Laravel.
 */
class CreateDefaultNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_las_seis_preferencias_por_defecto(): void
    {
        $user = User::factory()->create();

        event(new Registered($user));

        $this->assertSame(6, NotificationPreference::query()->count());
    }

    public function test_usa_los_defaults_del_enum_para_los_canales(): void
    {
        $user = User::factory()->create();

        event(new Registered($user));

        $message = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event', NotificationEvent::NewMessage->value)
            ->first();

        $this->assertNotNull($message);
        $this->assertTrue((bool) $message->in_app);
        $this->assertTrue((bool) $message->email);

        $digest = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event', NotificationEvent::DailyDigest->value)
            ->first();

        $this->assertNotNull($digest);
        $this->assertFalse((bool) $digest->in_app);
        $this->assertTrue((bool) $digest->email);
    }

    public function test_es_idempotente_si_se_dispara_dos_veces(): void
    {
        $user = User::factory()->create();

        event(new Registered($user));
        event(new Registered($user));

        // Aunque se dispare dos veces, solo debe haber 6 filas.
        $this->assertSame(6, NotificationPreference::query()->count());
    }

    public function test_respeta_una_persistencia_existente_del_usuario(): void
    {
        $user = User::factory()->create();

        // El usuario ya personalizo una preferencia.
        NotificationPreference::create([
            'user_id' => $user->id,
            'event' => NotificationEvent::NewMessage->value,
            'in_app' => false,
            'email' => false,
        ]);

        event(new Registered($user));

        $count = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event', NotificationEvent::NewMessage->value)
            ->count();

        $this->assertSame(1, $count, 'No debe duplicar la fila ya existente.');

        $row = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event', NotificationEvent::NewMessage->value)
            ->first();

        // Y debe mantener la personalizacion del usuario.
        $this->assertFalse((bool) $row->in_app);
        $this->assertFalse((bool) $row->email);
    }
}
