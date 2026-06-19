<?php

namespace Database\Factories;

use App\Models\AgentTemplate;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAgent>
 */
class ProjectAgentFactory extends Factory
{
    /**
     * Estado por defecto. Crea la asignacion sin override:
     * el prompt efectivo sera el del template. Es el caso
     * comun al poblar fixtures.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'agent_template_id' => AgentTemplate::factory(),
            'system_prompt_override' => null,
        ];
    }

    /**
     * Asocia un override concreto (o uno generado si no se
     * pasa). Pensado para tests que cubren la logica de
     * `effectiveSystemPrompt`.
     *
     * @return static
     */
    public function withOverride(?string $text = null): static
    {
        return $this->state(fn (): array => [
            'system_prompt_override' => $text ?? fake()->paragraphs(2, true),
        ]);
    }
}
