<?php

namespace App\Models;

use Database\Factories\MessageAttachmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Adjunto subido a un mensaje del chat de un proyecto.
 *
 * Estructura paralela a `TaskAttachment` pero apuntando a
 * `ProjectMessage` en lugar de `Task`. El archivo vive en
 * `storage/app/clientflow/projects/{project_id}/attachments/messages/`
 * y se sirve unicamente desde el controlador
 * `MessageAttachmentController::download`.
 *
 * Reglas importantes:
 * - Borrar el mensaje padre borra sus adjuntos (cascade en la FK).
 * - El usuario autor se preserva (`restrictOnDelete` en la FK)
 *   para mantener la autoria historica.
 */
class MessageAttachment extends Model
{
    /** @use HasFactory<MessageAttachmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Genera un nombre interno unico para el archivo en disco.
     * Mismo patron que `TaskAttachment::generateFilename` para
     * que la inspeccion del directorio sea coherente.
     *
     * @param  string  $originalName
     * @return string
     */
    public static function generateFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? '.'.Str::lower($extension) : '';

        return now()->format('Ymd_His').'_'.Str::random(8).$extension;
    }

    /**
     * Mensaje al que pertenece el adjunto.
     *
     * @return BelongsTo<ProjectMessage, MessageAttachment>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ProjectMessage::class, 'message_id');
    }

    /**
     * Usuario que subio el adjunto.
     *
     * @return BelongsTo<User, MessageAttachment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -----------------------------------------------------------------
    // Accesors
    // -----------------------------------------------------------------

    /**
     * Tamano formateado para la UI. Reutiliza el helper estatico
     * de `TaskAttachment` para mantener una sola implementacion.
     *
     * @return string
     */
    public function getHumanSizeAttribute(): string
    {
        return TaskAttachment::formatBytes($this->size);
    }

    /**
     * Nombre legible para `Content-Disposition`.
     *
     * @return string
     */
    public function getDownloadNameAttribute(): string
    {
        return $this->original_name ?: $this->filename;
    }

    /**
     * Ruta completa en disco del archivo.
     *
     * @return string
     */
    public function getDiskPathAttribute(): string
    {
        $sub = (string) config('clientflow.attachments.subdirectory', 'clientflow/projects');
        $projectId = $this->message?->project_id ?? 0;

        return $sub.'/'.$projectId.'/attachments/messages/'.$this->filename;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determina si el adjunto pertenece al proyecto indicado. Se
     * usa en el controlador de descarga para evitar servir
     * adjuntos de proyectos ajenos aunque se conozca el ID.
     *
     * @param  int  $projectId
     * @return bool
     */
    public function belongsToProject(int $projectId): bool
    {
        return $this->message !== null
            && (int) $this->message->project_id === $projectId;
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Adjuntos de un mensaje concreto.
     *
     * @param  Builder<MessageAttachment>  $query
     * @param  int  $messageId
     * @return Builder<MessageAttachment>
     */
    public function scopeForMessage(Builder $query, int $messageId): Builder
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * Adjuntos de cualquier mensaje de un proyecto. Util para
     * limpieza en bloque si en algun momento se archiva un
     * proyecto.
     *
     * @param  Builder<MessageAttachment>  $query
     * @param  int  $projectId
     * @return Builder<MessageAttachment>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->whereHas('message', fn (Builder $q) => $q->where('project_id', $projectId));
    }

    /**
     * Orden cronologico inverso (mas reciente primero).
     *
     * @param  Builder<MessageAttachment>  $query
     * @return Builder<MessageAttachment>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
