<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->sentence(3);

        return [
            'client_id' => Client::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraph(),
            'goal' => fake()->sentence(),
            'status' => 'planning',
            'progress' => fake()->numberBetween(0, 100),
            'current_phase' => 'Analisis inicial',
            'next_milestone' => 'Primera revision',
            'starts_at' => now()->toDateString(),
            'estimated_ends_at' => now()->addMonth()->toDateString(),
            'is_visible_to_client' => true,
        ];
    }

    public function hiddenFromClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible_to_client' => false,
        ]);
    }
}
