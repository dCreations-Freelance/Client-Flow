<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature de los endpoints de la campana portal. Mismas
 * aserciones que el test del admin pero con la URL del portal.
 */
class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_devuelve_las_notificaciones_del_cliente(): void
    {
        $client = User::factory()->client()->create();
        $client->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $this->actingAs($client)
            ->getJson(route('portal.notifications.inbox'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_mark_read_marca_una_notificacion_como_leida(): void
    {
        $client = User::factory()->client()->create();
        $client->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $notification = $client->notifications()->first();

        $this->actingAs($client)
            ->post(route('portal.notifications.read', $notification->id));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_vacia_todas_las_no_leidas(): void
    {
        $client = User::factory()->client()->create();
        $client->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $this->actingAs($client)
            ->post(route('portal.notifications.read-all'));

        $this->assertSame(0, $client->fresh()->unreadNotifications()->count());
    }
}
