<?php

namespace Tests\Unit\Models;

use App\Models\AgentTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `AgentTemplate`.
 *
 * Cubren: cast de `tools` a array, relaciones
 * (`creator`, `projects`), scopes (`byCategory`, `search`)
 * y el helper `toExportArray()`.
 */
class AgentTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tools_se_castea_a_array(): void
    {
        $template = AgentTemplate::factory()->withTools()->create();

        $this->assertIsArray($template->tools);
        $this->assertCount(2, $template->tools);
        $this->assertSame('web_search', $template->tools[0]['name']);
    }

    public function test_created_by_se_castea_a_entero(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->forCreator($admin)->create();

        $this->assertSame($admin->id, $template->created_by);
    }

    public function test_relacion_creator_devuelve_el_usuario_autor(): void
    {
        $admin = User::factory()->admin()->create();
        $template = AgentTemplate::factory()->forCreator($admin)->create();

        $this->assertInstanceOf(User::class, $template->creator);
        $this->assertSame($admin->id, $template->creator->id);
    }

    public function test_relacion_proyectos_empieza_vacia(): void
    {
        $template = AgentTemplate::factory()->create();

        $this->assertCount(0, $template->projects);
    }

    public function test_relacion_proyectos_refleja_las_asignaciones(): void
    {
        $template = AgentTemplate::factory()->create();
        $project = Project::factory()->create();
        $template->projects()->attach($project->id, [
            'system_prompt_override' => null,
        ]);

        $this->assertCount(1, $template->refresh()->projects);
        $this->assertSame($project->id, $template->projects->first()->id);
        $this->assertNull($template->projects->first()->pivot->system_prompt_override);
    }

    public function test_scope_by_category_filtra_por_categoria_exacta(): void
    {
        AgentTemplate::factory()->inCategory('backend')->create();
        AgentTemplate::factory()->inCategory('frontend')->create();
        AgentTemplate::factory()->inCategory('frontend')->create();

        $frontend = AgentTemplate::byCategory('frontend')->pluck('category')->all();

        $this->assertCount(2, $frontend);
        $this->assertSame(['frontend', 'frontend'], $frontend);
    }

    public function test_scope_by_category_con_null_o_vacio_no_filtra(): void
    {
        AgentTemplate::factory()->inCategory('backend')->create();
        AgentTemplate::factory()->inCategory('frontend')->create();

        $this->assertCount(2, AgentTemplate::byCategory(null)->get());
        $this->assertCount(2, AgentTemplate::byCategory('')->get());
    }

    public function test_scope_search_busca_en_name_y_description(): void
    {
        AgentTemplate::factory()->create(['name' => 'Arquitecto Backend', 'description' => 'diseno de servicios']);
        AgentTemplate::factory()->create(['name' => 'Frontend Dev', 'description' => 'interfaces accesibles']);
        AgentTemplate::factory()->create(['name' => 'Reviewer', 'description' => 'revisor de pull requests']);

        $byName = AgentTemplate::search('arquitecto')->pluck('name')->all();
        $this->assertCount(1, $byName);
        $this->assertSame('Arquitecto Backend', $byName[0]);

        $byDescription = AgentTemplate::search('revisor')->pluck('name')->all();
        $this->assertCount(1, $byDescription);
        $this->assertSame('Reviewer', $byDescription[0]);
    }

    public function test_scope_search_con_null_o_vacio_no_filtra(): void
    {
        AgentTemplate::factory()->count(2)->create();

        $this->assertCount(2, AgentTemplate::search(null)->get());
        $this->assertCount(2, AgentTemplate::search('')->get());
    }

    public function test_to_export_array_devuelve_la_forma_esperada(): void
    {
        $template = AgentTemplate::factory()->withTools()->create([
            'name' => 'Arquitecto',
            'description' => 'Disena sistemas',
            'system_prompt' => 'Eres un arquitecto',
            'model' => 'gpt-4o',
            'category' => 'architecture',
        ]);

        $export = $template->toExportArray();

        $this->assertSame('Arquitecto', $export['name']);
        $this->assertSame('Disena sistemas', $export['description']);
        $this->assertSame('Eres un arquitecto', $export['system_prompt']);
        $this->assertSame('gpt-4o', $export['model']);
        $this->assertSame('architecture', $export['category']);
        $this->assertIsArray($export['tools']);
    }
}
