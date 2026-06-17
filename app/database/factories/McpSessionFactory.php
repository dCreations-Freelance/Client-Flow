<?php

namespace Database\Factories;

use App\Models\McpSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<McpSession>
 */
class McpSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'session_id' => fake()->uuid(),
            'last_activity_at' => now(),
        ];
    }
}
