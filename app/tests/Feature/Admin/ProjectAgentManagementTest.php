<?php

namespace Tests\Feature\Admin;

use App\Models\AgentTemplate;
use App\Models\Project;
use App\Models\ProjectAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de feature de las asignaciones de templates de
 * agentes a proyectos.
 *
 * Cubren: listado, alta, validacion de duplicados via
 * `Rule::unique` con `where project_id`, actualizacion
 * de override, desasignacion, export JSON y bloqueos
 * de autorizacion para clientes.
 */
class ProjectAgentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ve_los_agentes_del_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create(['name' => 'Arquitecto']);
        $project->agents()->attach($template->id, [
            'system_prompt_override' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.projects.agents.index', $project))
            ->assertOk()
            ->assertSee('Arquitecto');
    }

    public function test_admin_puede_asignar_un_template_al_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.agents.store', $project), [
                'agent_template_id' => $template->id,
                'system_prompt_override' => 'Override custom.',
            ])->assertRedirect(route('admin.projects.agents.index', $project));

        $this->assertDatabaseHas('project_agents', [
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
            'system_prompt_override' => 'Override custom.',
        ]);
    }

    public function test_admin_puede_asignar_sin_override_y_queda_null(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.agents.store', $project), [
                'agent_template_id' => $template->id,
            ])->assertRedirect(route('admin.projects.agents.index', $project));

        $assignment = ProjectAgent::where('project_id', $project->id)->first();
        $this->assertNotNull($assignment);
        $this->assertNull($assignment->system_prompt_override);
    }

    public function test_admin_no_puede_asignar_un_template_duplicado(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();
        $project->agents()->attach($template->id, ['system_prompt_override' => null]);

        $this->actingAs($admin)
            ->post(route('admin.projects.agents.store', $project), [
                'agent_template_id' => $template->id,
            ])->assertSessionHasErrors('agent_template_id');

        $this->assertSame(1, ProjectAgent::where('project_id', $project->id)->count());
    }

    public function test_admin_puede_actualizar_el_override_de_una_asignacion(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();
        $assignment = ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
            'system_prompt_override' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.projects.agents.update', [$project, $assignment]), [
                'system_prompt_override' => 'Nuevo override',
            ])->assertRedirect(route('admin.projects.agents.index', $project));

        $this->assertSame('Nuevo override', $assignment->fresh()->system_prompt_override);
    }

    public function test_admin_puede_desasignar_un_template_del_proyecto(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();
        $assignment = ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.projects.agents.destroy', [$project, $assignment]))
            ->assertRedirect(route('admin.projects.agents.index', $project));

        $this->assertDatabaseMissing('project_agents', ['id' => $assignment->id]);
    }

    public function test_cliente_no_puede_listar_los_agentes_de_un_proyecto(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();

        $this->actingAs($client)
            ->get(route('admin.projects.agents.index', $project))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_cliente_no_puede_asignar_un_template(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($client)
            ->post(route('admin.projects.agents.store', $project), [
                'agent_template_id' => $template->id,
            ])->assertRedirect(route('portal.dashboard'));

        $this->assertSame(0, ProjectAgent::count());
    }

    public function test_cliente_no_puede_editar_una_asignacion(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();
        $assignment = ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
            'system_prompt_override' => null,
        ]);

        $this->actingAs($client)
            ->put(route('admin.projects.agents.update', [$project, $assignment]), [
                'system_prompt_override' => 'Hack',
            ])->assertRedirect(route('portal.dashboard'));

        $this->assertNull($assignment->fresh()->system_prompt_override);
    }

    public function test_cliente_no_puede_desasignar_un_template(): void
    {
        $client = User::factory()->client()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();
        $assignment = ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
        ]);

        $this->actingAs($client)
            ->delete(route('admin.projects.agents.destroy', [$project, $assignment]))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('project_agents', ['id' => $assignment->id]);
    }

    public function test_export_json_de_asignacion_devuelve_el_prompt_efectivo(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create([
            'name' => 'Arquitecto',
            'system_prompt' => 'Prompt del template.',
        ]);
        $assignment = ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
            'system_prompt_override' => 'Override concreto del proyecto.',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.projects.agents.export', [$project, $assignment]));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('agent-assignment-arquitecto.json', $response->headers->get('Content-Disposition'));

        $payload = $response->json();
        $this->assertSame('Override concreto del proyecto.', $payload['system_prompt']);
        $this->assertSame('Arquitecto', $payload['name']);
    }

    public function test_la_vista_index_muestra_el_prompt_efectivo(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create(['system_prompt' => 'Prompt del template.']);
        ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
            'system_prompt_override' => 'Override del proyecto',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.projects.agents.index', $project))
            ->assertOk()
            ->assertSee('Override del proyecto')
            ->assertSee('Prompt efectivo');
    }
}
