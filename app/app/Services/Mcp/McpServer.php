<?php

namespace App\Services\Mcp;

use App\Models\User;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Orquestador del protocolo MCP sobre HTTP/SSE.
 *
 * Recibe mensajes JSON-RPC, gestiona el handshake inicial, despacha
 * las invocaciones a las tools y construye las respuestas conforme
 * a la especificacion. No mantiene estado de negocio: delega en
 * McpSessionStore para la correlacion SSE.
 */
class McpServer
{
    private McpToolRegistry $registry;

    private McpSessionStore $sessions;

    /**
     * @param  McpToolRegistry  $registry
     * @param  McpSessionStore  $sessions
     */
    public function __construct(McpToolRegistry $registry, McpSessionStore $sessions)
    {
        $this->registry = $registry;
        $this->sessions = $sessions;
    }

    /**
     * Devuelve la respuesta inicial de handshake que el cliente MCP
     * espera tras conectarse al endpoint SSE.
     *
     * @param  string  $sessionId
     * @param  string  $messagesUrl
     * @return array<string, mixed>
     */
    public function handshake(string $sessionId, string $messagesUrl): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => Uuid::uuid4()->toString(),
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => (object) [],
                ],
                'serverInfo' => [
                    'name' => 'clientflow-mcp',
                    'version' => '1.0.0',
                ],
                'sessionId' => $sessionId,
                '_meta' => [
                    'messagesEndpoint' => $messagesUrl,
                ],
            ],
        ];
    }

    /**
     * Procesa un mensaje JSON-RPC entrante y devuelve la respuesta
     * lista para enviar por SSE.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleMessage(User $user, array $payload): array
    {
        if (! isset($payload['jsonrpc']) || $payload['jsonrpc'] !== '2.0') {
            return $this->errorResponse($payload['id'] ?? null, -32600, 'Version JSON-RPC invalida.');
        }

        $method = $payload['method'] ?? null;

        if (! is_string($method)) {
            return $this->errorResponse($payload['id'] ?? null, -32601, 'Metodo no especificado.');
        }

        return match ($method) {
            'initialize' => $this->handleInitialize($payload),
            'tools/list' => $this->handleToolsList($payload),
            'tools/call' => $this->handleToolCall($user, $payload),
            default => $this->errorResponse($payload['id'] ?? null, -32601, "Metodo no soportado: {$method}"),
        };
    }

    /**
     * Responde al metodo `initialize` del protocolo MCP.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function handleInitialize(array $payload): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $payload['id'] ?? null,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => (object) [],
                ],
                'serverInfo' => [
                    'name' => 'clientflow-mcp',
                    'version' => '1.0.0',
                ],
            ],
        ];
    }

    /**
     * Responde al metodo `tools/list` anunciando las tools disponibles.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function handleToolsList(array $payload): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $payload['id'] ?? null,
            'result' => [
                'tools' => $this->registry->definitions(),
            ],
        ];
    }

    /**
     * Responde al metodo `tools/call` ejecutando la tool solicitada.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function handleToolCall(User $user, array $payload): array
    {
        $params = $payload['params'] ?? [];
        $name = $params['name'] ?? null;

        if (! is_string($name) || $name === '') {
            return $this->errorResponse($payload['id'] ?? null, -32602, 'Nombre de tool no especificado.');
        }

        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        try {
            $result = $this->registry->execute($name, $user, $arguments);

            return [
                'jsonrpc' => '2.0',
                'id' => $payload['id'] ?? null,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        ],
                    ],
                    'isError' => false,
                ],
            ];
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($payload['id'] ?? null, -32601, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->errorResponse($payload['id'] ?? null, -32603, $e->getMessage());
        }
    }

    /**
     * Construye una respuesta de error JSON-RPC estandar.
     *
     * @param  string|int|null  $id
     * @param  int  $code
     * @param  string  $message
     * @return array<string, mixed>
     */
    private function errorResponse(string|int|null $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
