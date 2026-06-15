<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'column_id' => BoardColumn::factory(),
            'parent_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => TaskPriority::Medium,
            'type' => TaskType::Task,
            'estimated_hours' => null,
            'actual_hours' => null,
            'due_date' => null,
            'position' => 0,
            'assignee_id' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Tarea con prioridad critica.
     *
     * @return static
     */
    public function critical(): static
    {
        return $this->state(fn (): array => [
            'priority' => TaskPriority::Critical,
        ]);
    }

    /**
     * Tarea vencida (due_date ayer, no completada).
     *
     * @return static
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'due_date' => now()->subDay()->toDateString(),
            'completed_at' => null,
        ]);
    }

    /**
     * Tarea completada.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'completed_at' => now(),
        ]);
    }

    /**
     * Asigna la tarea a un usuario concreto.
     *
     * @return static
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => [
            'assignee_id' => $user->id,
        ]);
    }
}
