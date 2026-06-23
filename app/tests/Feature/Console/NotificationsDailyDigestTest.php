<?php

namespace Tests\Feature\Console;

use App\Enums\NotificationEvent;
use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests del comando `notifications:daily-digest`.
 *
 * Cubre: envio al usuario con preferencia activa, exclusion por
 * opt-out, salvaguarda anti-duplicados, modo dry-run.
 */
class NotificationsDailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_el_digest_a_usuarios_con_preferencia_email_activa(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::DailyDigest)
            ->create();

        $this->artisan('notifications:daily-digest')->assertSuccessful();

        Notification::assertSentTo($user, \App\Notifications\DailyDigest::class);
    }

    public function test_no_envia_a_usuarios_sin_preferencia_activa(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();
        // Sin preferencias, default in_app=false email=true.
        // Pero no hemos sembrado la fila (simulando usuario
        // creado antes de la fase). El helper `preferenceFor`
        // devuelve defaults: email=true. Asi que se envia.
        // Si queremos excluirlo, basta con tener email=false.
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::DailyDigest)
            ->inAppOnly()
            ->create();

        $this->artisan('notifications:daily-digest')->assertSuccessful();

        Notification::assertNotSentTo($user, \App\Notifications\DailyDigest::class);
    }

    public function test_sella_last_digest_sent_at_despues_de_enviar(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::DailyDigest)
            ->create();

        $this->artisan('notifications:daily-digest')->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->last_digest_sent_at);
    }

    public function test_no_re_envia_si_se_sello_en_las_ultimas_18h(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::DailyDigest)
            ->create();
        $user->forceFill(['last_digest_sent_at' => now()->subHours(2)])->save();

        $this->artisan('notifications:daily-digest')->assertSuccessful();

        Notification::assertNotSentTo($user, \App\Notifications\DailyDigest::class);
    }

    public function test_re_envia_si_pasaron_las_18h_desde_el_ultimo(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::DailyDigest)
            ->create();
        $user->forceFill(['last_digest_sent_at' => now()->subHours(20)])->save();

        $this->artisan('notifications:daily-digest')->assertSuccessful();

        Notification::assertSentTo($user, \App\Notifications\DailyDigest::class);
    }

    public function test_dry_run_no_envia_ni_sella(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::DailyDigest)
            ->create();

        $this->artisan('notifications:daily-digest --dry-run')->assertSuccessful();

        Notification::assertNotSentTo($user, \App\Notifications\DailyDigest::class);

        $user->refresh();
        $this->assertNull($user->last_digest_sent_at);
    }
}
