<?php

namespace Tests\Unit\Models;

use App\Models\AgentTemplate;
use App\Models\Project;
use App\Models\ProjectAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios del modelo `ProjectAgent`.
 *
 * Cubren: relaciones, helpers `effectiveSystemPrompt()` y
 * `toExportArray()`. El caso del override vacio es
 * importante: el admin puede haber editado la asignacion
 * y dejado el textarea en blanco, lo que NO debe sobrescribir
 * el prompt del template.
 */
class ProjectAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_relaciones_project_y_template(): void
    {
        $project = Project::factory()->create();
        $template = AgentTemplate::factory()->create();
        $assignment = ProjectAgent::factory()->create([
            'project_id' => $project->id,
            'agent_template_id' => $template->id,
        ]);

        $this->assertSame($project->id, $assignment->project->id);
        $this->assertSame($template->id, $assignment->template->id);
    }

    public function test_effective_system_prompt_devuelve_el_override_si_no_esta_vacio(): void
    {
        $template = AgentTemplate::factory()->create(['system_prompt' => 'Prompt del template.']);
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => 'Override custom para este proyecto.',
        ]);

        $this->assertSame('Override custom para este proyecto.', $assignment->effectiveSystemPrompt());
    }

    public function test_effective_system_prompt_devuelve_el_del_template_si_override_es_null(): void
    {
        $template = AgentTemplate::factory()->create(['system_prompt' => 'Prompt del template.']);
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => null,
        ]);

        $this->assertSame('Prompt del template.', $assignment->effectiveSystemPrompt());
    }

    public function test_effective_system_prompt_devuelve_el_del_template_si_override_esta_vacio(): void
    {
        $template = AgentTemplate::factory()->create(['system_prompt' => 'Prompt del template.']);
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => '   ',
        ]);

        $this->assertSame('Prompt del template.', $assignment->effectiveSystemPrompt());
    }

    public function test_effective_system_prompt_devuelve_null_si_no_hay_template_ni_override(): void
    {
        $template = AgentTemplate::factory()->create(['system_prompt' => '']);
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => null,
        ]);
        // Forzamos el template a null para simular un borrado
        // en cascada: la relacion devuelve null y el override
        // tambien, asi que no hay prompt efectivo.
        $assignment->setRelation('template', null);

        $this->assertNull($assignment->effectiveSystemPrompt());
    }

    public function test_to_export_array_incluye_el_prompt_efectivo(): void
    {
        $template = AgentTemplate::factory()->create([
            'name' => 'Backend',
            'description' => 'desc',
            'system_prompt' => 'Prompt del template.',
            'tools' => [['name' => 'tool1']],
            'model' => 'gpt-4o',
            'category' => 'backend',
        ]);
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => 'Override concreto',
        ]);

        $export = $assignment->toExportArray();

        $this->assertSame('Backend', $export['name']);
        $this->assertSame('desc', $export['description']);
        $this->assertSame('Override concreto', $export['system_prompt']);
        $this->assertSame([['name' => 'tool1']], $export['tools']);
        $this->assertSame('gpt-4o', $export['model']);
        $this->assertSame('backend', $export['category']);
    }

    public function test_to_export_array_usa_prompt_del_template_sin_override(): void
    {
        $template = AgentTemplate::factory()->create(['system_prompt' => 'Prompt del template.']);
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => null,
        ]);

        $this->assertSame('Prompt del template.', $assignment->toExportArray()['system_prompt']);
    }

    public function test_to_export_array_maneja_template_nulo(): void
    {
        $template = AgentTemplate::factory()->create();
        $assignment = ProjectAgent::factory()->create([
            'agent_template_id' => $template->id,
            'system_prompt_override' => 'Override',
        ]);
        // Forzamos la relacion a null para simular un borrado
        // en cascada: el export debe seguir devolviendo el
        // override y los campos del template como null.
        $assignment->setRelation('template', null);

        $export = $assignment->toExportArray();

        $this->assertNull($export['name']);
        $this->assertSame('Override', $export['system_prompt']);
    }
}
