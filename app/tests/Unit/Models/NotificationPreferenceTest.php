<?php

namespace Tests\Unit\Models;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `NotificationPreference`.
 *
 * Cubren: casts a enum y booleanos, relacion `user`, scopes
 * (`forUser`, `inAppEnabled`, `emailEnabled`) y helpers
 * (`isInAppEnabled`, `isEmailEnabled`, `isFullyDisabled`).
 */
class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_se_castea_al_enum(): void
    {
        $pref = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::TaskAssigned)
            ->create();

        $this->assertInstanceOf(NotificationEvent::class, $pref->event);
        $this->assertSame(NotificationEvent::TaskAssigned, $pref->event);
    }

    public function test_in_app_y_email_se_castean_a_bool(): void
    {
        $pref = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->inAppOnly()
            ->create();

        $this->assertTrue($pref->in_app);
        $this->assertFalse($pref->email);
    }

    public function test_relacion_user_devuelve_el_usuario_dueno(): void
    {
        $user = User::factory()->create();
        $pref = NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::NewMessage)
            ->create();

        $this->assertTrue($pref->user->is($user));
    }

    public function test_helper_is_in_app_enabled(): void
    {
        $enabled = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->create();
        $disabled = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->emailOnly()
            ->create();

        $this->assertTrue($enabled->isInAppEnabled());
        $this->assertFalse($disabled->isInAppEnabled());
    }

    public function test_helper_is_email_enabled(): void
    {
        $enabled = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->create();
        $disabled = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->inAppOnly()
            ->create();

        $this->assertTrue($enabled->isEmailEnabled());
        $this->assertFalse($disabled->isEmailEnabled());
    }

    public function test_is_fully_disabled_solo_es_true_con_ambos_canales_apagados(): void
    {
        $both = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->disabled()
            ->create();
        $inApp = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->inAppOnly()
            ->create();
        $email = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->emailOnly()
            ->create();
        $full = NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->create();

        $this->assertTrue($both->isFullyDisabled());
        $this->assertFalse($inApp->isFullyDisabled());
        $this->assertFalse($email->isFullyDisabled());
        $this->assertFalse($full->isFullyDisabled());
    }

    public function test_scope_for_user_filtra_por_usuario(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        NotificationPreferenceFactory::new()->forUser($user)->count(2)->create();
        NotificationPreferenceFactory::new()->forUser($other)->count(3)->create();

        $rows = NotificationPreference::query()->forUser($user->id)->get();

        $this->assertCount(2, $rows);
        $rows->each(fn ($r) => $this->assertSame($user->id, $r->user_id));
    }

    public function test_scope_in_app_enabled_filtra_correctamente(): void
    {
        NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->inAppOnly()
            ->create();
        NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::TaskAssigned)
            ->emailOnly()
            ->create();

        $rows = NotificationPreference::query()->inAppEnabled()->get();

        $this->assertCount(1, $rows);
        $this->assertSame(NotificationEvent::NewMessage, $rows->first()->event);
    }

    public function test_scope_email_enabled_filtra_correctamente(): void
    {
        NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::NewMessage)
            ->inAppOnly()
            ->create();
        NotificationPreferenceFactory::new()
            ->forEvent(NotificationEvent::TaskAssigned)
            ->emailOnly()
            ->create();

        $rows = NotificationPreference::query()->emailEnabled()->get();

        $this->assertCount(1, $rows);
        $this->assertSame(NotificationEvent::TaskAssigned, $rows->first()->event);
    }
}
