<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Password estatico para evitar hashear la misma cadena en cada llamada.
     * Tests y seeders lo comparten; en produccion cada usuario debe tener
     * su propio hash.
     */
    protected static ?string $password;

    /**
     * Estado por defecto. Crea un usuario con rol `client`, el caso comun
     * al poblar fixtures en tests y seeders.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Client,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Crea un usuario con rol de administrador.
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * Crea un usuario con rol de cliente (estado explicito, equivalente al
     * por defecto pero util en tests donde se quiere ser explicito).
     *
     * @return static
     */
    public function client(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Client,
        ]);
    }

    /**
     * Marca al usuario como no verificado. Util para tests de verificacion
     * de email si se anade ese flujo en una fase futura.
     *
     * @return static
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
