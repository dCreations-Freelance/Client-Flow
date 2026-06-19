<?php

namespace Database\Factories;

use App\Models\AgentTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentTemplate>
 */
class AgentTemplateFactory extends Factory
{
    /**
     * Estado por defecto. Genera un nombre de tres palabras,
     * un system prompt multi-parrafo realista y deja `tools`
     * y `created_by` abiertos para que los tests o seeders
     * los configuren segun convenga.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'system_prompt' => implode("\n\n", fake()->paragraphs(4)),
            'tools' => null,
            'model' => fake()->randomElement(['gpt-4o', 'claude-3-5-sonnet-latest', null]),
            'category' => fake()->randomElement(['architecture', 'frontend', 'backend', 'review', null]),
            'created_by' => User::factory()->admin(),
        ];
    }

    /**
     * Asocia el template con un admin concreto. Util cuando
     * el test necesita un `creator` conocido.
     *
     * @return static
     */
    public function forCreator(User $user): static
    {
        return $this->state(fn (): array => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Fija la categoria del template.
     *
     * @return static
     */
    public function inCategory(string $category): static
    {
        return $this->state(fn (): array => [
            'category' => $category,
        ]);
    }

    /**
     * Asocia un set minimo de herramientas al template.
     *
     * @return static
     */
    public function withTools(): static
    {
        return $this->state(fn (): array => [
            'tools' => [
                ['name' => 'web_search', 'description' => 'Busca en la web'],
                ['name' => 'file_read', 'description' => 'Lee archivos del workspace'],
            ],
        ]);
    }
}
