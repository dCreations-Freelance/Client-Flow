<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Estado por defecto. Crea una organizacion activa con un nombre
     * aleatorio. El slug se genera automaticamente en el evento
     * `creating` del modelo, por lo que no lo fijamos aqui.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->sentence(),
            'logo_path' => null,
            'owner_id' => User::factory()->admin(),
            'status' => OrganizationStatus::Active,
        ];
    }

    /**
     * Marca la organizacion como inactiva.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => OrganizationStatus::Inactive,
        ]);
    }
}
