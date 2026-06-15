<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\DefaultBoardColumnsService;
use Illuminate\Console\Command;

/**
 * Crea las columnas por defecto del kanban en proyectos que aun
 * no las tienen.
 *
 * Pensado para proyectos creados antes de fase 3 (cuando el alta
 * de proyecto no generaba columnas) y para entornos donde el
 * seeder dejo proyectos sin board. Es idempotente: si el proyecto
 * ya tiene columnas, las respeta.
 */
class EnsureBoardColumns extends Command
{
    /**
     * @var string
     */
    protected $signature = 'clientflow:ensure-board-columns {--all : procesa todos los proyectos}';

    /**
     * @var string
     */
    protected $description = 'Crea las columnas por defecto del kanban en proyectos que no las tengan';

    /**
     * Logica principal. Si se pasa `--all` procesa todos los
     * proyectos sin columnas. Sin flag, procesa los proyectos sin
     * columnas y los muestra uno a uno.
     */
    public function handle(DefaultBoardColumnsService $service): int
    {
        $query = Project::doesntHave('columns');

        if (! $this->option('all')) {
            $projects = $query->get();
            if ($projects->isEmpty()) {
                $this->info('No hay proyectos sin columnas. Todo al dia.');

                return self::SUCCESS;
            }

            $this->info('Proyectos sin columnas: '.$projects->count());
            foreach ($projects as $project) {
                $service->create($project);
                $this->line("  - {$project->name} ({$project->id})");
            }

            $this->info('Columnas creadas. Total procesado: '.$projects->count());

            return self::SUCCESS;
        }

        // Modo --all: procesa todos.
        $count = 0;
        Project::chunk(50, function ($projects) use ($service, &$count): void {
            foreach ($projects as $project) {
                $service->ensure($project);
                $count++;
            }
        });

        $this->info("Procesados {$count} proyectos.");

        return self::SUCCESS;
    }
}
