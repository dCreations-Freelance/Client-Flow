<?php

namespace App\Services\Mcp\Contracts;

use App\Models\User;

/**
 * Contrato que deben implementar todas las tools expuestas por
 * el MCP server de ClientFlow.
 */
interface McpTool
{
    /**
     * Nombre unico de la tool en el protocolo MCP.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Descripcion legible para el modelo de IA.
     *
     * @return string
     */
    public function description(): string;

    /**
     * Esquema JSON de los parametros que acepta la tool.
     *
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * Ejecuta la tool y devuelve el resultado serializable.
     *
     * @param  User  $user  Usuario autenticado que invoca la tool.
     * @param  array<string, mixed>  $arguments  Argumentos validados.
     * @return array<string, mixed>
     */
    public function execute(User $user, array $arguments): array;
}
