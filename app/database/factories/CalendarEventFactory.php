<?php

namespace Database\Factories;

use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * Estado por defecto: meeting dentro de un proyecto, con
     * duracion de 30-180 minutos, en horario laboral del dia
     * siguiente. Pensado para que los seeders y tests generen
     * datos realistas sin esfuerzo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::now()
            ->addDays($this->faker->numberBetween(1, 30))
            ->setTime($this->faker->numberBetween(9, 17), 0);

        $endsAt = $startsAt->copy()
            ->addMinutes($this->faker->numberBetween(30, 180));

        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->randomElement([
                'Reunion de kickoff',
                'Demo de avance',
                'Sprint review',
                'Planificacion semanal',
                'Llamada con cliente',
                'Workshop tecnico',
            ]),
            'description' => $this->faker->optional(0.6)->sentence(),
            'type' => CalendarEventType::Meeting,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_all_day' => false,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Evento de tipo milestone.
     *
     * @return static
     */
    public function milestone(): static
    {
        return $this->state(fn (): array => [
            'type' => CalendarEventType::Milestone,
            'title' => $this->faker->randomElement([
                'Lanzamiento MVP',
                'Entrega de fase 1',
                'Release v1.0',
                'Hito de diseno',
            ]),
            'is_all_day' => true,
        ]);
    }

    /**
     * Evento de todo un dia: `is_all_day = true` y horas
     * normalizadas a 00:00 / 23:59.
     *
     * @return static
     */
    public function allDay(): static
    {
        return $this->state(function (): array {
            $date = Carbon::now()->addDays($this->faker->numberBetween(1, 60));
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();

            return [
                'is_all_day' => true,
                'starts_at' => $start,
                'ends_at' => $end,
            ];
        });
    }

    /**
     * Fija el proyecto al que pertenece el evento. Pensado
     * para que el seeder pueda crear varios eventos dentro
     * del mismo proyecto.
     *
     * @return static
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Fija el creador del evento. Util para testear
     * notificaciones (no se envia al propio emisor).
     *
     * @return static
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (): array => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Evento en el pasado. Pensado para testear estados
     * `isPast()` y para llenar historiales.
     *
     * @return static
     */
    public function inPast(): static
    {
        return $this->state(function (): array {
            $startsAt = Carbon::now()
                ->subDays($this->faker->numberBetween(1, 60))
                ->setTime($this->faker->numberBetween(9, 17), 0);

            return [
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($this->faker->numberBetween(30, 180)),
            ];
        });
    }

    /**
     * Evento futuro lejano. Pensado para testear
     * `upcoming()` con limites.
     *
     * @return static
     */
    public function inFuture(): static
    {
        return $this->state(function (): array {
            $startsAt = Carbon::now()
                ->addDays($this->faker->numberBetween(60, 365))
                ->setTime($this->faker->numberBetween(9, 17), 0);

            return [
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($this->faker->numberBetween(30, 180)),
            ];
        });
    }
}
