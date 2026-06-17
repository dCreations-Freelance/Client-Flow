<?php

namespace App\Services\Mcp\Tools;

use App\Models\Task;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use InvalidArgumentException;

/**
 * Tool MCP que devuelve el detalle completo de una tarea.
 */
class GetTaskTool implements McpTool
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'get_task';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Devuelve el detalle de una tarea concreta, incluyendo subtareas.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['task_id'],
            'properties' => [
                'task_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la tarea.',
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
        $taskId = (int) ($arguments['task_id'] ?? 0);

        if ($taskId <= 0) {
            throw new InvalidArgumentException('El task_id es obligatorio.');
        }

        $task = Task::with(['project', 'column', 'assignee', 'creator', 'subtasks'])->find($taskId);

        if ($task === null || ! $user->can('view', $task)) {
            throw new InvalidArgumentException('Tarea no encontrada o sin permisos.');
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->completed_at !== null ? 'completed' : 'pending',
            'priority' => $task->priority->value,
            'type' => $task->type->value,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => $task->actual_hours,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toDateTimeString(),
            'column' => $task->column?->name,
            'project' => [
                'id' => $task->project?->id,
                'name' => $task->project?->name,
            ],
            'assignee' => $task->assignee?->name,
            'creator' => $task->creator?->name,
            'subtasks' => $task->subtasks->map(fn (Task $subtask): array => [
                'id' => $subtask->id,
                'title' => $subtask->title,
                'status' => $subtask->completed_at !== null ? 'completed' : 'pending',
            ])->all(),
        ];
    }
}
