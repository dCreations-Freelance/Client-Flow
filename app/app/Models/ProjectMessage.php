<?php

namespace App\Models;

use App\Enums\MessageType;
use Database\Factories\ProjectMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mensaje del chat de un proyecto.
 *
 * Cada mensaje pertenece a un proyecto y, si es de tipo `text` o
 * `file`, a un usuario autor. Los mensajes de `system` se generan
 * automaticamente (tarea creada, completada, etc.) y tienen
 * `user_id = null` y `type = system`.
 *
 * El contenido se almacena tal cual se envia. Para los mensajes de
 * texto se aplica escape HTML en la vista (via `e()`) para evitar
 * XSS; los mensajes de sistema son generados por la app y se
 * renderizan directamente.
 */
class ProjectMessage extends Model
{
    /** @use HasFactory<ProjectMessageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'content',
        'type',
    ];

    /**
     * Casts: el enum `type` para comparaciones type-safe en policies
     * y vistas.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
        ];
    }

    /**
     * Proyecto al que pertenece el mensaje.
     *
     * @return BelongsTo<Project, ProjectMessage>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Usuario autor del mensaje. Es null en mensajes de sistema.
     *
     * @return BelongsTo<User, ProjectMessage>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registros de lectura de este mensaje por parte de los
     * usuarios del proyecto.
     *
     * @return HasMany<MessageRead>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determina si el mensaje es de tipo texto escrito por un usuario.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this->type?->isText() ?? false;
    }

    /**
     * Determina si el mensaje es un evento automatico del sistema.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->type?->isSystem() ?? false;
    }

    /**
     * Determina si el mensaje es un archivo adjunto (reservado).
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return $this->type?->isFile() ?? false;
    }

    /**
     * Compara el autor del mensaje con un id de usuario. Pensado
     * para alineacion de burbujas en la vista: el admin que envia
     * un mensaje lo vera a la derecha, los demas a la izquierda.
     *
     * @param  int|null  $userId
     * @return bool
     */
    public function isFromUserId(?int $userId): bool
    {
        return $userId !== null && $this->user_id === $userId;
    }

    /**
     * Determina si el mensaje ha sido leido por un usuario concreto.
     *
     * @param  User  $user
     * @return bool
     */
    public function isReadBy(User $user): bool
    {
        return $this->reads()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determina si alguien distinto al emisor ha leido el mensaje.
     *
     * Es la condicion que activa el doble check de "visto" en las
     * burbujas propias. El emisor ya ve su propio mensaje al
     * enviarlo, asi que no cuenta como lectura de "otro".
     *
     * @param  User  $currentUser
     * @return bool
     */
    public function readByAnyoneElse(User $currentUser): bool
    {
        return $this->reads()
            ->where('user_id', '!=', $currentUser->id)
            ->exists();
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Solo mensajes de texto humano.
     *
     * @param  Builder<ProjectMessage>  $query
     * @return Builder<ProjectMessage>
     */
    public function scopeText(Builder $query): Builder
    {
        return $query->where('type', MessageType::Text->value);
    }

    /**
     * Solo mensajes automaticos del sistema.
     *
     * @param  Builder<ProjectMessage>  $query
     * @return Builder<ProjectMessage>
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('type', MessageType::System->value);
    }

    /**
     * Mensajes con id estrictamente menor al indicado. Util para
     * paginar hacia atras en el historial.
     *
     * @param  Builder<ProjectMessage>  $query
     * @param  int  $messageId
     * @return Builder<ProjectMessage>
     */
    public function scopeBefore(Builder $query, int $messageId): Builder
    {
        return $query->where('id', '<', $messageId);
    }

    /**
     * Mensajes con id estrictamente mayor al indicado. Util para
     * "no leidos hasta cierto id".
     *
     * @param  Builder<ProjectMessage>  $query
     * @param  int  $messageId
     * @return Builder<ProjectMessage>
     */
    public function scopeAfter(Builder $query, int $messageId): Builder
    {
        return $query->where('id', '>', $messageId);
    }

    /**
     * Orden cronologico ascendente (los mas antiguos primero). Pensado
     * para renderizar el chat de arriba (antiguo) hacia abajo (nuevo).
     *
     * @param  Builder<ProjectMessage>  $query
     * @return Builder<ProjectMessage>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('id');
    }

    /**
     * Limita a los N ultimos mensajes (por id descendente). Pensado
     * para el listado inicial del chat.
     *
     * @param  Builder<ProjectMessage>  $query
     * @param  int  $limit
     * @return Builder<ProjectMessage>
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderByDesc('id')->limit($limit);
    }
}
