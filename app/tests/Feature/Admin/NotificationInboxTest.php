<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature de los endpoints de la campana admin:
 * `notifications.inbox`, `notifications.read`, `notifications.read-all`.
 */
class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_devuelve_las_notificaciones_del_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.inbox'))
            ->assertOk()
            ->assertJsonStructure([
                'notifications',
                'unread_count',
            ])
            ->assertJsonPath('unread_count', 1);
    }

    public function test_mark_read_marca_una_notificacion_como_leida(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $notification = $admin->notifications()->first();
        $this->assertNull($notification->read_at);

        $this->actingAs($admin)
            ->post(route('admin.notifications.read', $notification->id));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_read_no_afecta_a_otro_usuario(): void
    {
        $owner = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $owner->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $notification = $owner->notifications()->first();

        $this->actingAs($other)
            ->post(route('admin.notifications.read', $notification->id));

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_vacia_todas_las_no_leidas(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));
        $admin->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $this->actingAs($admin)
            ->post(route('admin.notifications.read-all'));

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }
}
