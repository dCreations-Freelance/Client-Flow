<?php

namespace Database\Factories;

use App\Enums\AiProvider;
use App\Models\AiConfig;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiConfig>
 */
class AiConfigFactory extends Factory
{
    /**
     * Estado por defecto: configuracion OpenAI sin proyecto
     * asociado (global), activa, con limites por defecto. En
     * los tests se reasignan estos valores segun convenga.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => null,
            'provider' => AiProvider::Openai,
            'api_key' => 'sk-test-'.str_repeat('a', 20),
            'model' => null,
            'system_prompt' => null,
            'is_active' => true,
            'max_messages_per_hour' => 20,
            'max_sessions_per_day' => 10,
        ];
    }

    /**
     * Limita la configuracion a un proyecto concreto.
     *
     * @return static
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Marca la configuracion como inactiva.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Cambia el provider.
     *
     * @return static
     */
    public function withProvider(AiProvider $provider): static
    {
        return $this->state(fn (): array => [
            'provider' => $provider,
        ]);
    }
}
