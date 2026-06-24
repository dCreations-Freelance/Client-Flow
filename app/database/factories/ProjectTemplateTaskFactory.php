<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTemplateTask>
 */
class ProjectTemplateTaskFactory extends Factory
{
    /**
     * Estado por defecto: tarea "feature" de
     * prioridad media en la columna 0.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => ProjectTemplate::factory(),
            'column_position' => 0,
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.6)->paragraph(),
            'type' => TaskType::Task,
            'priority' => TaskPriority::Medium,
            'estimated_hours' => fake()->optional(0.5)->numberBetween(1, 16),
            'position' => 0,
        ];
    }

    /**
     * Fija la plantilla a la que pertenece la tarea.
     *
     * @return static
     */
    public function forTemplate(ProjectTemplate $template): static
    {
        return $this->state(fn (): array => [
            'template_id' => $template->id,
        ]);
    }

    /**
     * Fija la columna destino (por su position en
     * la plantilla, no por id).
     *
     * @return static
     */
    public function inColumn(int $columnPosition): static
    {
        return $this->state(fn (): array => [
            'column_position' => $columnPosition,
        ]);
    }

    /**
     * Tarea de tipo bug. Util para tests de
     * distribucion por tipo.
     *
     * @return static
     */
    public function bug(): static
    {
        return $this->state(fn (): array => [
            'type' => TaskType::Bug,
        ]);
    }

    /**
     * Tarea de prioridad critica. Util para tests
     * de distribucion por prioridad.
     *
     * @return static
     */
    public function critical(): static
    {
        return $this->state(fn (): array => [
            'priority' => TaskPriority::Critical,
        ]);
    }
}
