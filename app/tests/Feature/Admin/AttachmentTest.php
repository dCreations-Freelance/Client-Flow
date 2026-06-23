<?php

namespace Tests\Feature\Admin;

use App\Enums\MessageType;
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
 * Tests feature de la gestion de adjuntos en el panel admin.
 *
 * Cubre:
 * - Subida via HTTP de adjuntos de tareas y mensajes.
 * - Descarga con verificacion de policy y cross-project.
 * - Borrado por admin (regla estricta) y rechazo a clientes.
 * - Generacion automatica de mensaje de sistema al subir a tarea.
 * - Cascade: borrar tarea borra adjuntos.
 */
class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function adminAndProject(): array
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($admin->id, ['role' => 'owner']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);

        return [$admin, $project, $task];
    }

    // -----------------------------------------------------------------
    // Subida de adjuntos a tareas
    // -----------------------------------------------------------------

    public function test_admin_puede_subir_adjunto_a_tarea(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'contenido');

        $response = $this->actingAs($admin)
            ->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
                'attachment' => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'original_name' => 'doc.pdf',
            'user_id' => $admin->id,
        ]);
        $attachment = TaskAttachment::where('task_id', $task->id)->first();
        Storage::disk('local')->assertExists($attachment->disk_path);
    }

    public function test_subir_adjunto_genera_mensaje_de_sistema_en_el_chat(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'contenido');

        $this->actingAs($admin)
            ->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
                'attachment' => $file,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => MessageType::System->value,
        ]);
        $message = ProjectMessage::where('project_id', $project->id)
            ->where('type', MessageType::System->value)
            ->first();
        $this->assertStringContainsString($task->title, $message->content);
        $this->assertStringContainsString('adjunto', $message->content);
    }

    public function test_cliente_no_puede_subir_adjunto_a_tarea(): void
    {
        Storage::fake('local');
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $file = UploadedFile::fake()->create('doc.pdf');

        $this->actingAs($client)
            ->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
                'attachment' => $file,
            ])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_subida_rechaza_mime_no_permitido(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $file = UploadedFile::fake()->create('malware.exe');

        $response = $this->actingAs($admin)
            ->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
                'attachment' => $file,
            ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_subida_rechaza_archivo_demasiado_grande(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        // 11 MB supera el limite de 10 MB por defecto.
        $file = UploadedFile::fake()->create('grande.pdf', 11 * 1024);

        $response = $this->actingAs($admin)
            ->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
                'attachment' => $file,
            ]);

        $response->assertSessionHasErrors('attachment');
    }

    // -----------------------------------------------------------------
    // Descarga
    // -----------------------------------------------------------------

    public function test_admin_puede_descargar_adjunto_de_su_tarea(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'filename' => 'test.pdf',
            'original_name' => 'doc.pdf',
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.tasks.attachments.download', [$project, $task, $attachment]));

        $response->assertOk();
        $this->assertStringContainsString('doc.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_no_se_puede_descargar_adjunto_de_otra_tarea_del_mismo_proyecto(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $otherTask = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $attachment = TaskAttachment::factory()->create([
            'task_id' => $otherTask->id,
            'filename' => 'test.pdf',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.tasks.attachments.download', [$project, $task, $attachment]));

        $response->assertNotFound();
    }

    public function test_no_se_puede_descargar_adjunto_de_otro_proyecto(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $project1 = Project::factory()->create();
        $column1 = BoardColumn::factory()->create(['project_id' => $project1->id]);
        $task1 = Task::factory()->create(['project_id' => $project1->id, 'column_id' => $column1->id]);
        $project2 = Project::factory()->create();
        $column2 = BoardColumn::factory()->create(['project_id' => $project2->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id, 'column_id' => $column2->id]);
        $attachment = TaskAttachment::factory()->create(['task_id' => $task2->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.tasks.attachments.download', [$project1, $task1, $attachment]));

        $response->assertNotFound();
    }

    // -----------------------------------------------------------------
    // Borrado
    // -----------------------------------------------------------------

    public function test_admin_puede_eliminar_adjunto(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $attachment = TaskAttachment::factory()->create(['task_id' => $task->id]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $response = $this->actingAs($admin)
            ->delete(route('admin.projects.tasks.attachments.destroy', [$project, $task, $attachment]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->disk_path);
    }

    public function test_cliente_no_puede_eliminar_adjunto_de_tarea(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $attachment = TaskAttachment::factory()->create(['task_id' => $task->id]);

        $this->actingAs($client)
            ->delete(route('admin.projects.tasks.attachments.destroy', [$project, $task, $attachment]))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('task_attachments', ['id' => $attachment->id]);
    }

    // -----------------------------------------------------------------
    // Adjuntos de mensajes
    // -----------------------------------------------------------------

    public function test_admin_puede_eliminar_adjunto_de_mensaje(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'content' => 'hola',
        ]);
        $attachment = \App\Models\MessageAttachment::factory()->create([
            'message_id' => $message->id,
            'user_id' => $admin->id,
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $response = $this->actingAs($admin)
            ->delete(route('admin.projects.messages.attachments.destroy', [$project, $message, $attachment]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('message_attachments', ['id' => $attachment->id]);
    }

    public function test_borrar_ultimo_adjunto_de_mensaje_vacio_tambien_borra_el_mensaje(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'content' => '',
            'type' => MessageType::File,
        ]);
        $attachment = \App\Models\MessageAttachment::factory()->create([
            'message_id' => $message->id,
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $this->actingAs($admin)
            ->delete(route('admin.projects.messages.attachments.destroy', [$project, $message, $attachment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('message_attachments', ['id' => $attachment->id]);
        $this->assertDatabaseMissing('project_messages', ['id' => $message->id]);
    }

    public function test_borrar_adjunto_de_mensaje_con_texto_conserva_el_mensaje(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $message = ProjectMessage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'content' => 'hola, adjunto el doc',
        ]);
        $attachment = \App\Models\MessageAttachment::factory()->create([
            'message_id' => $message->id,
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $this->actingAs($admin)
            ->delete(route('admin.projects.messages.attachments.destroy', [$project, $message, $attachment]))
            ->assertRedirect();

        $this->assertDatabaseHas('project_messages', ['id' => $message->id, 'content' => 'hola, adjunto el doc']);
    }

    public function test_cliente_no_puede_eliminar_adjunto_de_mensaje(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $project->organization->members()->attach($client->id, ['role' => 'member']);
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $attachment = \App\Models\MessageAttachment::factory()->create(['message_id' => $message->id]);

        $this->actingAs($client)
            ->delete(route('admin.projects.messages.attachments.destroy', [$project, $message, $attachment]))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_puede_descargar_adjunto_de_mensaje(): void
    {
        Storage::fake('local');
        [$admin, $project, $task] = $this->adminAndProject();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $attachment = \App\Models\MessageAttachment::factory()->create([
            'message_id' => $message->id,
            'filename' => 'doc.pdf',
            'original_name' => 'doc.pdf',
        ]);
        Storage::disk('local')->put($attachment->disk_path, 'contenido');

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.messages.attachments.download', [$project, $message, $attachment]));

        $response->assertOk();
    }
}
