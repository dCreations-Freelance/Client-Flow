<?php

namespace Database\Factories;

use App\Models\AiChatSession;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiChatSession>
 */
class AiChatSessionFactory extends Factory
{
    /**
     * Estado por defecto: sesion sin titulo, asociada a un
     * proyecto y un usuario. El titulo se autogenera con
     * `displayTitle()` a partir del primer mensaje del
     * usuario cuando proceda.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'title' => null,
        ];
    }

    /**
     * Asocia la sesion a un proyecto concreto.
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
     * Asocia la sesion a un usuario concreto.
     *
     * @return static
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Titulo puesto por el usuario (en vez del autogenerado).
     *
     * @return static
     */
    public function titled(string $title): static
    {
        return $this->state(fn (): array => [
            'title' => $title,
        ]);
    }
}
