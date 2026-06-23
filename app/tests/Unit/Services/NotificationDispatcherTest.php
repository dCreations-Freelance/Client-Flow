<?php

namespace Tests\Unit\Services;

use App\Enums\NotificationEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Services\Notifications\NotificationDispatcher;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests del `NotificationDispatcher`.
 *
 * Cubre los cuatro escenarios clave:
 * 1. Usuario sin preferencias (usa defaults del enum).
 * 2. Usuario con in-app activado y email desactivado.
 * 3. Usuario con ambos canales desactivados (no se envia nada).
 * 4. dispatchToMany itera correctamente respetando opt-outs.
 * 5. dispatchToAddress funciona para destinatarios anonimos.
 */
class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_por_ambos_canales_con_defaults_del_enum(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        $actor = User::factory()->admin()->create();

        $sent = NotificationDispatcher::dispatch(
            $user,
            new TaskAssigned($task, $project, $actor),
            NotificationEvent::TaskAssigned,
        );

        $this->assertTrue($sent);
        Notification::assertSentTo(
            $user,
            TaskAssigned::class,
            function (TaskAssigned $notification, $channels, $notifiable) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true);
            },
        );
    }

    public function test_no_envia_nada_si_ambos_canales_estan_desactivados(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::TaskAssigned)
            ->disabled()
            ->create();

        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        $actor = User::factory()->admin()->create();

        $sent = NotificationDispatcher::dispatch(
            $user,
            new TaskAssigned($task, $project, $actor),
            NotificationEvent::TaskAssigned,
        );

        $this->assertFalse($sent);
        Notification::assertNotSentTo($user, TaskAssigned::class);
    }

    public function test_solo_envia_por_canal_in_app_si_email_esta_desactivado(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::TaskAssigned)
            ->inAppOnly()
            ->create();

        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        $actor = User::factory()->admin()->create();

        NotificationDispatcher::dispatch(
            $user,
            new TaskAssigned($task, $project, $actor),
            NotificationEvent::TaskAssigned,
        );

        Notification::assertSentTo(
            $user,
            TaskAssigned::class,
            function ($notification, $channels, $notifiable) {
                return in_array('database', $channels, true)
                    && ! in_array('mail', $channels, true);
            },
        );
    }

    public function test_solo_envia_por_email_si_in_app_esta_desactivado(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        NotificationPreferenceFactory::new()
            ->forUser($user)
            ->forEvent(NotificationEvent::TaskAssigned)
            ->emailOnly()
            ->create();

        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        $actor = User::factory()->admin()->create();

        NotificationDispatcher::dispatch(
            $user,
            new TaskAssigned($task, $project, $actor),
            NotificationEvent::TaskAssigned,
        );

        Notification::assertSentTo(
            $user,
            TaskAssigned::class,
            function ($notification, $channels, $notifiable) {
                return ! in_array('database', $channels, true)
                    && in_array('mail', $channels, true);
            },
        );
    }

    public function test_dispatch_to_many_respeta_opt_out_individual(): void
    {
        Notification::fake();

        $optedOut = User::factory()->create();
        NotificationPreferenceFactory::new()
            ->forUser($optedOut)
            ->forEvent(NotificationEvent::NewMessage)
            ->disabled()
            ->create();

        $enabled = User::factory()->create();

        $project = Project::factory()->create();
        $actor = User::factory()->admin()->create();

        $sent = NotificationDispatcher::dispatchToMany(
            [$optedOut, $enabled],
            new \App\Notifications\NewProjectMessage(
                \App\Models\ProjectMessage::factory()->create(['project_id' => $project->id]),
                $project,
                $actor,
            ),
            NotificationEvent::NewMessage,
        );

        $this->assertSame(1, $sent);
    }

    public function test_dispatch_to_address_envia_a_email_anonimo(): void
    {
        Notification::fake();

        $notification = new DispatchToAddressFakeNotification;

        NotificationDispatcher::dispatchToAddress(
            'externo@example.com',
            'Persona Externa',
            $notification,
        );

        Notification::assertSentTo(
            new AnonymousNotifiable,
            DispatchToAddressFakeNotification::class,
        );
    }
}

/**
 * Notificacion anonima usada por el test `test_dispatch_to_address_envia_a_email_anonimo`.
 *
 * Se declara como clase con nombre (no anonima) porque el fake de
 * notificaciones de Laravel indexa las notificaciones enviadas por
 * `get_class($notification)` y `assertSentTo` espera poder resolver
 * la clase como string. Con clases anonimas PHP no permite
 * referenciarlas por nombre.
 */
class DispatchToAddressFakeNotification extends \Illuminate\Notifications\Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->line('hola');
    }
}
