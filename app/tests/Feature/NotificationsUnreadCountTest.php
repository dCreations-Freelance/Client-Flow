<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del endpoint `/api/notifications/unread-count`.
 *
 * Es el endpoint que `pwa.js` poll-ea cada 30s para alimentar
 * las notificaciones client-side de la PWA. Devuelve un JSON
 * compacto con los contadores que el SW necesita para decidir
 * si dispara una notificacion del sistema.
 */
class NotificationsUnreadCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_es_redirigido_a_login(): void
    {
        $this->assertGuest();

        $this->get(route('api.notifications.unread-count'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_autenticado_recibe_contadores_en_cero(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'))
            ->assertOk();

        $response->assertJson([
            'messages' => 0,
            'tasks' => 0,
            'total' => 0,
        ]);
    }

    public function test_cliente_autenticado_recibe_contadores_en_cero(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson(route('api.notifications.unread-count'))
            ->assertOk()
            ->assertJson([
                'messages' => 0,
                'tasks' => 0,
                'total' => 0,
            ]);
    }

    public function test_cuenta_mensajes_no_leidos_en_proyectos_del_usuario(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        // 3 mensajes en el proyecto y el admin ya leyo hasta el 1.
        ProjectMessage::factory()->count(3)->create(['project_id' => $project->id]);
        ProjectChatRead::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'last_read_message_id' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'));

        // 2 mensajes restantes (id > 1).
        $response->assertJson([
            'messages' => 2,
            'tasks' => 0,
            'total' => 2,
        ]);
    }

    public function test_cuenta_tareas_asignadas_pendientes(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $column = $project->columns()->first() ?? $project->columns()->create([
            'name' => 'Por hacer',
            'slug' => 'todo',
            'position' => 1,
        ]);

        // 2 tareas pendientes asignadas al admin.
        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea 1',
            'assignee_id' => $admin->id,
            'created_by' => $admin->id,
        ]);
        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea 2',
            'assignee_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        // 1 tarea ya completada (no debe contar).
        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea 3',
            'assignee_id' => $admin->id,
            'created_by' => $admin->id,
            'completed_at' => now(),
        ]);

        // 1 tarea asignada a otro usuario (no debe contar).
        $other = User::factory()->admin()->create();
        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea 4',
            'assignee_id' => $other->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'));

        $response->assertJson([
            'tasks' => 2,
        ]);
    }

    public function test_suma_total_combina_mensajes_y_tareas(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $messages = ProjectMessage::factory()->count(2)->create(['project_id' => $project->id]);
        // Marcamos el primer mensaje como leido, asi queda 1 sin leer.
        ProjectChatRead::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'last_read_message_id' => $messages->first()->id,
        ]);

        $column = $project->columns()->first() ?? $project->columns()->create([
            'name' => 'Por hacer',
            'slug' => 'todo',
            'position' => 1,
        ]);
        Task::create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Tarea',
            'assignee_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'));

        $response->assertJson([
            'messages' => 1,
            'tasks' => 1,
            'total' => 2,
        ]);
    }

    public function test_devuelve_urls_adecuadas_segun_rol(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        $adminUrl = $this->actingAs($admin)
            ->getJson(route('api.notifications.unread-count'))
            ->json('messages_url');

        $clientUrl = $this->actingAs($client)
            ->getJson(route('api.notifications.unread-count'))
            ->json('messages_url');

        $this->assertSame(route('admin.dashboard'), $adminUrl);
        $this->assertSame(route('portal.dashboard'), $clientUrl);
    }
}
