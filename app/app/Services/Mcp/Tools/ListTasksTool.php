<?php

namespace App\Services\Mcp\Tools;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Tool MCP que lista las tareas de un proyecto con filtros.
 */
class ListTasksTool implements McpTool
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'list_tasks';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Lista las tareas de un proyecto, filtrables por estado, prioridad o asignado.';
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
                'status' => [
                    'type' => 'string',
                    'description' => 'Filtrar por estado de la tarea (pending, completed).',
                    'nullable' => true,
                ],
                'priority' => [
                    'type' => 'string',
                    'description' => 'Filtrar por prioridad (critical, high, medium, low).',
                    'nullable' => true,
                ],
                'assignee_id' => [
                    'type' => 'integer',
                    'description' => 'Filtrar por ID del asignado.',
                    'nullable' => true,
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
        $status = $arguments['status'] ?? null;
        $priority = $arguments['priority'] ?? null;
        $assigneeId = $arguments['assignee_id'] ?? null;

        if ($projectId <= 0) {
            throw new InvalidArgumentException('El project_id es obligatorio.');
        }

        $project = Project::find($projectId);

        if ($project === null || ! $user->can('view', $project)) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permisos.');
        }

        $query = Task::query()
            ->where('project_id', $projectId)
            ->with(['column', 'assignee'])
            ->when($status === 'completed', function (Builder $query): Builder {
                return $query->whereNotNull('completed_at');
            })
            ->when($status === 'pending', function (Builder $query): Builder {
                return $query->whereNull('completed_at');
            })
            ->when(is_string($priority) && $priority !== '', function (Builder $query) use ($priority): Builder {
                return $query->where('priority', $priority);
            })
            ->when(is_numeric($assigneeId), function (Builder $query) use ($assigneeId): Builder {
                return $query->where('assignee_id', (int) $assigneeId);
            })
            ->orderByDesc('id');

        return [
            'total' => $query->count(),
            'tasks' => $query->get()->map(fn (Task $task): array => $this->serializeTask($task))->all(),
        ];
    }

    /**
     * @param  Task  $task
     * @return array<string, mixed>
     */
    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->completed_at !== null ? 'completed' : 'pending',
            'priority' => $task->priority->value,
            'type' => $task->type->value,
            'column' => $task->column?->name,
            'assignee' => $task->assignee?->name,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toDateTimeString(),
            'parent_id' => $task->parent_id,
        ];
    }
}
