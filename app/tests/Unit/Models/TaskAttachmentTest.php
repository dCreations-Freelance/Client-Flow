<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del modelo `TaskAttachment`: fillable, casts, accesors
 * (`humanSize`, `downloadName`, `diskPath`), scopes
 * (`forTask`, `forProject`, `recent`) y helpers
 * (`generateFilename`, `formatBytes`, `belongsToProject`).
 */
class TaskAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_crea_adjunto_con_datos_basicos(): void
    {
        $attachment = TaskAttachment::factory()->create();

        $this->assertNotNull($attachment->id);
        $this->assertNotNull($attachment->filename);
        $this->assertNotNull($attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertGreaterThan(0, $attachment->size);
    }

    public function test_size_se_castea_a_integer(): void
    {
        $attachment = TaskAttachment::factory()->create(['size' => '5000']);

        $this->assertSame(5000, $attachment->size);
    }

    public function test_human_size_formatea_bytes_a_unidad_legible(): void
    {
        $this->assertSame('500 B', TaskAttachment::formatBytes(500));
        $this->assertSame('1.0 KB', TaskAttachment::formatBytes(1024));
        $this->assertSame('1.5 MB', TaskAttachment::formatBytes(1_572_864));
        $this->assertSame('2.0 GB', TaskAttachment::formatBytes(2_147_483_648));
    }

    public function test_generate_filename_produce_nombre_unico_con_extension(): void
    {
        $name1 = TaskAttachment::generateFilename('doc.pdf');
        $name2 = TaskAttachment::generateFilename('foto.png');

        $this->assertStringEndsWith('.pdf', $name1);
        $this->assertStringEndsWith('.png', $name2);
        $this->assertNotSame($name1, $name2);
    }

    public function test_generate_filename_sin_extension_sigue_siendo_valido(): void
    {
        $name = TaskAttachment::generateFilename('archivo_sin_extension');

        // El nombre generado no debe terminar con un punto, ya
        // que la extension estaba vacia.
        $this->assertStringEndsNotWith('.', $name);
        $this->assertMatchesRegularExpression('/^\d{8}_\d{6}_[A-Za-z0-9]{8}$/', $name);
    }

    public function test_disk_path_incluye_proyecto_y_contexto_tasks(): void
    {
        $project = Project::factory()->create();
        $column = \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'filename' => 'foto.png',
        ]);

        $this->assertSame("clientflow/projects/{$project->id}/attachments/tasks/foto.png", $attachment->disk_path);
    }

    public function test_belongs_to_project_devuelve_true_si_coincide(): void
    {
        $project = Project::factory()->create();
        $column = \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'column_id' => $column->id]);
        $attachment = TaskAttachment::factory()->create(['task_id' => $task->id]);

        $this->assertTrue($attachment->belongsToProject($project->id));
        $this->assertFalse($attachment->belongsToProject($project->id + 1));
    }

    public function test_scope_for_task_filtra_por_tarea(): void
    {
        $task1 = Task::factory()->create();
        $task2 = Task::factory()->create();

        TaskAttachment::factory()->count(2)->create(['task_id' => $task1->id]);
        TaskAttachment::factory()->count(3)->create(['task_id' => $task2->id]);

        $this->assertCount(2, TaskAttachment::forTask($task1->id)->get());
        $this->assertCount(3, TaskAttachment::forTask($task2->id)->get());
    }

    public function test_scope_recent_ordena_por_mas_reciente(): void
    {
        $task = Task::factory()->create();
        $old = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'created_at' => now()->subDays(5),
        ]);
        $new = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'created_at' => now(),
        ]);

        $ordered = $task->attachments()->recent()->get();
        $this->assertSame($new->id, $ordered->first()->id);
        $this->assertSame($old->id, $ordered->last()->id);
    }

    public function test_relacion_con_user(): void
    {
        $user = User::factory()->create();
        $attachment = TaskAttachment::factory()->create(['user_id' => $user->id]);

        $this->assertSame($user->id, $attachment->user->id);
    }

    public function test_relacion_con_task(): void
    {
        $task = Task::factory()->create();
        $attachment = TaskAttachment::factory()->create(['task_id' => $task->id]);

        $this->assertSame($task->id, $attachment->task->id);
    }
}
