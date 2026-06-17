<?php

namespace App\Services\Mcp;

use App\Models\McpMessage;
use App\Models\McpSession;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Gestiona el ciclo de vida de las sesiones MCP.
 *
 * Abstrae la creacion de sesiones, el encolado de mensajes SSE y
 * la recuperacion de mensajes pendientes, permitiendo implementar
 * el transporte HTTP+SSE sin depender de Redis.
 */
class McpSessionStore
{
    /**
     * Crea una nueva sesion MCP para un usuario.
     *
     * @param  User  $user
     * @return McpSession
     */
    public function create(User $user): McpSession
    {
        return McpSession::create([
            'user_id' => $user->id,
            'session_id' => Str::uuid()->toString(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Busca una sesion activa por su identificador publico.
     *
     * @param  string  $sessionId
     * @return McpSession|null
     */
    public function find(string $sessionId): ?McpSession
    {
        return McpSession::where('session_id', $sessionId)->first();
    }

    /**
     * Encola un mensaje JSON-RPC para ser emitido por SSE.
     *
     * @param  McpSession  $session
     * @param  array<string, mixed>  $payload
     * @return McpMessage
     */
    public function push(McpSession $session, array $payload): McpMessage
    {
        return $session->messages()->create([
            'payload' => $payload,
            'sent_at' => null,
        ]);
    }

    /**
     * Recupera los mensajes pendientes de una sesion y los marca como
     * enviados en una sola operacion.
     *
     * @param  McpSession  $session
     * @return array<int, McpMessage>
     */
    public function pendingMessages(McpSession $session): array
    {
        $messages = $session->messages()
            ->whereNull('sent_at')
            ->orderBy('id')
            ->get();

        if ($messages->isEmpty()) {
            return [];
        }

        McpMessage::whereIn('id', $messages->pluck('id'))
            ->update(['sent_at' => now()]);

        return $messages->all();
    }

    /**
     * Actualiza el timestamp de ultima actividad de una sesion.
     *
     * @param  McpSession  $session
     * @return void
     */
    public function touch(McpSession $session): void
    {
        $session->update(['last_activity_at' => now()]);
    }

    /**
     * Elimina sesiones inactivas mas alla de un umbral en minutos.
     *
     * @param  int  $minutes
     * @return void
     */
    public function cleanup(int $minutes = 30): void
    {
        McpSession::where('last_activity_at', '<', now()->subMinutes($minutes))->delete();
    }
}
