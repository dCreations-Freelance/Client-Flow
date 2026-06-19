<?php

namespace Tests\Feature\Admin;

use App\Models\AgentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de feature del CRUD de templates de agentes IA.
 *
 * Cubren: listado, formularios (create/edit/show), filtros
 * (search, category), export JSON, sidebar y bloqueos de
 * autorizacion para clientes.
 */
class AgentTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ve_el_listado_de_templates(): void
    {
        $admin = User::factory()->admin()->create();
        AgentTemplate::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.index'))
            ->assertOk()
            ->assertSee('Templates de agentes IA');
    }

    public function test_admin_ve_el_formulario_de_creacion(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.create'))
            ->assertOk()
            ->assertSee('Nuevo template');
    }

    public function test_admin_ve_el_formulario_de_edicion(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.edit', $template))
            ->assertOk()
            ->assertSee('Editar template');
    }

    public function test_admin_ve_el_detalle_de_un_template(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->create(['name' => 'Arquitecto']);

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.show', $template))
            ->assertOk()
            ->assertSee('Arquitecto');
    }

    public function test_cliente_es_redirigido_al_portal_desde_el_index(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('admin.agent-templates.index'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_cliente_no_puede_crear_un_template(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->post(route('admin.agent-templates.store'), [
                'name' => 'Hack',
                'system_prompt' => 'Hack system prompt',
            ])->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseCount('agent_templates', 0);
    }

    public function test_cliente_no_puede_editar_un_template(): void
    {
        $client = User::factory()->client()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($client)
            ->put(route('admin.agent-templates.update', $template), [
                'name' => 'Hack',
            ])->assertRedirect(route('portal.dashboard'));

        $this->assertNotSame('Hack', $template->fresh()->name);
    }

    public function test_cliente_no_puede_eliminar_un_template(): void
    {
        $client = User::factory()->client()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($client)
            ->delete(route('admin.agent-templates.destroy', $template))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('agent_templates', ['id' => $template->id]);
    }

    public function test_admin_crea_un_template_valido(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.agent-templates.store'), [
            'name' => 'Backend Helper',
            'description' => 'Ayuda con backend',
            'system_prompt' => 'Eres un asistente backend experto.',
            'category' => 'backend',
            'model' => 'gpt-4o',
        ]);

        $template = AgentTemplate::where('name', 'Backend Helper')->first();

        $this->assertNotNull($template);
        $this->assertSame($admin->id, $template->created_by);
        $this->assertSame('backend', $template->category);
        $response->assertRedirect(route('admin.agent-templates.show', $template));
    }

    public function test_admin_no_puede_crear_un_template_sin_system_prompt(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.agent-templates.store'), [
                'name' => 'Sin prompt',
            ])->assertSessionHasErrors('system_prompt');
    }

    public function test_admin_puede_editar_un_template(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->create(['name' => 'Antiguo']);

        $this->actingAs($admin)
            ->put(route('admin.agent-templates.update', $template), [
                'name' => 'Nuevo',
                'system_prompt' => 'Prompt actualizado suficientemente largo.',
            ])->assertRedirect(route('admin.agent-templates.show', $template));

        $this->assertSame('Nuevo', $template->fresh()->name);
    }

    public function test_admin_puede_eliminar_un_template(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.agent-templates.destroy', $template))
            ->assertRedirect(route('admin.agent-templates.index'));

        $this->assertDatabaseMissing('agent_templates', ['id' => $template->id]);
    }

    public function test_filtro_search_por_nombre_funciona(): void
    {
        $admin = User::factory()->admin()->create();
        AgentTemplate::factory()->create(['name' => 'Arquitecto Backend']);
        AgentTemplate::factory()->create(['name' => 'Frontend Dev']);

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.index', ['search' => 'Arquitecto']))
            ->assertOk()
            ->assertSee('Arquitecto Backend')
            ->assertDontSee('Frontend Dev');
    }

    public function test_filtro_category_filtra_por_categoria(): void
    {
        $admin = User::factory()->admin()->create();
        AgentTemplate::factory()->inCategory('backend')->create(['name' => 'Backend One']);
        AgentTemplate::factory()->inCategory('frontend')->create(['name' => 'Frontend One']);

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.index', ['category' => 'backend']))
            ->assertOk()
            ->assertSee('Backend One')
            ->assertDontSee('Frontend One');
    }

    public function test_export_json_devuelve_un_archivo_descargable(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->create([
            'name' => 'Arquitecto',
            'description' => 'desc',
            'system_prompt' => 'Prompt',
            'model' => 'gpt-4o',
            'category' => 'architecture',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.agent-templates.export', $template));

        $response->assertOk();
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('agent-template-arquitecto.json', $response->headers->get('Content-Disposition'));

        $payload = $response->json();
        $this->assertSame('Arquitecto', $payload['name']);
        $this->assertSame('desc', $payload['description']);
        $this->assertSame('Prompt', $payload['system_prompt']);
        $this->assertSame('gpt-4o', $payload['model']);
        $this->assertSame('architecture', $payload['category']);
    }

    public function test_sidebar_admin_incluye_link_a_templates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.agent-templates.index'))
            ->assertOk()
            ->assertSee('Templates IA');
    }
}
