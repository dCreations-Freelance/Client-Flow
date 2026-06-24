<?php

namespace Database\Factories;

use App\Enums\TimeEntryType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Estado por defecto: entrada manual de 30 minutos
     * asociada a una tarea recien creada, sin facturar y
     * con una descripcion humana.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'description' => fake()->optional(0.7)->sentence(),
            'type' => TimeEntryType::Manual,
            'minutes' => fake()->numberBetween(15, 180),
            'started_at' => null,
            'billed' => false,
        ];
    }

    /**
     * Entrada de temporizador: lleva `started_at` con la
     * marca de inicio y un tipo explicito.
     *
     * @return static
     */
    public function timer(): static
    {
        return $this->state(fn (): array => [
            'type' => TimeEntryType::Timer,
            'started_at' => now()->subMinutes(fake()->numberBetween(5, 240)),
        ]);
    }

    /**
     * Entrada marcada como facturable.
     *
     * @return static
     */
    public function billable(): static
    {
        return $this->state(fn (): array => [
            'billed' => true,
        ]);
    }

    /**
     * Fija la tarea a la que pertenece la entrada. Se
     * usa en los tests para evitar que el factory cree
     * tareas aleatorias que no son las del test.
     *
     * @return static
     */
    public function forTask(Task $task): static
    {
        return $this->state(fn (): array => [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }

    /**
     * Fija el autor de la entrada. Util para tests de
     * autorizacion y de dashboards por miembro.
     *
     * @return static
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Fija la duracion exacta en minutos. Pensado para
     * tests que validan sumas exactas.
     *
     * @return static
     */
    public function minutes(int $minutes): static
    {
        return $this->state(fn (): array => [
            'minutes' => $minutes,
        ]);
    }

    /**
     * Entrada creada hoy. Pensado para tests de
     * dashboard con filtro por dia.
     *
     * @return static
     */
    public function today(): static
    {
        return $this->state(fn (): array => [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Entrada creada hace N dias. Pensado para tests
     * de dashboard con filtro por rango de fechas.
     *
     * @return static
     */
    public function daysAgo(int $days): static
    {
        return $this->state(fn (): array => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
