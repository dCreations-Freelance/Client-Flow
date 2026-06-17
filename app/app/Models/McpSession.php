<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesion activa del MCP server.
 *
 * Cada conexion SSE genera una sesion unica. La sesion vincula
 * al usuario autenticado y actua como buzon para las respuestas
 * que el handler JSON-RPC deja pendientes.
 */
class McpSession extends Model
{
    /** @use HasFactory<\Database\Factories\McpSessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'last_activity_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Usuario propietario de la sesion.
     *
     * @return BelongsTo<User, McpSession>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mensajes SSE pendientes o ya enviados de esta sesion.
     *
     * @return HasMany<McpMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(McpMessage::class);
    }
}
