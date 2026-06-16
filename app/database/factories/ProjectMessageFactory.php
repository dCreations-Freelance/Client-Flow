<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMessage>
 */
class ProjectMessageFactory extends Factory
{
    /**
     * Estado por defecto: mensaje de texto de un usuario, dentro de
     * un proyecto. Pensado para tests y seeders.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'type' => MessageType::Text,
        ];
    }

    /**
     * Mensaje de sistema: sin autor y con el tipo adecuado. Usado
     * para representar eventos automaticos (tarea creada, etc.).
     *
     * @return static
     */
    public function system(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'type' => MessageType::System,
            'content' => fake()->sentence(),
        ]);
    }

    /**
     * Mensaje de un usuario concreto.
     *
     * @return static
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
