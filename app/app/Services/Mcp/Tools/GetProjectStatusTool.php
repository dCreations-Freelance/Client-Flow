<?php

namespace App\Services\Mcp\Tools;

use App\Models\Project;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use InvalidArgumentException;

/**
 * Tool MCP que devuelve un resumen ejecutivo del estado de un proyecto.
 */
class GetProjectStatusTool implements McpTool
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'get_project_status';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Resumen de estado de un proyecto: progreso, tareas, fechas y documentos.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => [
                    'type' => 'integer',
                    'description' => 'ID del proyecto.',
                ],
            ],
        ];
    }

    /**
     * @param  User  $user
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function execute(User $user, array $arguments): array
    {
        $projectId = (int) ($arguments['project_id'] ?? 0);

        if ($projectId <= 0) {
            throw new InvalidArgumentException('El project_id es obligatorio.');
        }

        $project = Project::with(['organization'])->find($projectId);

        if ($project === null || ! $user->can('view', $project)) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permisos.');
        }

        $tasks = $project->tasks();
        $totalTasks = $tasks->count();
        $completedTasks = (clone $tasks)->whereNotNull('completed_at')->count();
        $pendingTasks = $totalTasks - $completedTasks;

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'status' => $project->status->value,
                'progress' => $project->progress,
                'organization' => [
                    'id' => $project->organization?->id,
                    'name' => $project->organization?->name,
                ],
                'starts_at' => $project->starts_at?->toDateString(),
                'estimated_ends_at' => $project->estimated_ends_at?->toDateString(),
                'archived_at' => $project->archived_at?->toDateTimeString(),
            ],
            'tasks' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'pending' => $pendingTasks,
            ],
            'documents' => [
                'total' => $project->documents()->count(),
                'public' => $project->documents()->public()->count(),
                'private' => $project->documents()->private()->count(),
            ],
            'members' => $project->members()->count(),
        ];
    }
}
