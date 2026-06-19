<?php

namespace App\Models;

use App\Enums\AiChatRole;
use Database\Factories\AiChatMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje individual dentro de una sesion de chat con el
 * asistente IA.
 *
 * Solo se persisten tres tipos:
 * - `user`: lo escribio el humano.
 * - `assistant`: lo genero el modelo.
 * - `system`: instrucciones persistentes inyectadas por
 *   `ProjectContextBuilder`. No se muestran en la UI.
 *
 * Los mensajes son inmutables: una vez creados no se editan.
 * Si el usuario quiere "corregir" un mensaje, el patron es
 * iniciar una nueva sesion o anadir un nuevo mensaje.
 */
class AiChatMessage extends Model
{
    /** @use HasFactory<AiChatMessageFactory> */
    use HasFactory;

    /**
     * El modelo solo mantiene `created_at`. No nos interesa
     * saber cuando se "actualizo" un mensaje historico.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_chat_session_id',
        'role',
        'content',
        'tokens_used',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AiChatRole::class,
            'tokens_used' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Sesion a la que pertenece el mensaje.
     *
     * @return BelongsTo<AiChatSession, AiChatMessage>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }

    /**
     * Determina si el mensaje fue escrito por el humano.
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->role instanceof AiChatRole && $this->role->isUser();
    }

    /**
     * Determina si el mensaje es una respuesta del modelo.
     *
     * @return bool
     */
    public function isAssistant(): bool
    {
        return $this->role instanceof AiChatRole && $this->role->isAssistant();
    }

    /**
     * Determina si el mensaje es una instruccion de sistema.
     * Estos mensajes no se renderizan en la UI: solo se
     * envian al provider para que el modelo tenga contexto.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->role instanceof AiChatRole && $this->role->isSystem();
    }
}
