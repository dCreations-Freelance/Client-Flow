<?php

namespace App\Services\Mcp\Tools;

use App\Models\Project;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Tool MCP que lista los proyectos accesibles para el usuario.
 *
 * Para administradores devuelve todos los proyectos; para clientes
 * solo los de sus organizaciones visibles. En la practica el MCP
 * esta restringido a admins, pero la tool respeta las policies.
 */
class ListProjectsTool implements McpTool
{
    /**
     * Nombre de la tool en el protocolo MCP.
     *
     * @return string
     */
    public function name(): string
    {
        return 'list_projects';
    }

    /**
     * Descripcion visible para el modelo de IA.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Lista los proyectos disponibles con paginacion y filtro opcional por estado.';
    }

    /**
     * Esquema JSON de los parametros de entrada.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Numero maximo de proyectos a devolver (default 50).',
                    'default' => 50,
                ],
                'offset' => [
                    'type' => 'integer',
                    'description' => 'Offset para paginacion (default 0).',
                    'default' => 0,
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Filtrar por estado del proyecto (planning, in_progress, on_hold, waiting_client, completed, archived).',
                    'nullable' => true,
                ],
            ],
        ];
    }

    /**
     * Ejecuta la consulta y devuelve la lista de proyectos.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function execute(User $user, array $arguments): array
    {
        $limit = (int) ($arguments['limit'] ?? 50);
        $offset = (int) ($arguments['offset'] ?? 0);
        $status = $arguments['status'] ?? null;

        if ($limit < 1 || $limit > 200) {
            throw new InvalidArgumentException('El limite debe estar entre 1 y 200.');
        }

        $query = Project::query()
            ->with('organization')
            ->when($user->isAdmin(), function (Builder $query): Builder {
                return $query;
            }, function (Builder $query) use ($user): Builder {
                return $query->forUser($user)->visibleToClient();
            })
            ->when(is_string($status) && $status !== '', function (Builder $query) use ($status): Builder {
                return $query->where('status', $status);
            })
            ->orderByDesc('id');

        $total = $query->count();
        $items = $query->limit($limit)->offset($offset)->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'projects' => $items->map(fn (Project $project): array => $this->serializeProject($project))->all(),
        ];
    }

    /**
     * Serializa un proyecto para la respuesta de la tool.
     *
     * @param  Project  $project
     * @return array<string, mixed>
     */
    private function serializeProject(Project $project): array
    {
        return [
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
        ];
    }
}
