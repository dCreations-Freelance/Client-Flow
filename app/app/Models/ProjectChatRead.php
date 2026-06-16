<?php

namespace App\Models;

use Database\Factories\ProjectChatReadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marcador de lectura del chat de un proyecto.
 *
 * Un usuario tiene una fila por proyecto que contiene el id del
 * ultimo mensaje que ha leido. Esto permite calcular el numero de
 * mensajes no leidos con un simple `count where id >
 * last_read_message_id` y mantener el coste de actualizacion
 * constante (un upsert) independientemente de cuantos mensajes
 * haya en el chat.
 *
 * Tambien guarda `last_email_sent_at` para que el futuro debounce
 * de notificaciones email (seccion "Transversal: Notificaciones")
 * tenga donde apoyarse.
 */
class ProjectChatRead extends Model
{
    /** @use HasFactory<ProjectChatReadFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'last_read_message_id',
        'last_email_sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_email_sent_at' => 'datetime',
        ];
    }

    /**
     * Proyecto al que pertenece el marcador.
     *
     * @return BelongsTo<Project, ProjectChatRead>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Usuario al que pertenece el marcador.
     *
     * @return BelongsTo<User, ProjectChatRead>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ultimo mensaje marcado como leido.
     *
     * @return BelongsTo<ProjectMessage, ProjectChatRead>
     */
    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(ProjectMessage::class, 'last_read_message_id');
    }

    /**
     * Marca los mensajes del proyecto como leidos hasta el id
     * indicado. Si ya existia un marcador, lo actualiza solo si
     * el nuevo id es estrictamente mayor (asi una peticion vieja
     * no "rebobina" el marcador).
     *
     * El metodo es estatico para que el chat pueda llamarlo sin
     * tener que cargar la fila primero. Internamente hace un
     * upsert idempotente: si no existe la fila, la crea.
     *
     * Si `$lastMessageId` es 0 o negativo (no hay mensajes que
     * marcar) el metodo es no-op y devuelve `null`. Esto evita
     * crear filas con `last_read_message_id = 0` que ademas
     * violarian la FK a `project_messages.id`.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $user
     * @param  int  $lastMessageId
     * @return \App\Models\ProjectChatRead|null
     */
    public static function markAsRead(Project $project, User $user, int $lastMessageId): ?self
    {
        if ($lastMessageId <= 0) {
            return null;
        }

        $existing = static::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing === null) {
            return static::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'last_read_message_id' => $lastMessageId,
            ]);
        }

        // No rebobinamos: si el id nuevo es menor o igual, no
        // actualizamos. Asi una peticion vieja o un race condition
        // no resetea el progreso de lectura.
        $current = (int) ($existing->last_read_message_id ?? 0);
        if ($lastMessageId > $current) {
            $existing->last_read_message_id = $lastMessageId;
            $existing->save();
        }

        return $existing;
    }

    /**
     * Numero de mensajes no leidos del proyecto para el usuario
     * asociado a este marcador. Util para badges.
     */
    public function unreadCount(): int
    {
        $query = ProjectMessage::query()
            ->where('project_id', $this->project_id);

        if ($this->last_read_message_id !== null) {
            $query->where('id', '>', $this->last_read_message_id);
        }

        return $query->count();
    }
}
