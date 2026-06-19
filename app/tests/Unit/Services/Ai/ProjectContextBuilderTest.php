<?php

namespace Tests\Unit\Services\Ai;

use App\Enums\DocumentVisibility;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Task;
use App\Models\User;
use App\Services\Ai\ProjectContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_project_name_status_and_progress(): void
    {
        $project = Project::factory()->create([
            'name' => 'Mi proyecto top',
            'status' => ProjectStatus::InProgress,
        ]);

        $prompt = (new ProjectContextBuilder())->build($project);

        $this->assertStringContainsString('Mi proyecto top', $prompt);
        $this->assertStringContainsString('En progreso', $prompt);
        $this->assertStringContainsString('castellano', $prompt);
    }

    public function test_lists_recent_tasks_with_priority_and_assignee(): void
    {
        $assignee = User::factory()->create(['name' => 'Daniel']);
        $project = Project::factory()->create();
        Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Implementar login',
            'priority' => TaskPriority::High,
            'assignee_id' => $assignee->id,
            'created_at' => now(),
        ]);

        $prompt = (new ProjectContextBuilder())->build($project);

        $this->assertStringContainsString('Implementar login', $prompt);
        $this->assertStringContainsString('Alta', $prompt);
        $this->assertStringContainsString('Daniel', $prompt);
    }

    public function test_lists_only_public_documents(): void
    {
        $project = Project::factory()->create();
        ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'title' => 'Manual publico',
            'visibility' => DocumentVisibility::Public,
        ]);
        ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'title' => 'Notas internas',
            'visibility' => DocumentVisibility::Private,
        ]);

        $prompt = (new ProjectContextBuilder())->build($project);

        $this->assertStringContainsString('Manual publico', $prompt);
        $this->assertStringNotContainsString('Notas internas', $prompt);
    }

    public function test_handles_empty_projects_gracefully(): void
    {
        $project = Project::factory()->create();

        $prompt = (new ProjectContextBuilder())->build($project);

        $this->assertStringContainsString('aun no tiene tareas', $prompt);
        $this->assertStringContainsString('no tiene documentos publicos', $prompt);
    }
}
