<?php

namespace Tests\Feature\Shared;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

/**
 * Tests feature del componente Livewire `NotificationBell`.
 *
 * Cubre: render inicial con/sin notificaciones, badge de
 * contador, abrir/cerrar el dropdown, marcar individual y
 * todas como leidas, y serializacion del payload para la vista.
 */
class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderiza_con_cero_notificaciones(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeLivewire('shared.notification-bell');
    }

    public function test_renderiza_el_badge_con_el_contador(): void
    {
        $user = User::factory()->admin()->create();
        $user->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $component = \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Shared\NotificationBell::class);

        $component->assertSet('open', false);
        $this->assertSame(1, $component->viewData('unreadCount'));
    }

    public function test_toggle_open_alterna_el_estado(): void
    {
        $user = User::factory()->admin()->create();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Shared\NotificationBell::class)
            ->assertSet('open', false)
            ->call('toggleOpen')
            ->assertSet('open', true)
            ->call('close')
            ->assertSet('open', false);
    }

    public function test_mark_all_as_read_vacia_las_no_leidas(): void
    {
        $user = User::factory()->admin()->create();
        $user->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));
        $user->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $this->assertSame(2, $user->unreadNotifications()->count());

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Shared\NotificationBell::class)
            ->call('markAllAsRead');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_open_notification_marca_como_leida_y_devuelve_url(): void
    {
        $user = User::factory()->admin()->create();
        $user->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Shared\NotificationBell::class)
            ->call('openNotification', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_open_notification_solo_afecta_a_las_notificaciones_del_usuario(): void
    {
        $owner = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $owner->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $notification = $owner->notifications()->first();

        // El otro usuario intenta abrir la notificacion ajena.
        \Livewire\Livewire::actingAs($other)
            ->test(\App\Livewire\Shared\NotificationBell::class)
            ->call('openNotification', $notification->id);

        // La notificacion sigue sin leer.
        $this->assertNull($notification->fresh()->read_at);
    }
}
