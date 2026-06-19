<?php

namespace App\Models;

use Database\Factories\AiChatSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Sesion de chat con el asistente IA en el contexto de un
 * proyecto.
 *
 * Cada sesion agrupa un hilo completo de mensajes user/
 * assistant. Las sesiones son inmutables salvo por su titulo
 * (autogenerado o fijado por el usuario) y por los mensajes
 * que se anaden al chatear.
 */
class AiChatSession extends Model
{
    /** @use HasFactory<AiChatSessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'title',
    ];

    /**
     * Proyecto del que la sesion extrae contexto.
     *
     * @return BelongsTo<Project, AiChatSession>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Usuario propietario de la sesion.
     *
     * @return BelongsTo<User, AiChatSession>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mensajes que componen la sesion, en orden cronologico
     * ascendente por defecto.
     *
     * @return HasMany<AiChatMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class)->orderBy('id');
    }

    /**
     * Acceso rapido al ultimo mensaje de la sesion (util para
     * previsualizar en el sidebar de sesiones).
     *
     * @return HasOne<AiChatMessage>
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(AiChatMessage::class)->latestOfMany('id');
    }

    /**
     * Titulo a mostrar en la UI. Si el usuario no puso uno
     * propio, se devuelve un titulo autogenerado a partir
     * del primer mensaje del usuario (60 caracteres).
     *
     * @return string
     */
    public function displayTitle(): string
    {
        if (is_string($this->title) && trim($this->title) !== '') {
            return $this->title;
        }

        $firstUserMessage = $this->messages()
            ->where('role', \App\Enums\AiChatRole::User->value)
            ->orderBy('id')
            ->first();

        if ($firstUserMessage === null) {
            return 'Nueva conversacion';
        }

        $preview = trim($firstUserMessage->content);
        if (mb_strlen($preview) > 60) {
            $preview = mb_substr($preview, 0, 60).'...';
        }

        return $preview;
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Sesiones del usuario indicado.
     *
     * @param  Builder<AiChatSession>  $query
     * @param  int  $userId
     * @return Builder<AiChatSession>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Sesiones del proyecto indicado.
     *
     * @param  Builder<AiChatSession>  $query
     * @param  int  $projectId
     * @return Builder<AiChatSession>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Sesiones del usuario en un proyecto concreto, ordenadas
     * por la mas reciente primero. Patron de uso principal:
     * el sidebar del chat.
     *
     * @param  Builder<AiChatSession>  $query
     * @param  int  $userId
     * @param  int  $projectId
     * @return Builder<AiChatSession>
     */
    public function scopeForUserInProject(Builder $query, int $userId, int $projectId): Builder
    {
        return $query->forUser($userId)
            ->forProject($projectId)
            ->latest('updated_at');
    }
}
