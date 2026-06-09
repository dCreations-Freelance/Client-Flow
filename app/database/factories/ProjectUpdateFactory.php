<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectUpdate>
 */
class ProjectUpdateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'author_id' => User::factory()->admin(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(2, true),
            'type' => 'update',
            'visibility' => ProjectUpdate::VISIBILITY_PUBLIC,
            'notify_client' => false,
            'published_at' => now(),
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => ProjectUpdate::VISIBILITY_INTERNAL,
        ]);
    }
}
