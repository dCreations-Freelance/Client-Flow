<?php

namespace App\Services\Mcp;

use App\Services\Mcp\Contracts\McpTool;
use App\Services\Mcp\Tools\GetDocumentsTool;
use App\Services\Mcp\Tools\GetProjectStatusTool;
use App\Services\Mcp\Tools\GetProjectTool;
use App\Services\Mcp\Tools\GetTaskTool;
use App\Services\Mcp\Tools\ListProjectsTool;
use App\Services\Mcp\Tools\ListTasksTool;
use App\Services\Mcp\Tools\SearchDocumentsTool;
use InvalidArgumentException;

/**
 * Registro central de tools disponibles en el MCP server.
 *
 * Mantiene las instancias de las tools de solo lectura y permite
 * consultar su definicion o ejecutarlas por nombre.
 */
class McpToolRegistry
{
    /**
     * @var array<string, McpTool>
     */
    private array $tools = [];

    /**
     * Inicializa el registro con las tools soportadas por el MVP.
     */
    public function __construct()
    {
        $this->register(new ListProjectsTool);
        $this->register(new GetProjectTool);
        $this->register(new ListTasksTool);
        $this->register(new GetTaskTool);
        $this->register(new GetDocumentsTool);
        $this->register(new SearchDocumentsTool);
        $this->register(new GetProjectStatusTool);
    }

    /**
     * Registra una tool en el catalogo.
     *
     * @param  McpTool  $tool
     * @return void
     */
    private function register(McpTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Devuelve la lista de tools con su esquema, lista para anunciar
     * en las capabilities del servidor MCP.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->tools as $tool) {
            $definitions[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->schema(),
            ];
        }

        return $definitions;
    }

    /**
     * Ejecuta una tool por nombre, validando previamente que exista.
     *
     * @param  string  $name
     * @param  \App\Models\User  $user
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $name, $user, array $arguments): array
    {
        if (! isset($this->tools[$name])) {
            throw new InvalidArgumentException("Tool no encontrada: {$name}");
        }

        return $this->tools[$name]->execute($user, $arguments);
    }
}
