<?php

namespace Database\Factories;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    /**
     * Estado por defecto: preferencia con ambos canales activos
     * para un evento aleatorio del enum. Se usa en tests cuando
     * solo necesitamos "una preferencia que existe" sin
     * importarnos el evento concreto.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = fake()->unique()->randomElement(NotificationEvent::cases());

        return [
            'user_id' => User::factory(),
            'event' => $event->value,
            'in_app' => true,
            'email' => true,
        ];
    }

    /**
     * Estado con ambos canales desactivados. Util para tests
     * que verifican el opt-out completo: la notificacion no
     * se envia por ningun canal.
     *
     * @return static
     */
    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'in_app' => false,
            'email' => false,
        ]);
    }

    /**
     * Estado solo in-app activo. Pensado para tests que
     * verifican que el email se omite pero la campana sigue
     * sonando.
     *
     * @return static
     */
    public function inAppOnly(): static
    {
        return $this->state(fn (): array => [
            'in_app' => true,
            'email' => false,
        ]);
    }

    /**
     * Estado solo email activo. Complemento del anterior.
     *
     * @return static
     */
    public function emailOnly(): static
    {
        return $this->state(fn (): array => [
            'in_app' => false,
            'email' => true,
        ]);
    }

    /**
     * Fija el evento de la preferencia. Por defecto la factory
     * elige uno aleatorio, pero en tests solemos querer uno
     * concreto para aserciones deterministas.
     *
     * @return static
     */
    public function forEvent(NotificationEvent $event): static
    {
        return $this->state(fn (): array => [
            'event' => $event->value,
        ]);
    }

    /**
     * Asocia la preferencia a un usuario concreto. Acepta tanto
     * una instancia de `User` como un id.
     *
     * @return static
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
