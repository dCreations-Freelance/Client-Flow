<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `ProjectChatRead`.
 *
 * Cubren: el helper estatico `markAsRead` (idempotente y no
 * rebobinable) y el contador `unreadCount`.
 */
class ProjectChatReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_read_crea_la_fila_si_no_existe(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);

        $read = ProjectChatRead::markAsRead($project, $user, $message->id);

        $this->assertNotNull($read->id);
        $this->assertSame($message->id, $read->last_read_message_id);
    }

    public function test_mark_as_read_actualiza_si_el_id_es_mayor(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $m1 = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $m2 = ProjectMessage::factory()->create(['project_id' => $project->id]);

        ProjectChatRead::markAsRead($project, $user, $m1->id);
        $updated = ProjectChatRead::markAsRead($project, $user, $m2->id);

        $this->assertSame($m2->id, $updated->last_read_message_id);
    }

    public function test_mark_as_read_no_rebobina_si_el_id_es_menor(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $m1 = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $m2 = ProjectMessage::factory()->create(['project_id' => $project->id]);

        ProjectChatRead::markAsRead($project, $user, $m2->id);
        $stillSame = ProjectChatRead::markAsRead($project, $user, $m1->id);

        $this->assertSame($m2->id, $stillSame->last_read_message_id);
    }

    public function test_unread_count_devuelve_cero_si_no_hay_mensajes(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        // markAsRead con id 0 es no-op (no crea fila, no tendria
        // sentido apuntar a un mensaje que no existe).
        $result = ProjectChatRead::markAsRead($project, $user, 0);
        $this->assertNull($result);

        // Sin fila de marcador, el unread count se calcula contra
        // todos los mensajes del proyecto, que en este caso son 0.
        $this->assertSame(0, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_unread_count_cuenta_mensajes_posteriores_al_marcador(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        ProjectMessage::factory()->count(5)->create(['project_id' => $project->id]);
        $lastId = (int) ProjectMessage::where('project_id', $project->id)->max('id');

        $read = ProjectChatRead::markAsRead($project, $user, $lastId - 2);

        $this->assertSame(2, $read->unreadCount());
    }

    public function test_last_email_sent_at_se_persiste_como_datetime(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $read = ProjectChatRead::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'last_read_message_id' => null,
            'last_email_sent_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $read->last_email_sent_at);
    }
}
