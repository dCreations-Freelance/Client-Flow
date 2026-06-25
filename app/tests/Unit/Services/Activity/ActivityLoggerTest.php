<?php

namespace Tests\Unit\Services\Activity;

use App\Enums\ActivityType;
use App\Enums\DocumentVisibility;
use App\Enums\MessageType;
use App\Enums\ProjectStatus;
use App\Models\ActivityLog;
use App\Models\BoardColumn;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del servicio `ActivityLogger`.
 *
 * Cada metodo del servicio hace doble escritura: persiste en
 * `activity_log` (lo que alimenta el feed) y, cuando aplica,
 * delega en `ProjectActivityLogger` para mantener el system
 * message en el chat.
 *
 * Los tests verifican:
 *
 *  1. La entrada en `activity_log` se crea con el `type`,
 *     `description`, `user_id`, `project_id`,
 *     `subject_type`, `subject_id` y `properties` correctos.
 *  2. Cuando el metodo tiene su contraparte en
 *     `ProjectActivityLogger` (los que afectan al chat), se
 *     crea un `ProjectMessage::system` con el formato
 *     esperado.
 *  3. Cuando el metodo NO delega al chat (documentos
 *     privados, task_updated, member_added, etc.), no se
 *     crea system message.
 */
class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    private function logger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }

    private function makeProjectWithColumn(): array
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);

        return [$project, $column];
    }

    // -----------------------------------------------------------------
    // Tareas
    // -----------------------------------------------------------------

    public function test_task_created_persiste_en_activity_log_y_delega_al_chat(): void
    {
        [$project, $column] = $this->makeProjectWithColumn();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Implementar login',
        ]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->taskCreated($project, $task, $actor);

        $this->assertInstanceOf(ActivityLog::class, $entry);
        $this->assertSame(ActivityType::TaskCreated, $entry->type);
        $this->assertSame($actor->id, $entry->user_id);
        $this->assertSame($project->id, $entry->project_id);
        $this->assertSame($task->id, $entry->subject_id);
        $this->assertSame(Task::class, $entry->subject_type);
        $this->assertStringContainsString('Daniel', $entry->description);
        $this->assertStringContainsString('Implementar login', $entry->description);

        // El chat recibe un system message con el formato
        // establecido por `ProjectActivityLogger::taskCreated`.
        $chatMessage = ProjectMessage::where('project_id', $project->id)
            ->where('type', MessageType::System)
            ->first();
        $this->assertNotNull($chatMessage);
        $this->assertStringContainsString('Implementar login', $chatMessage->content);
    }

    public function test_task_completed_persiste_y_delega(): void
    {
        [$project, $column] = $this->makeProjectWithColumn();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create(['name' => 'Lucia']);

        $entry = $this->logger()->taskCompleted($project, $task, $actor);

        $this->assertSame(ActivityType::TaskCompleted, $entry->type);
        $this->assertStringContainsString('Lucia', $entry->description);
        $this->assertStringContainsString('completo', $entry->description);
    }

    public function test_task_reopened_persiste_y_delega(): void
    {
        [$project, $column] = $this->makeProjectWithColumn();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->taskReopened($project, $task, $actor);

        $this->assertSame(ActivityType::TaskReopened, $entry->type);
        $this->assertStringContainsString('re-abrio', $entry->description);
    }

    public function test_task_moved_persiste_columna_origen_y_destino(): void
    {
        [$project, $columnA] = $this->makeProjectWithColumn();
        $columnB = BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'En curso', 'slug' => 'in_progress']);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $columnA->id]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->taskMoved($project, $task, $columnB, $columnA, $actor);

        $this->assertSame(ActivityType::TaskMoved, $entry->type);
        $this->assertSame($columnA->slug, $entry->properties['from_column']);
        $this->assertSame($columnB->slug, $entry->properties['to_column']);
        $this->assertStringContainsString('En curso', $entry->description);
    }

    public function test_task_updated_con_cambios_persiste_sin_delegar_al_chat(): void
    {
        [$project, $column] = $this->makeProjectWithColumn();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create();

        $chatCountBefore = ProjectMessage::where('project_id', $project->id)->count();

        $entry = $this->logger()->taskUpdated($project, $task, $actor, [
            'priority' => ['old' => 'low', 'new' => 'high'],
        ]);

        $this->assertNotNull($entry);
        $this->assertSame(ActivityType::TaskUpdated, $entry->type);
        $this->assertStringContainsString('prioridad', $entry->description);

        // taskUpdated NO genera system message en el chat.
        $this->assertSame($chatCountBefore, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_task_updated_sin_cambios_devuelve_null(): void
    {
        [$project, $column] = $this->makeProjectWithColumn();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create();

        $entry = $this->logger()->taskUpdated($project, $task, $actor, []);

        $this->assertNull($entry);
    }

    public function test_task_deleted_persiste_con_titulo_string(): void
    {
        [$project] = $this->makeProjectWithColumn();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->taskDeleted($project, 'Login con Google', $actor);

        $this->assertSame(ActivityType::TaskDeleted, $entry->type);
        $this->assertStringContainsString('Login con Google', $entry->description);
        $this->assertSame('Login con Google', $entry->properties['title']);
        $this->assertNull($entry->subject_id);
    }

    // -----------------------------------------------------------------
    // Documentos
    // -----------------------------------------------------------------

    public function test_document_created_persiste_sin_delegar_al_chat(): void
    {
        [$project] = $this->makeProjectWithColumn();
        $document = ProjectDocument::factory()->public()->create([
            'project_id' => $project->id,
            'title' => 'Manual',
        ]);
        $actor = User::factory()->create();

        $chatBefore = ProjectMessage::where('project_id', $project->id)->count();

        $entry = $this->logger()->documentCreated($project, $document, $actor);

        $this->assertSame(ActivityType::DocumentCreated, $entry->type);
        $this->assertSame('public', $entry->properties['visibility']);
        $this->assertSame($document->id, $entry->subject_id);

        // documentCreated NO genera system message (solo lo
        // hace `documentPublished`).
        $this->assertSame($chatBefore, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_document_published_persiste_y_delega_al_chat(): void
    {
        [$project] = $this->makeProjectWithColumn();
        $document = ProjectDocument::factory()->public()->create([
            'project_id' => $project->id,
            'title' => 'Manual',
        ]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->documentPublished($project, $document, $actor);

        $this->assertSame(ActivityType::DocumentPublished, $entry->type);
        $this->assertStringContainsString('publico', $entry->description);

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => MessageType::System->value,
        ]);
    }

    public function test_document_updated_persiste_sin_delegar(): void
    {
        [$project] = $this->makeProjectWithColumn();
        $document = ProjectDocument::factory()->private()->create(['project_id' => $project->id]);
        $actor = User::factory()->create();

        $chatBefore = ProjectMessage::where('project_id', $project->id)->count();

        $entry = $this->logger()->documentUpdated($project, $document, $actor);

        $this->assertSame(ActivityType::DocumentUpdated, $entry->type);
        $this->assertSame($chatBefore, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_document_deleted_persiste_con_visibility(): void
    {
        [$project] = $this->makeProjectWithColumn();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->documentDeleted(
            $project,
            'Notas internas',
            $actor,
            DocumentVisibility::Private,
        );

        $this->assertSame(ActivityType::DocumentDeleted, $entry->type);
        $this->assertSame('private', $entry->properties['visibility']);
        $this->assertSame('Notas internas', $entry->properties['title']);
    }

    // -----------------------------------------------------------------
    // Proyecto
    // -----------------------------------------------------------------

    public function test_project_created_persiste_sin_delegar_al_chat(): void
    {
        $project = Project::factory()->create();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $chatBefore = ProjectMessage::where('project_id', $project->id)->count();

        $entry = $this->logger()->projectCreated($project, $actor);

        $this->assertSame(ActivityType::ProjectCreated, $entry->type);
        $this->assertSame($chatBefore, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_status_changed_persiste_old_y_new(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->statusChanged(
            $project,
            ProjectStatus::Planning,
            ProjectStatus::InProgress,
            $actor,
        );

        $this->assertSame(ActivityType::StatusChanged, $entry->type);
        $this->assertSame('planning', $entry->properties['from']);
        $this->assertSame('in_progress', $entry->properties['to']);
    }

    public function test_project_archived_persiste_y_delega(): void
    {
        $project = Project::factory()->create();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->projectArchived($project, $actor);

        $this->assertSame(ActivityType::ProjectArchived, $entry->type);
        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => MessageType::System->value,
        ]);
    }

    public function test_template_applied_persiste_template_id(): void
    {
        $project = Project::factory()->create();
        $template = ProjectTemplate::factory()->create(['name' => 'Web basica']);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->templateApplied($project, $template, $actor);

        $this->assertSame(ActivityType::TemplateApplied, $entry->type);
        $this->assertSame($template->id, $entry->properties['template_id']);
        $this->assertStringContainsString('Web basica', $entry->description);
    }

    // -----------------------------------------------------------------
    // Miembros
    // -----------------------------------------------------------------

    public function test_member_added_persiste_sin_delegar(): void
    {
        $project = Project::factory()->create();
        $member = User::factory()->create(['name' => 'Lucia']);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $chatBefore = ProjectMessage::where('project_id', $project->id)->count();

        $entry = $this->logger()->memberAdded($project, $member, $actor);

        $this->assertSame(ActivityType::MemberAdded, $entry->type);
        $this->assertSame($member->id, $entry->properties['member_id']);
        $this->assertSame($chatBefore, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_member_removed_persiste_con_nombre_string(): void
    {
        $project = Project::factory()->create();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->memberRemoved($project, 'Lucia', $actor);

        $this->assertSame(ActivityType::MemberRemoved, $entry->type);
        $this->assertSame('Lucia', $entry->properties['member_name']);
    }

    // -----------------------------------------------------------------
    // Calendario
    // -----------------------------------------------------------------

    public function test_event_created_persiste_y_delega(): void
    {
        $project = Project::factory()->create();
        $event = CalendarEvent::factory()->forProject($project)->create(['title' => 'Reunion']);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->eventCreated($project, $event, $actor);

        $this->assertSame(ActivityType::EventCreated, $entry->type);
        $this->assertStringContainsString('Reunion', $entry->description);
        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => MessageType::System->value,
        ]);
    }

    public function test_event_deleted_persiste_titulo_como_string(): void
    {
        $project = Project::factory()->create();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->eventDeleted($project, 'Reunion cancelada', $actor);

        $this->assertSame(ActivityType::EventDeleted, $entry->type);
        $this->assertSame('Reunion cancelada', $entry->properties['title']);
    }

    // -----------------------------------------------------------------
    // Adjuntos y chat
    // -----------------------------------------------------------------

    public function test_attachment_uploaded_persiste_count_y_delega(): void
    {
        [$project, $column] = $this->makeProjectWithColumn();
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $entry = $this->logger()->attachmentUploadedToTask($project, $task, 3, $actor);

        $this->assertSame(ActivityType::AttachmentUploadedToTask, $entry->type);
        $this->assertSame(3, $entry->properties['count']);
        $this->assertStringContainsString('3 adjuntos', $entry->description);

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $project->id,
            'type' => MessageType::System->value,
        ]);
    }

    public function test_message_sent_persiste_sin_delegar_al_chat(): void
    {
        $project = Project::factory()->create();
        $author = User::factory()->create(['name' => 'Daniel']);
        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $author->id,
            'content' => 'Hola a todos, como va?',
            'type' => MessageType::Text,
        ]);

        $chatBefore = ProjectMessage::where('project_id', $project->id)->count();

        $entry = $this->logger()->messageSent($project, $message);

        $this->assertSame(ActivityType::MessageSent, $entry->type);
        $this->assertSame($author->id, $entry->user_id);
        $this->assertStringContainsString('Daniel', $entry->description);
        $this->assertStringContainsString('Hola a todos', $entry->description);
        $this->assertSame($message->id, $entry->properties['message_id']);

        // messageSent NO genera system message: el chat ya
        // tiene el mensaje humano original.
        $this->assertSame($chatBefore, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_message_sent_trunca_contenido_largo(): void
    {
        $project = Project::factory()->create();
        $author = User::factory()->create(['name' => 'Daniel']);
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 10);
        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $author->id,
            'content' => $longContent,
            'type' => MessageType::Text,
        ]);

        $entry = $this->logger()->messageSent($project, $message);

        // La preview se trunca a 80 caracteres. Verificamos que
        // el texto del mensaje dentro del description NO contiene
        // el contenido completo (es mas corto que el original).
        $this->assertLessThan(
            mb_strlen($longContent),
            mb_strlen($entry->description),
        );
        $this->assertStringContainsString('Lorem ipsum', $entry->description);
        $this->assertStringContainsString('...', $entry->description);
    }
}
