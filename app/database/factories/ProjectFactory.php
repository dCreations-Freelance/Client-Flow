<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Estado por defecto: proyecto en planificacion dentro de una
     * organizacion recien creada, visible al cliente, sin progreso.
     * El slug se genera automaticamente en el evento `creating` del
     * modelo, por lo que no lo fijamos aqui para que respete el
     * nombre que se pase en `create([...])`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => ProjectStatus::Planning,
            'progress' => 0,
            'starts_at' => now()->toDateString(),
            'estimated_ends_at' => now()->addMonths(2)->toDateString(),
            'cover_path' => null,
            'is_visible_to_client' => true,
            'archived_at' => null,
        ];
    }

    /**
     * Marca el proyecto como en progreso con un progreso aleatorio.
     *
     * @return static
     */
    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::InProgress,
            'progress' => fake()->numberBetween(10, 80),
        ]);
    }

    /**
     * Marca el proyecto como completado.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::Completed,
            'progress' => 100,
        ]);
    }

    /**
     * Archiva el proyecto.
     *
     * @return static
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    /**
     * Marca el proyecto como oculto para clientes.
     *
     * @return static
     */
    public function hiddenFromClient(): static
    {
        return $this->state(fn (): array => [
            'is_visible_to_client' => false,
        ]);
    }
}
