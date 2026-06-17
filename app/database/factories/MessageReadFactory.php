<?php

namespace Database\Factories;

use App\Models\MessageRead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageRead>
 */
class MessageReadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => \App\Models\ProjectMessage::factory(),
            'user_id' => \App\Models\User::factory(),
            'read_at' => now(),
        ];
    }
}
