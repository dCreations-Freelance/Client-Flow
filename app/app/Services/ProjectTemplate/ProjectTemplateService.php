<?php

namespace App\Services\ProjectTemplate;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateColumn;
use App\Models\ProjectTemplateDocument;
use App\Models\ProjectTemplateTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio central de plantillas de proyecto.
 *
 * Centraliza la logica de:
 * - Aplicar una plantilla a un proyecto nuevo
 *   (copiar columnas, tareas y documentos).
 * - Listar las categorias distintas para alimentar
 *   los chips de filtro del listado.
 * - Helpers de posicionamiento (cual es la
 *   siguiente posicion libre en una coleccion).
 *
 * Mantener la logica aqui tiene dos objetivos:
 * 1. Evitar duplicar el orden de creacion
 *   (columnas -> tareas -> documentos) en cada
 *   controlador que aplique la plantilla.
 * 2. Poder testear la logica de copia sin tener
 *   que levantar HTTP.
 */
class ProjectTemplateService
{
    /**
     * Aplica la plantilla a un proyecto. Es
     * idempotente en el sentido de que la plantilla
     * no se modifica: solo se leen sus elementos y
     * se copian al proyecto destino.
     *
     * Orden de la copia (importante):
     * 1. Columnas: se crean primero porque las
     *    tareas referencian a la columna por su
     *    `position`. Necesitamos el `id` real de la
     *    columna recien creada para resolver el
     *    mapeo.
     * 2. Tareas: se crean con la `column_id` ya
     *    resuelta, dentro de la columna correcta.
     * 3. Documentos: son independientes de las
     *    columnas y se crean al final.
     *
     * @param  ProjectTemplate  $template
     * @param  Project  $project
     * @param  User  $actor  usuario que aplica la plantilla (se usa como `created_by` de los nuevos elementos)
     * @return array{columns: int, tasks: int, documents: int}
     */
    public function applyToProject(ProjectTemplate $template, Project $project, User $actor): array
    {
        // Cargamos las relaciones en una sola query
        // cada una para evitar N+1. El eager loading
        // con load() reusa lo ya cargado si es
        // posible.
        $template->loadMissing(['columns', 'tasks', 'documents']);

        $columnMap = $this->copyColumns($template, $project);
        $tasksCopied = $this->copyTasks($template, $project, $actor, $columnMap);
        $documentsCopied = $this->copyDocuments($template, $project, $actor);

        return [
            'columns' => count($columnMap),
            'tasks' => $tasksCopied,
            'documents' => $documentsCopied,
        ];
    }

    /**
     * Copia las columnas de la plantilla al proyecto.
     * Devuelve un mapa `position_original => columna_real`
     * para que `copyTasks` pueda resolver la columna
     * destino de cada tarea.
     *
     * @return array<int, BoardColumn>
     */
    private function copyColumns(ProjectTemplate $template, Project $project): array
    {
        $map = [];

        foreach ($template->columns as $templateColumn) {
            $column = BoardColumn::create([
                'project_id' => $project->id,
                'name' => $templateColumn->name,
                'slug' => BoardColumn::generateUniqueSlug((int) $project->id, $templateColumn->name),
                'color' => $templateColumn->color,
                'position' => $templateColumn->position,
                // Las columnas traidas de una plantilla
                // NO son "default" del sistema: son
                // personalizadas. Esto permite, en una
                // fase futura, distinguirlas si se
                // quiere ofrecer un "reset a columnas
                // por defecto" sin perder las de la
                // plantilla.
                'is_default' => false,
            ]);
            $map[(int) $templateColumn->position] = $column;
        }

        return $map;
    }

    /**
     * Copia las tareas de la plantilla al proyecto,
     * resolviendo la columna destino por su
     * `position` original.
     *
     * @param  array<int, BoardColumn>  $columnMap
     * @return int  numero de tareas copiadas
     */
    private function copyTasks(ProjectTemplate $template, Project $project, User $actor, array $columnMap): int
    {
        $count = 0;

        foreach ($template->tasks as $templateTask) {
            $column = $columnMap[(int) $templateTask->column_position] ?? null;
            if ($column === null) {
                // Si la tarea referencia una position
                // de columna que no existe (plantilla
                // mal formada), la saltamos. Esto
                // evita errores 500 por datos
                // inconsistentes.
                continue;
            }

            Task::create([
                'project_id' => $project->id,
                'column_id' => $column->id,
                'parent_id' => null,
                'title' => $templateTask->title,
                'description' => $templateTask->description,
                'priority' => $templateTask->priority,
                'type' => $templateTask->type,
                'estimated_hours' => $templateTask->estimated_hours,
                'actual_hours' => null,
                'total_logged_minutes' => 0,
                'due_date' => null,
                'position' => $templateTask->position,
                'assignee_id' => null,
                'completed_at' => null,
                'created_by' => $actor->id,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Copia los documentos de la plantilla al
     * proyecto, preservando visibilidad y orden.
     *
     * @return int
     */
    private function copyDocuments(ProjectTemplate $template, Project $project, User $actor): int
    {
        $count = 0;

        foreach ($template->documents as $templateDocument) {
            ProjectDocument::create([
                'project_id' => $project->id,
                'title' => $templateDocument->title,
                'content' => $templateDocument->content,
                'visibility' => $templateDocument->visibility,
                'created_by' => $actor->id,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Devuelve la lista de categorias distintas
     * presentes en la biblioteca, ordenadas
     * alfabeticamente. Pensado para alimentar los
     * chips del filtro del listado.
     *
     * Las categorias nulas o vacias se excluyen
     * (la vista tiene un chip "Todas" que
     * representa esa opcion).
     *
     * @return Collection<int, string>
     */
    public function categories(): Collection
    {
        return ProjectTemplate::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Query base del listado con los filtros
     * aplicados. Encapsula la logica para que el
     * controlador y los tests compartan la misma
     * semantica.
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ProjectTemplate>
     */
    public function queryWithFilters(?string $search, ?string $category)
    {
        $query = ProjectTemplate::query()
            ->with('creator')
            ->withCount(['columns', 'tasks', 'documents'])
            ->latest('updated_at');

        if ($search !== null && $search !== '') {
            $query->search($search);
        }

        if ($category !== null && $category !== '') {
            $query->inCategory($category);
        }

        return $query;
    }

    /**
     * Devuelve la siguiente `position` libre para
     * anadir una columna al final de la coleccion.
     * Pensado para los handlers de Livewire / HTTP
     * que reciben solo `name` y `color`.
     *
     * @return int
     */
    public function nextColumnPosition(ProjectTemplate $template): int
    {
        $max = $template->columns()->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    /**
     * Devuelve la siguiente `position` libre para
     * una tarea en una columna concreta.
     *
     * @return int
     */
    public function nextTaskPosition(ProjectTemplate $template, int $columnPosition): int
    {
        $max = $template->tasks()
            ->where('column_position', $columnPosition)
            ->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    /**
     * Devuelve la siguiente `position` libre para
     * un documento al final de la coleccion.
     *
     * @return int
     */
    public function nextDocumentPosition(ProjectTemplate $template): int
    {
        $max = $template->documents()->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
