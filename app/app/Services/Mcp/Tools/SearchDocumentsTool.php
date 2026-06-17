<?php

namespace App\Services\Mcp\Tools;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Services\Mcp\Contracts\McpTool;
use InvalidArgumentException;

/**
 * Tool MCP que busca documentos por titulo o contenido.
 */
class SearchDocumentsTool implements McpTool
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'search_documents';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Busca documentos de un proyecto por titulo o contenido markdown.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['project_id', 'query'],
            'properties' => [
                'project_id' => [
                    'type' => 'integer',
                    'description' => 'ID del proyecto.',
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'Termino de busqueda.',
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
        $query = trim((string) ($arguments['query'] ?? ''));

        if ($projectId <= 0) {
            throw new InvalidArgumentException('El project_id es obligatorio.');
        }

        if ($query === '') {
            throw new InvalidArgumentException('El termino de busqueda no puede estar vacio.');
        }

        $project = Project::find($projectId);

        if ($project === null || ! $user->can('view', $project)) {
            throw new InvalidArgumentException('Proyecto no encontrado o sin permisos.');
        }

        $documents = ProjectDocument::query()
            ->forProject($projectId)
            ->search($query)
            ->orderByDesc('id')
            ->get();

        return [
            'query' => $query,
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
