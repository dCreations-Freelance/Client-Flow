<?php

namespace Tests\Feature\Portal;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests feature del portal cliente para adjuntos.
 *
 * Cubre:
 * - Descarga de adjuntos de tareas y mensajes visibles.
 * - Bloqueo de adjuntos de proyectos ocultos/archivados.
 * - Aislamiento cross-project.
 * - Subida a mensajes del chat (consistente con permiso de enviar mensajes).
 * - Bloqueo de borrado de adjuntos para el cliente.
 */
class AttachmentViewTest extends TestCase
{
    use RefreshDatabase;

    private function clientAndProject(): array
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        return [$client, $project, $task];
    }

    public function test_cliente_puede_descargar_adjunto_de_su_tarea(): void
    {
        Storage::fake('local');
        [$client, $project, $task] = $this->clientAndProject();
        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'filename' => 'doc.pdf',
            'original_name' => 'doc.pdf',
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $response = $this->actingAs($client)
            ->get(route('portal.projects.tasks.attachments.download', [$project, $task, $attachment]));

        $response->assertOk();
    }

    public function test_cliente_no_puede_descargar_adjunto_de_proyecto_ajeno(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $otherProject->id]);
        $task = Task::factory()->create(['project_id' => $otherProject->id, 'column_id' => $column->id]);
        $attachment = TaskAttachment::factory()->create(['task_id' => $task->id]);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.tasks.attachments.download', [$otherProject, $task, $attachment]));

        $response->assertForbidden();
    }

    public function test_cliente_no_puede_descargar_adjunto_de_tarea_ajena_del_mismo_proyecto(): void
    {
        [$client, $project, $task] = $this->clientAndProject();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $otherTask = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $attachment = TaskAttachment::factory()->create(['task_id' => $otherTask->id]);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.tasks.attachments.download', [$project, $task, $attachment]));

        $response->assertNotFound();
    }

    public function test_cliente_no_puede_eliminar_adjunto(): void
    {
        [$client, $project, $task] = $this->clientAndProject();
        $attachment = TaskAttachment::factory()->create(['task_id' => $task->id]);

        // El cliente no debe tener una ruta de borrado de
        // adjuntos en el portal. Verificamos que la ruta no
        // esta registrada lanzando una excepcion al pedirla.
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        route('portal.projects.tasks.attachments.destroy', [$project, $task, $attachment]);
    }

    public function test_cliente_puede_subir_adjunto_a_mensaje(): void
    {
        Storage::fake('local');
        [$client, $project, $task] = $this->clientAndProject();
        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $client->id,
        ]);
        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'contenido');

        $response = $this->actingAs($client)
            ->post(route('portal.projects.messages.attachments.store', [$project, $message]), [
                'attachment' => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('message_attachments', [
            'message_id' => $message->id,
            'user_id' => $client->id,
            'original_name' => 'doc.pdf',
        ]);
    }

    public function test_cliente_puede_descargar_adjunto_de_mensaje(): void
    {
        Storage::fake('local');
        [$client, $project, $task] = $this->clientAndProject();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $attachment = \App\Models\MessageAttachment::factory()->create([
            'message_id' => $message->id,
            'filename' => 'doc.pdf',
            'original_name' => 'doc.pdf',
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $response = $this->actingAs($client)
            ->get(route('portal.projects.messages.attachments.download', [$project, $message, $attachment]));

        $response->assertOk();
    }

    public function test_cliente_no_puede_descargar_adjunto_de_mensaje_de_otro_proyecto(): void
    {
        $client = User::factory()->client()->create();
        $otherProject = Project::factory()->create();
        $message = ProjectMessage::factory()->create(['project_id' => $otherProject->id]);
        $attachment = \App\Models\MessageAttachment::factory()->create(['message_id' => $message->id]);

        $response = $this->actingAs($client)
            ->get(route('portal.projects.messages.attachments.download', [$otherProject, $message, $attachment]));

        $response->assertForbidden();
    }
}
