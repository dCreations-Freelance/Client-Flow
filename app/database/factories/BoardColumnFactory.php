<?php

namespace Database\Factories;

use App\Models\BoardColumn;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardColumn>
 */
class BoardColumnFactory extends Factory
{
    /**
     * Estado por defecto: columna generica en planificacion.
     * `slug` se genera en el evento `creating` del modelo para
     * respetar nombres pasados en `create([...])`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'project_id' => Project::factory(),
            'name' => ucfirst($name),
            'color' => null,
            'position' => 0,
            'is_default' => false,
        ];
    }

    /**
     * Marca la columna como parte del set default.
     *
     * @return static
     */
    public function default(): static
    {
        return $this->state(fn (): array => [
            'is_default' => true,
        ]);
    }
}
