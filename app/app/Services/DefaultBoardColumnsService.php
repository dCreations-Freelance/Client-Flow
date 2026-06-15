<?php

namespace App\Services;

use App\Models\BoardColumn;
use App\Models\Project;

/**
 * Crea el conjunto de columnas por defecto para un proyecto.
 *
 * Las cuatro columnas canonicas (To Do, In Progress, Review, Done)
 * se crean con `is_default = true` y posiciones 0..3. El metodo
 * `ensure` es idempotente: si el proyecto ya tiene columnas no
 * duplica nada, lo que permite llamarlo en cualquier momento sin
 * preocupacion.
 */
class DefaultBoardColumnsService
{
    /**
     * Listado canonico de columnas por defecto. Cada entrada incluye
     * el nombre visible, el color opcional y la posicion.
     *
     * Los nombres estan en castellano siguiendo la convencion del
     * proyecto: la UI se muestra siempre en espanol.
     *
     * @var list<array{name: string, color: ?string, position: int}>
     */
    private const DEFAULTS = [
        ['name' => 'Por hacer', 'color' => '#94A3B8', 'position' => 0],
        ['name' => 'En curso', 'color' => '#2563EB', 'position' => 1],
        ['name' => 'En revision', 'color' => '#D97706', 'position' => 2],
        ['name' => 'Hecho', 'color' => '#16A34A', 'position' => 3],
    ];

    /**
     * Crea las columnas por defecto en el proyecto. Si ya existen
     * columnas no hace nada.
     *
     * @return void
     */
    public function ensure(Project $project): void
    {
        if ($project->columns()->exists()) {
            return;
        }

        $this->create($project);
    }

    /**
     * Crea las columnas por defecto sin comprobar si ya existen.
     * Pensado para uso forzado (seeders, comandos artisan).
     *
     * @return void
     */
    public function create(Project $project): void
    {
        foreach (self::DEFAULTS as $defaults) {
            BoardColumn::create([
                'project_id' => $project->id,
                'name' => $defaults['name'],
                'color' => $defaults['color'],
                'position' => $defaults['position'],
                'is_default' => true,
            ]);
        }
    }
}
