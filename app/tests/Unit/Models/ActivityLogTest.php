<?php

namespace Tests\Unit\Models;

use App\Enums\ActivityType;
use App\Enums\DocumentVisibility;
use App\Models\ActivityLog;
use App\Models\CalendarEvent;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMessage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del modelo `ActivityLog`.
 *
 * Cubren: casts (type a enum, properties a array), scopes
 * (ofType, inCategory, public, private, forProject,
 * chronological, recent, beforeId), relaciones (project,
 * organization, user, subject morphTo) y helpers
 * (isPublic, icon, tone, category).
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_castea_type_al_enum(): void
    {
        $entry = ActivityLog::factory()->create([
            'type' => ActivityType::TaskCreated,
        ]);

        $this->assertInstanceOf(ActivityType::class, $entry->type);
        $this->assertSame(ActivityType::TaskCreated, $entry->type);
    }

    public function test_castea_properties_a_array(): void
    {
        $entry = ActivityLog::factory()->create([
            'properties' => ['count' => 3, 'visibility' => 'public'],
        ]);

        $this->assertSame(['count' => 3, 'visibility' => 'public'], $entry->properties);
    }

    public function test_no_tiene_updated_at(): void
    {
        $entry = ActivityLog::factory()->create();

        $this->assertNull($entry->updated_at);
    }

    public function test_relaciones_basicas(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $entry = ActivityLog::factory()
            ->forProject($project)
            ->byUser($user)
            ->create();

        $this->assertSame($project->id, $entry->project->id);
        $this->assertSame($user->id, $entry->user->id);
        $this->assertSame($project->organization_id, $entry->organization->id);
    }

    public function test_subject_morphto_resuelve_task(): void
    {
        $project = Project::factory()->create();
        $column = \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        $entry = ActivityLog::factory()
            ->forProject($project)
            ->create([
                'type' => ActivityType::TaskCreated,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
            ]);

        $this->assertInstanceOf(Task::class, $entry->subject);
        $this->assertSame($task->id, $entry->subject->id);
    }

    public function test_scope_of_type_filtra_por_tipo(): void
    {
        ActivityLog::factory()->taskCreated()->count(2)->create();
        ActivityLog::factory()->taskCompleted()->count(3)->create();

        $this->assertSame(2, ActivityLog::ofType(ActivityType::TaskCreated)->count());
        $this->assertSame(3, ActivityLog::ofType(ActivityType::TaskCompleted)->count());
    }

    public function test_scope_in_category_all_no_aplica_filtro(): void
    {
        ActivityLog::factory()->taskCreated()->count(2)->create();
        ActivityLog::factory()->messageSent()->count(1)->create();

        $this->assertSame(3, ActivityLog::inCategory('all')->count());
        $this->assertSame(3, ActivityLog::inCategory('')->count());
    }

    public function test_scope_in_category_agrupa_por_categoria(): void
    {
        // tasks: 2 (TaskCreated + TaskCompleted)
        ActivityLog::factory()->taskCreated()->count(2)->create();
        ActivityLog::factory()->taskCompleted()->count(1)->create();
        // documents: 1
        ActivityLog::factory()->publicDocumentCreated()->create();
        // messages: 1
        ActivityLog::factory()->messageSent()->create();

        $this->assertSame(3, ActivityLog::inCategory('tasks')->count());
        $this->assertSame(1, ActivityLog::inCategory('documents')->count());
        $this->assertSame(1, ActivityLog::inCategory('messages')->count());
    }

    public function test_scope_in_category_desconocida_devuelve_query_vacia(): void
    {
        ActivityLog::factory()->count(3)->create();

        $this->assertSame(0, ActivityLog::inCategory('inexistente')->count());
    }

    public function test_scope_public_excluye_eventos_privados(): void
    {
        // Publico: task_created
        ActivityLog::factory()->taskCreated()->create();
        // Privado: member_added, task_updated, template_applied
        ActivityLog::factory()->memberAdded()->create();
        ActivityLog::factory()->create(['type' => ActivityType::TaskUpdated]);
        ActivityLog::factory()->create(['type' => ActivityType::TemplateApplied]);

        $this->assertSame(1, ActivityLog::public()->count());
    }

    public function test_scope_public_incluye_documentos_publicos_y_excluye_privados(): void
    {
        $project = Project::factory()->create();

        ActivityLog::factory()->forProject($project)->publicDocumentCreated()->create();
        ActivityLog::factory()->forProject($project)->privateDocumentCreated()->create();

        $this->assertSame(1, ActivityLog::public()->count());
    }

    public function test_scope_private_devuelve_solo_eventos_no_publicos(): void
    {
        ActivityLog::factory()->taskCreated()->create();
        ActivityLog::factory()->memberAdded()->create();
        ActivityLog::factory()->create(['type' => ActivityType::TaskDeleted]);

        $this->assertSame(2, ActivityLog::private()->count());
    }

    public function test_scope_for_project_limita_a_proyecto(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        ActivityLog::factory()->forProject($project1)->count(2)->create();
        ActivityLog::factory()->forProject($project2)->count(3)->create();

        $this->assertSame(2, ActivityLog::forProject($project1)->count());
        $this->assertSame(3, ActivityLog::forProject($project2)->count());
    }

    public function test_scope_recent_devuelve_los_n_mas_recientes(): void
    {
        $project = Project::factory()->create();
        $a = ActivityLog::factory()->forProject($project)->create();
        $b = ActivityLog::factory()->forProject($project)->create();
        $c = ActivityLog::factory()->forProject($project)->create();
        $d = ActivityLog::factory()->forProject($project)->create();
        $e = ActivityLog::factory()->forProject($project)->create();

        $entries = ActivityLog::forProject($project)->recent(2)->get();

        $this->assertCount(2, $entries);
        // Los ids estan en orden descendente (mas reciente primero).
        $this->assertSame([$e->id, $d->id], $entries->pluck('id')->all());
    }

    public function test_scope_before_id_pagina_hacia_atras(): void
    {
        $project = Project::factory()->create();
        $a = ActivityLog::factory()->forProject($project)->create();
        $b = ActivityLog::factory()->forProject($project)->create();
        $c = ActivityLog::factory()->forProject($project)->create();

        $this->assertCount(2, ActivityLog::beforeId($c->id)->get());
        $this->assertCount(1, ActivityLog::beforeId($b->id)->get());
    }

    public function test_scope_chronological_orden_descendente(): void
    {
        $project = Project::factory()->create();
        $a = ActivityLog::factory()->forProject($project)->create();
        $b = ActivityLog::factory()->forProject($project)->create();
        $c = ActivityLog::factory()->forProject($project)->create();

        $ids = ActivityLog::forProject($project)->chronological()->pluck('id')->all();

        $this->assertSame([$c->id, $b->id, $a->id], $ids);
    }

    public function test_helper_is_public_delega_en_enum(): void
    {
        $public = ActivityLog::factory()->taskCreated()->create();
        $private = ActivityLog::factory()->memberAdded()->create();

        $this->assertTrue($public->isPublic());
        $this->assertFalse($private->isPublic());
    }

    public function test_helper_category_delega_en_enum(): void
    {
        $entry = ActivityLog::factory()->taskCreated()->create();

        $this->assertSame('tasks', $entry->category());
    }

    public function test_helper_subject_url_devuelve_null_si_no_hay_sujeto(): void
    {
        $entry = ActivityLog::factory()->create(['subject_type' => null, 'subject_id' => null]);

        $this->assertNull($entry->subjectUrl());
    }

    public function test_helper_subject_url_admin_devuelve_link_a_tarea(): void
    {
        $project = Project::factory()->create();
        $column = \App\Models\BoardColumn::factory()->create(['project_id' => $project->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]);

        $entry = ActivityLog::factory()
            ->forProject($project)
            ->create([
                'subject_type' => Task::class,
                'subject_id' => $task->id,
            ]);

        $this->assertStringContainsString((string) $task->id, $entry->subjectUrl());
    }
}
