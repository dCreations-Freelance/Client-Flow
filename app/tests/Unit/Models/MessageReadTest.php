<?php

namespace Tests\Unit\Models;

use App\Models\MessageRead;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del modelo `MessageRead`.
 *
 * Verifican el marcado masivo de mensajes como leidos y los
 * helpers de `ProjectMessage` que dependen de esta tabla.
 */
class MessageReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Marcar como leidos crea un registro por mensaje no leido.
     */
    public function test_mark_messages_as_read_crea_registros_para_mensajes_no_leidos(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->client()->create();
        $project->organization->members()->attach($user->id, ['role' => 'member']);

        $messageA = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $messageB = ProjectMessage::factory()->create(['project_id' => $project->id]);

        MessageRead::markMessagesAsRead($project, $user, $messageB->id);

        $this->assertTrue($messageA->isReadBy($user));
        $this->assertTrue($messageB->isReadBy($user));
        $this->assertDatabaseCount('message_reads', 2);
    }

    /**
     * No se duplican registros si un mensaje ya habia sido leido.
     */
    public function test_mark_messages_as_read_no_duplica_registros_existentes(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->client()->create();
        $project->organization->members()->attach($user->id, ['role' => 'member']);

        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);

        MessageRead::markMessagesAsRead($project, $user, $message->id);
        MessageRead::markMessagesAsRead($project, $user, $message->id);

        $this->assertDatabaseCount('message_reads', 1);
    }

    /**
     * Solo marca mensajes hasta el id indicado, no los posteriores.
     */
    public function test_mark_messages_as_read_respeta_el_limite_superior(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->client()->create();
        $project->organization->members()->attach($user->id, ['role' => 'member']);

        $messageA = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $messageB = ProjectMessage::factory()->create(['project_id' => $project->id]);

        MessageRead::markMessagesAsRead($project, $user, $messageA->id);

        $this->assertTrue($messageA->isReadBy($user));
        $this->assertFalse($messageB->isReadBy($user));
    }

    /**
     * Un upToMessageId menor o igual a cero no produce registros.
     */
    public function test_mark_messages_as_read_con_id_cero_es_no_op(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->client()->create();

        MessageRead::markMessagesAsRead($project, $user, 0);

        $this->assertDatabaseCount('message_reads', 0);
    }

    /**
     * `readByAnyoneElse` devuelve true si alguien distinto al
     * emisor ha leido el mensaje.
     */
    public function test_read_by_anyone_else_detecta_lectura_de_otro_usuario(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->admin()->create();
        $reader = User::factory()->client()->create();

        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $sender->id,
        ]);

        $this->assertFalse($message->readByAnyoneElse($sender));

        MessageRead::factory()->create([
            'message_id' => $message->id,
            'user_id' => $reader->id,
        ]);

        $this->assertTrue($message->readByAnyoneElse($sender));
    }

    /**
     * La lectura del propio emisor no cuenta como "visto por otro".
     */
    public function test_lectura_del_emisor_no_cuenta_como_visto_por_otro(): void
    {
        $project = Project::factory()->create();
        $sender = User::factory()->admin()->create();

        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $sender->id,
        ]);

        MessageRead::factory()->create([
            'message_id' => $message->id,
            'user_id' => $sender->id,
        ]);

        $this->assertFalse($message->readByAnyoneElse($sender));
    }
}
