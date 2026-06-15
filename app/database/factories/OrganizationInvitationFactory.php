<?php

namespace Database\Factories;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * Estado por defecto. Genera un token "en claro" (solo para tests)
     * y guarda su hash en la columna `token`. El atributo crudo se
     * conserva temporalmente en memoria para que los tests puedan
     * aceptar la invitacion sin tener que recalcular el hash.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawToken = Str::random(64);

        return [
            'organization_id' => Organization::factory(),
            'email' => fake()->safeEmail(),
            'token' => hash('sha256', $rawToken),
            'role' => OrganizationUserRole::Member,
            'expires_at' => Carbon::now()->addDays(7),
            'accepted_at' => null,
            'created_by' => User::factory()->admin(),
        ];
    }

    /**
     * Marca la invitacion como expirada para tests de validacion.
     *
     * @return static
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => Carbon::now()->subDay(),
        ]);
    }

    /**
     * Marca la invitacion como ya aceptada.
     *
     * @return static
     */
    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'accepted_at' => Carbon::now(),
        ]);
    }
}
