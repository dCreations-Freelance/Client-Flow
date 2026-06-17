<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McpSession;
use App\Services\Mcp\McpServer;
use App\Services\Mcp\McpSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador del MCP server.
 *
 * Expone dos endpoints:
 * - `/api/mcp/sse`: establece el stream SSE y entrega el endpoint
 *   de mensajes al cliente.
 * - `/api/mcp/messages`: recibe mensajes JSON-RPC, los procesa y
 *   encola la respuesta para que el stream SSE la emita.
 */
class McpController extends Controller
{
    private McpServer $server;

    private McpSessionStore $sessions;

    /**
     * @param  McpServer  $server
     * @param  McpSessionStore  $sessions
     */
    public function __construct(McpServer $server, McpSessionStore $sessions)
    {
        $this->server = $server;
        $this->sessions = $sessions;
    }

    /**
     * Endpoint SSE para el transporte MCP.
     *
     * Crea una sesion, envia el endpoint de mensajes y mantiene la
     * conexion abierta emitiendo heartbeats y mensajes encolados.
     *
     * @param  Request  $request
     * @return StreamedResponse
     */
    public function sse(Request $request): StreamedResponse
    {
        $user = $request->user();
        $session = $this->sessions->create($user);

        $messagesUrl = URL::route('api.mcp.messages', ['session_id' => $session->session_id]);

        $handshake = $this->server->handshake($session->session_id, $messagesUrl);

        $response = new StreamedResponse(function () use ($session, $handshake, $messagesUrl): void {
            set_time_limit(0);
            ignore_user_abort(false);

            $this->emitSse('endpoint', $messagesUrl);
            $this->emitSse('message', json_encode($handshake, JSON_UNESCAPED_UNICODE));

            $start = time();
            // En tests reducimos la duracion al minimo para evitar
            // bloquear la suite; en produccion mantenemos 60s.
            $maxDuration = app()->runningUnitTests() ? 0 : 60;
            $heartbeatInterval = 15;
            $lastHeartbeat = $start;

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $now = time();

                if ($now - $start > $maxDuration) {
                    break;
                }

                if ($now - $lastHeartbeat >= $heartbeatInterval) {
                    $this->emitSse('heartbeat', json_encode(['timestamp' => $now]));
                    $lastHeartbeat = $now;
                }

                $this->sessions->touch($session);

                $messages = $this->sessions->pendingMessages($session);
                foreach ($messages as $message) {
                    $this->emitSse('message', json_encode($message->payload, JSON_UNESCAPED_UNICODE));
                }

                sleep(1);
            }

            $this->emitSse('close', json_encode(['reason' => 'timeout']));
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Endpoint JSON-RPC del MCP server.
     *
     * Recibe el payload, identifica la sesion por query string y
     * encola la respuesta para que el stream SSE la entregue.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function messages(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');

        if (! is_string($sessionId) || $sessionId === '') {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32602,
                    'message' => 'session_id no proporcionado.',
                ],
            ], 400);
        }

        $session = $this->sessions->find($sessionId);

        if ($session === null) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32602,
                    'message' => 'Sesion no encontrada o expirada.',
                ],
            ], 404);
        }

        $payload = $request->json()->all();

        if (! is_array($payload)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Payload JSON invalido.',
                ],
            ], 400);
        }

        try {
            $response = $this->server->handleMessage($request->user(), $payload);
        } catch (\Throwable $e) {
            Log::error('Error en MCP messages', ['exception' => $e]);

            $response = [
                'jsonrpc' => '2.0',
                'id' => $payload['id'] ?? null,
                'error' => [
                    'code' => -32603,
                    'message' => 'Error interno del servidor MCP.',
                ],
            ];
        }

        $this->sessions->push($session, $response);
        $this->sessions->touch($session);

        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $payload['id'] ?? null,
            'result' => 'accepted',
        ]);
    }

    /**
     * Escribe una linea de evento SSE en el buffer de salida.
     *
     * @param  string  $event
     * @param  string  $data
     * @return void
     */
    private function emitSse(string $event, string $data): void
    {
        echo "event: {$event}\n";
        echo 'data: '.str_replace("\n", "\ndata: ", $data)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
