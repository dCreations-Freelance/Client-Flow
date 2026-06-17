<?php

namespace App\Services\Mcp\Tools;

use App\Models\Project;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use InvalidArgumentException;

/**
 * Tool MCP que devuelve el detalle completo de un proyecto.
 */
class GetProjectTool implements McpTool
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'get_project';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Devuelve el detalle de un proyecto incluyendo organizacion, progreso y fechas.';
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
            throw new InvalidArgumentException('El project_id es obligatorio y debe ser mayor a cero.');
        }

        $project = Project::with(['organization', 'columns', 'members'])->find($projectId);

        if ($project === null || ! $user->can('view', $project)) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permisos.');
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'description' => $project->description,
            'status' => $project->status->value,
            'progress' => $project->progress,
            'is_visible_to_client' => $project->is_visible_to_client,
            'archived_at' => $project->archived_at?->toDateTimeString(),
            'starts_at' => $project->starts_at?->toDateString(),
            'estimated_ends_at' => $project->estimated_ends_at?->toDateString(),
            'organization' => [
                'id' => $project->organization?->id,
                'name' => $project->organization?->name,
            ],
            'columns' => $project->columns->map(fn ($column): array => [
                'id' => $column->id,
                'name' => $column->name,
                'position' => $column->position,
            ])->all(),
            'members_count' => $project->members->count(),
            'tasks_count' => $project->tasks()->count(),
            'documents_count' => $project->documents()->count(),
        ];
    }
}
