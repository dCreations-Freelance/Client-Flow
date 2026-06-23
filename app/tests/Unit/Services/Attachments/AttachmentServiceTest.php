<?php

namespace Tests\Unit\Services\Attachments;

use App\Models\MessageAttachment;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Services\Attachments\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests del `AttachmentService`: store/delete para tareas y
 * mensajes, contextos invalidos, borrado idempotente.
 */
class AttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttachmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->service = app(AttachmentService::class);
    }

    public function test_store_para_task_crea_fila_y_archivo_en_disco(): void
    {
        $project = Project::factory()->create();
        $column = \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'contenido de prueba');

        $attachment = $this->service->store(
            $project,
            AttachmentService::CONTEXT_TASK,
            $task->id,
            $file,
            $user,
        );

        $this->assertInstanceOf(TaskAttachment::class, $attachment);
        $this->assertSame($task->id, $attachment->task_id);
        $this->assertSame($user->id, $attachment->user_id);
        $this->assertSame('doc.pdf', $attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        Storage::disk('local')->assertExists($attachment->disk_path);
    }

    public function test_store_para_message_crea_fila_y_archivo_en_disco(): void
    {
        $project = Project::factory()->create();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('foto.png', 'PNGfalso');

        $attachment = $this->service->store(
            $project,
            AttachmentService::CONTEXT_MESSAGE,
            $message->id,
            $file,
            $user,
        );

        $this->assertInstanceOf(MessageAttachment::class, $attachment);
        $this->assertSame($message->id, $attachment->message_id);
        $this->assertSame($user->id, $attachment->user_id);
        $this->assertSame('foto.png', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->disk_path);
    }

    public function test_store_lanza_excepcion_si_contexto_es_invalido(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('doc.pdf');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contexto de adjunto invalido');

        $this->service->store(
            $project,
            'invalido',
            1,
            $file,
            $user,
        );
    }

    public function test_delete_task_attachment_borra_fila_y_archivo(): void
    {
        $project = Project::factory()->create();
        $column = \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'contenido');
        $attachment = $this->service->store(
            $project,
            AttachmentService::CONTEXT_TASK,
            $task->id,
            $file,
            $user,
        );

        $path = $attachment->disk_path;
        Storage::disk('local')->assertExists($path);

        $this->service->deleteTaskAttachment($attachment);

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
    }

    public function test_delete_message_attachment_borra_fila_y_archivo(): void
    {
        $project = Project::factory()->create();
        $message = ProjectMessage::factory()->create(['project_id' => $project->id]);
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('doc.pdf', 'contenido');
        $attachment = $this->service->store(
            $project,
            AttachmentService::CONTEXT_MESSAGE,
            $message->id,
            $file,
            $user,
        );

        $path = $attachment->disk_path;
        Storage::disk('local')->assertExists($path);

        $this->service->deleteMessageAttachment($attachment);

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('message_attachments', ['id' => $attachment->id]);
    }

    public function test_delete_es_idempotente_si_archivo_ya_no_existe(): void
    {
        $attachment = TaskAttachment::factory()->create();
        // Borramos manualmente el archivo antes de invocar delete.
        Storage::disk('local')->delete($attachment->disk_path);

        // No debe lanzar excepcion.
        $this->service->deleteTaskAttachment($attachment);

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
    }
}
