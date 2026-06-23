<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la extension del endpoint
 * `/api/notifications/unread-count` con el campo `notifications`
 * (campana in-app).
 */
class NotificationsUnreadCountWithInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_incluye_el_contador_de_notificaciones_in_app(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $response = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'))
            ->assertOk();

        $response->assertJson([
            'notifications' => 1,
        ]);
    }

    public function test_incluye_notifications_url_segun_rol(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        $adminUrl = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'))
            ->json('notifications_url');
        $clientUrl = $this->actingAs($client)
            ->getJson(route('api.notifications.unread-count'))
            ->json('notifications_url');

        $this->assertSame(route('admin.dashboard'), $adminUrl);
        $this->assertSame(route('portal.dashboard'), $clientUrl);
    }

    public function test_suma_total_incluye_el_contador_in_app(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->notify(new \App\Notifications\TaskDueSoon(
            \App\Models\Task::factory()->create(),
            \App\Models\Project::factory()->create(),
        ));

        $response = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'))
            ->assertOk();

        $response->assertJsonPath('total', 1);
    }
}
