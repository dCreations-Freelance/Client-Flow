<?php

namespace App\DTOs\Project;

use App\Models\BoardColumn;
use Illuminate\Support\Collection;

/**
 * Mini-vista de una columna del kanban para el preview del hub.
 *
 * Contiene la columna y hasta 3 tareas raiz para no inflar la
 * pagina. El contador total de la columna se obtiene aparte en
 * `ProjectSummary::$columnCounts` para que la vista pueda
 * mostrar "5" aunque solo vea 3 tarjetas.
 */
final class BoardColumnPreview
{
    /**
     * @param  Collection<int, \App\Models\Task>  $previewTasks
     */
    public function __construct(
        public readonly BoardColumn $column,
        public readonly Collection $previewTasks,
    ) {}
}
