<?php

namespace Database\Factories;

use App\Models\McpMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<McpMessage>
 */
class McpMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mcp_session_id' => \App\Models\McpSession::factory(),
            'payload' => ['jsonrpc' => '2.0', 'id' => fake()->uuid(), 'result' => []],
            'sent_at' => null,
        ];
    }
}
