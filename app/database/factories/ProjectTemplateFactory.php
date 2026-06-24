<?php

namespace Database\Factories;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectTemplate>
 */
class ProjectTemplateFactory extends Factory
{
    /**
     * Estado por defecto: plantilla con nombre y
     * slug coherentes, descripcion opcional, sin
     * categoria.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->catchPhrase();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'category' => null,
            'created_by' => User::factory()->admin(),
        ];
    }

    /**
     * Marca la plantilla con una categoria concreta.
     * Pensado para tests del filtro por categoria.
     *
     * @param  string  $category
     * @return static
     */
    public function inCategory(string $category): static
    {
        return $this->state(fn (): array => [
            'category' => $category,
        ]);
    }

    /**
     * Anade una descripcion larga. Util para tests
     * de busqueda por texto.
     *
     * @return static
     */
    public function withDescription(): static
    {
        return $this->state(fn (): array => [
            'description' => fake()->paragraphs(3, true),
        ]);
    }
}
