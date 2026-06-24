<?php

namespace Database\Factories;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTemplateColumn>
 */
class ProjectTemplateColumnFactory extends Factory
{
    /**
     * Estado por defecto: columna "Por hacer" en
     * posicion 0. Pensado para que el factory
     * genere datos realistas sin configuracion.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => ProjectTemplate::factory(),
            'name' => fake()->randomElement([
                'Por hacer',
                'En curso',
                'En revision',
                'Hecho',
                'Backlog',
                'Bloqueado',
            ]),
            'color' => fake()->randomElement([
                '#94A3B8',
                '#2563EB',
                '#D97706',
                '#16A34A',
                '#7C3AED',
            ]),
            'position' => 0,
        ];
    }

    /**
     * Fija la plantilla a la que pertenece la
     * columna. Pensado para que el test no genere
     * plantillas aleatorias.
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
     * Fija la posicion. Util para tests que
     * validan el orden de aplicacion.
     *
     * @return static
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (): array => [
            'position' => $position,
        ]);
    }
}
