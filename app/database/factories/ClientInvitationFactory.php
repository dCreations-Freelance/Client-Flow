<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientInvitation>
 */
class ClientInvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
            'created_by' => User::factory()->admin(),
        ];
    }
}
