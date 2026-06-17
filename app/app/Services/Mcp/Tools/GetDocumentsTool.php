<?php

namespace App\Services\Mcp\Tools;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use InvalidArgumentException;

/**
 * Tool MCP que devuelve los documentos de un proyecto.
 *
 * El MCP server esta restringido a administradores, por lo que
 * esta tool incluye documentos privados junto a los publicos.
 */
class GetDocumentsTool implements McpTool
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'get_documents';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Devuelve los documentos de un proyecto, incluyendo los privados.';
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

        $project = Project::find($projectId);

        if ($project === null || ! $user->can('view', $project)) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permisos.');
        }

        $documents = ProjectDocument::query()
            ->where('project_id', $projectId)
            ->orderByDesc('id')
            ->get();

        return [
            'total' => $documents->count(),
            'documents' => $documents->map(fn (ProjectDocument $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'visibility' => $document->visibility->value,
                'updated_at' => $document->updated_at->toDateTimeString(),
                'excerpt' => $document->excerpt(200),
            ])->all(),
        ];
    }
}
