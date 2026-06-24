<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentVisibility;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateColumn;
use App\Models\ProjectTemplateDocument;
use App\Models\ProjectTemplateTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests feature del modulo de plantillas de
 * proyecto en el panel admin.
 *
 * Cubre:
 * - Render del listado con busqueda y filtro por
 *   categoria.
 * - CRUD principal de plantillas (crear, ver,
 *   editar, eliminar).
 * - CRUD anidado de columnas, tareas y documentos.
 * - Aislamiento entre plantillas (no se puede
 *   manipular una columna/tarea/documento de otra
 *   plantilla).
 * - Flujo de "crear proyecto desde plantilla":
 *   el proyecto se crea, se le aplican las
 *   columnas / tareas / documentos, y se muestra
 *   un mensaje de confirmacion.
 * - Cascade: borrar una plantilla borra sus
 *   elementos anidados.
 * - Rechazo a clientes: 403 en todas las rutas
 *   admin.
 */
class ProjectTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // -----------------------------------------------------------------
    // Rutas y autorizacion
    // -----------------------------------------------------------------

    public function test_admin_puede_ver_listado_de_plantillas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.project-templates.index'))
            ->assertOk();
    }

    public function test_cliente_no_puede_acceder_al_listado_de_plantillas(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.project-templates.index'))
            ->assertRedirect(route('portal.dashboard'));
    }

    // -----------------------------------------------------------------
    // CRUD principal
    // -----------------------------------------------------------------

    public function test_admin_puede_crear_plantilla_y_redirige_a_edit(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.project-templates.store'), [
                'name' => 'Lanzamiento Web',
                'description' => 'Plantilla estandar para proyectos web.',
                'category' => 'web',
            ])
            ->assertRedirect(route('admin.project-templates.edit', ProjectTemplate::first()));

        $this->assertDatabaseHas('project_templates', [
            'name' => 'Lanzamiento Web',
            'category' => 'web',
            'created_by' => $admin->id,
        ]);
    }

    public function test_validacion_rechaza_nombre_vacio(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.project-templates.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_validacion_rechaza_nombre_duplicado(): void
    {
        $admin = $this->admin();
        ProjectTemplate::factory()->create(['name' => 'Duplicado']);

        $this->actingAs($admin)
            ->post(route('admin.project-templates.store'), [
                'name' => 'Duplicado',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_puede_editar_plantilla_manteniendo_nombre(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create(['name' => 'Original']);

        $this->actingAs($admin)
            ->put(route('admin.project-templates.update', $template), [
                'name' => 'Original',
                'description' => 'Descripcion nueva',
                'category' => 'web',
            ])
            ->assertRedirect(route('admin.project-templates.edit', $template));

        $template->refresh();
        $this->assertSame('Descripcion nueva', $template->description);
        $this->assertSame('web', $template->category);
    }

    public function test_admin_puede_eliminar_plantilla_y_cascade_borra_elementos(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->create();
        ProjectTemplateTask::factory()->forTemplate($template)->create();
        ProjectTemplateDocument::factory()->forTemplate($template)->create();

        $this->actingAs($admin)
            ->delete(route('admin.project-templates.destroy', $template))
            ->assertRedirect(route('admin.project-templates.index'));

        $this->assertDatabaseMissing('project_templates', ['id' => $template->id]);
        $this->assertDatabaseCount('project_template_columns', 0);
        $this->assertDatabaseCount('project_template_tasks', 0);
        $this->assertDatabaseCount('project_template_documents', 0);
    }

    public function test_listado_filtra_por_search(): void
    {
        $admin = $this->admin();
        ProjectTemplate::factory()->create(['name' => 'Lanzamiento web']);
        ProjectTemplate::factory()->create(['name' => 'App movil']);

        $this->actingAs($admin)
            ->get(route('admin.project-templates.index', ['search' => 'movil']))
            ->assertOk()
            ->assertSee('App movil')
            ->assertDontSee('Lanzamiento web');
    }

    public function test_listado_filtra_por_categoria(): void
    {
        $admin = $this->admin();
        ProjectTemplate::factory()->create(['name' => 'Plantilla Web Unica', 'category' => 'web']);
        ProjectTemplate::factory()->create(['name' => 'Plantilla Movil Unica', 'category' => 'mobile']);

        $this->actingAs($admin)
            ->get(route('admin.project-templates.index', ['category' => 'web']))
            ->assertOk()
            ->assertSee('Plantilla Web Unica')
            ->assertDontSee('Plantilla Movil Unica');
    }

    // -----------------------------------------------------------------
    // CRUD anidado: columnas
    // -----------------------------------------------------------------

    public function test_admin_puede_anadir_columna_a_plantilla(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.project-templates.columns.store', $template), [
                'name' => 'Pendiente',
                'color' => '#94A3B8',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_template_columns', [
            'template_id' => $template->id,
            'name' => 'Pendiente',
            'color' => '#94A3B8',
            'position' => 0,
        ]);
    }

    public function test_anadir_columna_asigna_position_al_final(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(1)->create();

        $this->actingAs($admin)
            ->post(route('admin.project-templates.columns.store', $template), [
                'name' => 'Nueva',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_template_columns', [
            'template_id' => $template->id,
            'name' => 'Nueva',
            'position' => 2,
        ]);
    }

    public function test_admin_puede_actualizar_y_eliminar_columna(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();
        $column = ProjectTemplateColumn::factory()->forTemplate($template)->create();

        $this->actingAs($admin)
            ->put(route('admin.project-templates.columns.update', [$template, $column]), [
                'name' => 'Renombrada',
                'color' => '#2563EB',
            ])
            ->assertRedirect();
        $this->assertSame('Renombrada', $column->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('admin.project-templates.columns.destroy', [$template, $column]))
            ->assertRedirect();
        $this->assertDatabaseMissing('project_template_columns', ['id' => $column->id]);
    }

    public function test_no_se_puede_manipular_columna_de_otra_plantilla(): void
    {
        $admin = $this->admin();
        $templateA = ProjectTemplate::factory()->create();
        $templateB = ProjectTemplate::factory()->create();
        $columnB = ProjectTemplateColumn::factory()->forTemplate($templateB)->create();

        $this->actingAs($admin)
            ->put(route('admin.project-templates.columns.update', [$templateA, $columnB]), [
                'name' => 'Hack',
            ])
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // CRUD anidado: tareas
    // -----------------------------------------------------------------

    public function test_admin_puede_anadir_tarea_a_plantilla(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();
        $column = ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create();

        $this->actingAs($admin)
            ->post(route('admin.project-templates.tasks.store', $template), [
                'column_position' => 0,
                'title' => 'Maquetar landing',
                'type' => TaskType::Feature->value,
                'priority' => TaskPriority::High->value,
                'estimated_hours' => 8,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_template_tasks', [
            'template_id' => $template->id,
            'column_position' => 0,
            'title' => 'Maquetar landing',
            'type' => TaskType::Feature->value,
            'priority' => TaskPriority::High->value,
            'estimated_hours' => 8,
        ]);
    }

    public function test_admin_puede_actualizar_y_eliminar_tarea(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();
        $task = ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create();

        $this->actingAs($admin)
            ->put(route('admin.project-templates.tasks.update', [$template, $task]), [
                'column_position' => 0,
                'title' => 'Editada',
                'type' => TaskType::Task->value,
                'priority' => TaskPriority::Low->value,
            ])
            ->assertRedirect();
        $this->assertSame('Editada', $task->fresh()->title);

        $this->actingAs($admin)
            ->delete(route('admin.project-templates.tasks.destroy', [$template, $task]))
            ->assertRedirect();
        $this->assertDatabaseMissing('project_template_tasks', ['id' => $task->id]);
    }

    public function test_no_se_puede_manipular_tarea_de_otra_plantilla(): void
    {
        $admin = $this->admin();
        $templateA = ProjectTemplate::factory()->create();
        $templateB = ProjectTemplate::factory()->create();
        $taskB = ProjectTemplateTask::factory()->forTemplate($templateB)->create();

        $this->actingAs($admin)
            ->put(route('admin.project-templates.tasks.update', [$templateA, $taskB]), [
                'column_position' => 0,
                'title' => 'Hack',
                'type' => TaskType::Task->value,
                'priority' => TaskPriority::Low->value,
            ])
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // CRUD anidado: documentos
    // -----------------------------------------------------------------

    public function test_admin_puede_anadir_documento_a_plantilla(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.project-templates.documents.store', $template), [
                'title' => 'Onboarding',
                'content' => '# Bienvenida',
                'visibility' => DocumentVisibility::Private->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_template_documents', [
            'template_id' => $template->id,
            'title' => 'Onboarding',
            'content' => '# Bienvenida',
            'visibility' => DocumentVisibility::Private->value,
        ]);
    }

    public function test_admin_puede_actualizar_y_eliminar_documento(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();
        $document = ProjectTemplateDocument::factory()->forTemplate($template)->create();

        $this->actingAs($admin)
            ->put(route('admin.project-templates.documents.update', [$template, $document]), [
                'title' => 'Editado',
                'content' => null,
                'visibility' => DocumentVisibility::Public->value,
            ])
            ->assertRedirect();
        $document->refresh();
        $this->assertSame('Editado', $document->title);
        $this->assertTrue($document->isPublic());

        $this->actingAs($admin)
            ->delete(route('admin.project-templates.documents.destroy', [$template, $document]))
            ->assertRedirect();
        $this->assertDatabaseMissing('project_template_documents', ['id' => $document->id]);
    }

    public function test_no_se_puede_manipular_documento_de_otra_plantilla(): void
    {
        $admin = $this->admin();
        $templateA = ProjectTemplate::factory()->create();
        $templateB = ProjectTemplate::factory()->create();
        $documentB = ProjectTemplateDocument::factory()->forTemplate($templateB)->create();

        $this->actingAs($admin)
            ->put(route('admin.project-templates.documents.update', [$templateA, $documentB]), [
                'title' => 'Hack',
                'content' => null,
                'visibility' => DocumentVisibility::Private->value,
            ])
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // Crear proyecto desde plantilla
    // -----------------------------------------------------------------

    public function test_admin_puede_ver_formulario_de_crear_desde_plantilla(): void
    {
        $admin = $this->admin();
        $template = ProjectTemplate::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.projects.create-from-template', $template))
            ->assertOk()
            ->assertSee($template->name);
    }

    public function test_admin_puede_crear_proyecto_desde_plantilla_y_se_aplican_los_elementos(): void
    {
        $admin = $this->admin();
        $organization = Organization::factory()->create();
        $template = ProjectTemplate::factory()->create();
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(0)->create(['name' => 'Pendiente']);
        ProjectTemplateColumn::factory()->forTemplate($template)->atPosition(1)->create(['name' => 'Hecho']);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(0)->create(['title' => 'Tarea 1']);
        ProjectTemplateTask::factory()->forTemplate($template)->inColumn(1)->create(['title' => 'Tarea 2']);
        ProjectTemplateDocument::factory()->forTemplate($template)->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.store-from-template', $template), [
                'name' => 'Cliente A',
                'organization_id' => $organization->id,
                'description' => null,
                'is_visible_to_client' => true,
            ])
            ->assertRedirect();

        $project = Project::where('name', 'Cliente A')->first();
        $this->assertNotNull($project);
        $this->assertSame(2, $project->columns()->count());
        $this->assertSame(2, $project->tasks()->count());
        $this->assertSame(1, $project->documents()->count());
        $this->assertSame(1, (int) $project->columns()->max('position'));
    }

    public function test_crear_desde_plantilla_redirige_al_hub_con_mensaje(): void
    {
        $admin = $this->admin();
        $organization = Organization::factory()->create();
        $template = ProjectTemplate::factory()->create(['name' => 'Mi plantilla']);
        ProjectTemplateColumn::factory()->forTemplate($template)->create();
        ProjectTemplateTask::factory()->forTemplate($template)->create();
        ProjectTemplateDocument::factory()->forTemplate($template)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.projects.store-from-template', $template), [
                'name' => 'Nuevo',
                'organization_id' => $organization->id,
            ]);

        $project = Project::where('name', 'Nuevo')->first();
        $response->assertRedirect(route('admin.projects.show', $project));
        $response->assertSessionHas('status');
        $this->assertStringContainsString('Mi plantilla', (string) session('status'));
    }

    public function test_cliente_no_puede_crear_proyectos_desde_plantilla(): void
    {
        $client = User::factory()->client()->create();
        $template = ProjectTemplate::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($client)
            ->get(route('admin.projects.create-from-template', $template))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($client)
            ->post(route('admin.projects.store-from-template', $template), [
                'name' => 'Hack',
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('portal.dashboard'));
    }
}
