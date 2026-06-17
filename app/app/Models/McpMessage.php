<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje SSE encolado para una sesion MCP.
 *
 * El handler JSON-RPC escribe respuestas aqui; el loop SSE las
 * lee, las emite al cliente y marca `sent_at` para evitar
 * reenvios.
 */
class McpMessage extends Model
{
    /** @use HasFactory<\Database\Factories\McpMessageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mcp_session_id',
        'payload',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Sesion a la que pertenece el mensaje.
     *
     * @return BelongsTo<McpSession, McpMessage>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(McpSession::class);
    }
}
