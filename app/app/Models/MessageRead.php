<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registro de lectura de un mensaje por un usuario.
 *
 * Esta tabla es el pivot que permite implementar el doble check
 * de "visto". `project_chat_reads` sigue siendo la fuente de
 * verdad para contar no leidos de forma eficiente; esta tabla
 * responde a la pregunta "ha visto alguien mas este mensaje?".
 */
class MessageRead extends Model
{
    /** @use HasFactory<\Database\Factories\MessageReadFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Mensaje que ha sido leido.
     *
     * @return BelongsTo<ProjectMessage, MessageRead>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ProjectMessage::class);
    }

    /**
     * Usuario que leyo el mensaje.
     *
     * @return BelongsTo<User, MessageRead>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marca como leidos todos los mensajes de un proyecto hasta
     * un id determinado para un usuario concreto.
     *
     * Se usa desde el componente de chat al abrir la ventana y en
     * cada poll: el usuario actual "ve" todos los mensajes que ya
     * estaban cargados. Solo se insertan los mensajes que aun no
     * tengan un registro de lectura para ese usuario.
     *
     * @param  Project  $project
     * @param  User  $user
     * @param  int  $upToMessageId
     * @return void
     */
    public static function markMessagesAsRead(Project $project, User $user, int $upToMessageId): void
    {
        if ($upToMessageId <= 0) {
            return;
        }

        $now = Carbon::now();

        // Obtenemos los ids de los mensajes ya leidos por el
        // usuario para evitar duplicados sin depender de
        // excepciones de BD.
        $alreadyRead = static::query()
            ->where('user_id', $user->id)
            ->whereHas('message', function (Builder $query) use ($project): void {
                $query->where('project_id', $project->id);
            })
            ->pluck('message_id')
            ->all();

        $messagesToMark = ProjectMessage::query()
            ->where('project_id', $project->id)
            ->where('id', '<=', $upToMessageId)
            ->when($alreadyRead !== [], function (Builder $query) use ($alreadyRead): Builder {
                /** @var Builder<ProjectMessage> $query */
                return $query->whereNotIn('id', $alreadyRead);
            })
            ->pluck('id')
            ->all();

        if ($messagesToMark === []) {
            return;
        }

        $rows = [];
        foreach ($messagesToMark as $messageId) {
            $rows[] = [
                'message_id' => $messageId,
                'user_id' => $user->id,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insercion masiva directa: mas rapida que crear
        // instancias una a una y evita N queries en el polling.
        DB::table('message_reads')->insert($rows);
    }
}
