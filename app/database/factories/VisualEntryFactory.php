<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\VisualEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisualEntry>
 */
class VisualEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'author_id' => User::factory()->admin(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => 'annotated_capture',
            'media_path' => 'clientflow/projects/1/visual/example.jpg',
            'media_file_name' => 'example.jpg',
            'media_mime_type' => 'image/jpeg',
            'media_size' => 1024,
            'visibility' => VisualEntry::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => VisualEntry::VISIBILITY_INTERNAL,
        ]);
    }
}
