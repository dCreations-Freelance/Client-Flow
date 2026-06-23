<?php

namespace Tests\Unit\Models;

use App\Models\MessageAttachment;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del modelo `MessageAttachment`: fillable, casts,
 * accesores, scopes y helper `belongsToProject`.
 */
class MessageAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_crea_adjunto_con_datos_basicos(): void
    {
        $attachment = MessageAttachment::factory()->create();

        $this->assertNotNull($attachment->id);
        $this->assertNotNull($attachment->filename);
        $this->assertNotNull($attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
    }

    public function test_size_se_castea_a_integer(): void
    {
        $attachment = MessageAttachment::factory()->create(['size' => '8192']);

        $this->assertSame(8192, $attachment->size);
    }

    public function test_human_size_delega_en_task_attachment(): void
    {
        $attachment = MessageAttachment::factory()->create(['size' => 2_097_152]);

        $this->assertSame('2.0 MB', $attachment->human_size);
    }

    public function test_disk_path_incluye_proyecto_y_contexto_messages(): void
    {
        $project = Project::factory()->create();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $attachment = MessageAttachment::factory()->create([
            'message_id' => $message->id,
            'filename' => 'archivo.pdf',
        ]);

        $this->assertSame("clientflow/projects/{$project->id}/attachments/messages/archivo.pdf", $attachment->disk_path);
    }

    public function test_belongs_to_project_devuelve_true_si_coincide(): void
    {
        $project = Project::factory()->create();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $attachment = MessageAttachment::factory()->create(['message_id' => $message->id]);

        $this->assertTrue($attachment->belongsToProject($project->id));
        $this->assertFalse($attachment->belongsToProject($project->id + 1));
    }

    public function test_scope_for_message_filtra_por_mensaje(): void
    {
        $message1 = ProjectMessage::factory()->create();
        $message2 = ProjectMessage::factory()->create();

        MessageAttachment::factory()->count(2)->create(['message_id' => $message1->id]);
        MessageAttachment::factory()->count(3)->create(['message_id' => $message2->id]);

        $this->assertCount(2, MessageAttachment::forMessage($message1->id)->get());
        $this->assertCount(3, MessageAttachment::forMessage($message2->id)->get());
    }

    public function test_scope_for_project_filtra_via_join_con_mensajes(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $message1 = ProjectMessage::factory()->create(['project_id' => $project1->id]);
        $message2 = ProjectMessage::factory()->create(['project_id' => $project2->id]);

        MessageAttachment::factory()->create(['message_id' => $message1->id]);
        MessageAttachment::factory()->create(['message_id' => $message2->id]);

        $this->assertCount(1, MessageAttachment::forProject($project1->id)->get());
        $this->assertCount(1, MessageAttachment::forProject($project2->id)->get());
    }

    public function test_relaciones_con_message_y_user(): void
    {
        $user = User::factory()->create();
        $message = ProjectMessage::factory()->create();
        $attachment = MessageAttachment::factory()->create([
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame($message->id, $attachment->message->id);
        $this->assertSame($user->id, $attachment->user->id);
    }
}
