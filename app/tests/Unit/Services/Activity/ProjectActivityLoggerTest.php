<?php

namespace Tests\Unit\Services\Activity;

use App\Enums\DocumentVisibility;
use App\Enums\CalendarEventType;
use App\Enums\MessageType;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\BoardColumn;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ProjectActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del `ProjectActivityLogger`.
 *
 * Cubren: que cada metodo crea un mensaje de sistema con el
 * contenido adecuado, y que `documentPublished` no crea nada
 * cuando el documento es privado.
 */
class ProjectActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    private function logger(): ProjectActivityLogger
    {
        return app(ProjectActivityLogger::class);
    }

    public function test_task_created_crea_mensaje_de_sistema_con_titulo_y_tipo(): void
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
            'title' => 'Implementar login',
            'type' => TaskType::Feature,
            'priority' => TaskPriority::High,
        ]);

        $message = $this->logger()->taskCreated($project, $task);

        $this->assertInstanceOf(ProjectMessage::class, $message);
        $this->assertSame(MessageType::System, $message->type);
        $this->assertNull($message->user_id);
        $this->assertStringContainsString('Implementar login', $message->content);
        $this->assertStringContainsString('Caracteristica', $message->content);
        $this->assertStringContainsString('Alta', $message->content);
    }

    public function test_task_completed_incluye_nombre_del_actor(): void
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->taskCompleted($project, $task, $actor);

        $this->assertStringContainsString('Daniel', $message->content);
        $this->assertStringContainsString('completo', $message->content);
        $this->assertStringContainsString($task->title, $message->content);
    }

    public function test_task_reopened_incluye_nombre_del_actor(): void
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->taskReopened($project, $task, $actor);

        $this->assertStringContainsString('re-abrio', $message->content);
        $this->assertStringContainsString('Daniel', $message->content);
    }

    public function test_task_moved_incluye_columna_destino(): void
    {
        $project = Project::factory()->create();
        $column = BoardColumn::factory()->create(['project_id' => $project->id, 'name' => 'En curso']);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->taskMoved($project, $task, $column, $actor);

        $this->assertStringContainsString('movio', $message->content);
        $this->assertStringContainsString('En curso', $message->content);
        $this->assertStringContainsString('Daniel', $message->content);
    }

    public function test_project_archived_y_unarchived_generan_mensajes(): void
    {
        $project = Project::factory()->create();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $archived = $this->logger()->projectArchived($project, $actor);
        $unarchived = $this->logger()->projectUnarchived($project, $actor);

        $this->assertStringContainsString('archivo', $archived->content);
        $this->assertStringContainsString('desarchivo', $unarchived->content);
    }

    public function test_document_published_crea_mensaje_si_es_publico(): void
    {
        $project = Project::factory()->create();
        $document = ProjectDocument::factory()->public()->create([
            'project_id' => $project->id,
            'title' => 'Manual de uso',
        ]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->documentPublished($project, $document, $actor);

        $this->assertNotNull($message);
        $this->assertStringContainsString('publico', $message->content);
        $this->assertStringContainsString('Manual de uso', $message->content);
        $this->assertStringContainsString('Daniel', $message->content);
    }

    public function test_document_published_no_crea_mensaje_si_es_privado(): void
    {
        $project = Project::factory()->create();
        $document = ProjectDocument::factory()->private()->create([
            'project_id' => $project->id,
        ]);
        $actor = User::factory()->create();

        $message = $this->logger()->documentPublished($project, $document, $actor);

        $this->assertNull($message);
        $this->assertSame(0, ProjectMessage::where('project_id', $project->id)->count());
    }

    public function test_event_created_crea_mensaje_de_sistema_con_tipo_y_titulo(): void
    {
        $project = Project::factory()->create();
        $event = CalendarEvent::factory()->forProject($project)->create([
            'title' => 'Lanzamiento MVP',
            'type' => CalendarEventType::Milestone,
        ]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->eventCreated($project, $event, $actor);

        $this->assertInstanceOf(ProjectMessage::class, $message);
        $this->assertSame(MessageType::System, $message->type);
        $this->assertNull($message->user_id);
        $this->assertStringContainsString('Lanzamiento MVP', $message->content);
        $this->assertStringContainsString('Hito', $message->content);
        $this->assertStringContainsString('Daniel', $message->content);
    }

    public function test_event_updated_incluye_titulo_y_nombre_del_actor(): void
    {
        $project = Project::factory()->create();
        $event = CalendarEvent::factory()->forProject($project)->create([
            'title' => 'Reunion',
        ]);
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->eventUpdated($project, $event, $actor);

        $this->assertStringContainsString('actualizo', $message->content);
        $this->assertStringContainsString('Reunion', $message->content);
        $this->assertStringContainsString('Daniel', $message->content);
    }

    public function test_event_deleted_recibe_titulo_como_string(): void
    {
        $project = Project::factory()->create();
        $actor = User::factory()->create(['name' => 'Daniel']);

        $message = $this->logger()->eventDeleted($project, 'Reunion cancelada', $actor);

        $this->assertStringContainsString('elimino', $message->content);
        $this->assertStringContainsString('Reunion cancelada', $message->content);
        $this->assertStringContainsString('Daniel', $message->content);
    }
}
