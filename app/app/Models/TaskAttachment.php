<?php

namespace App\Models;

use Database\Factories\TaskAttachmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Adjunto subido a una tarea del kanban.
 *
 * Cada fila representa un archivo fisico almacenado bajo
 * `storage/app/clientflow/projects/{project_id}/attachments/tasks/`.
 * El nombre interno (`filename`) se genera en el momento de la
 * subida para evitar colisiones y para que la URL de descarga
 * no exponga el nombre original del usuario (que puede contener
 * espacios, e�es o caracteres que rompen algunos sistemas de
 * archivos).
 *
 * El archivo se sirve exclusivamente desde el controlador
 * `TaskAttachmentController::download`, que aplica la policy
 * correspondiente. Nunca se expone como `public/`.
 */
class TaskAttachment extends Model
{
    /** @use HasFactory<TaskAttachmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'task_id',
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
     * Genera un nombre interno unico para un archivo en disco.
     *
     * Lo usamos desde el `AttachmentService` al guardar. El patron
     * es: timestamp en microsegundos + 6 chars de `Str::random`
     * + extension del archivo original. Asi dos subidas del mismo
     * archivo en el mismo segundo no colisionan, y la URL
     * resultante es estable para el cache HTTP.
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
     * Tarea a la que pertenece el adjunto.
     *
     * @return BelongsTo<Task, TaskAttachment>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Usuario que subio el archivo. La autoria es historica y no
     * se reasigna automaticamente.
     *
     * @return BelongsTo<User, TaskAttachment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -----------------------------------------------------------------
    // Accesors
    // -----------------------------------------------------------------

    /**
     * Tamano formateado para mostrar en UI (KB / MB / GB).
     *
     * @return string
     */
    public function getHumanSizeAttribute(): string
    {
        return self::formatBytes($this->size);
    }

    /**
     * Nombre sugerido para `Content-Disposition: attachment`. Usa
     * el nombre original del usuario (no el interno) para que la
     * descarga se vea natural.
     *
     * @return string
     */
    public function getDownloadNameAttribute(): string
    {
        return $this->original_name ?: $this->filename;
    }

    /**
     * Ruta completa en disco del archivo fisico. La convencion
     * coincide con `config/clientflow.php` y `AttachmentService`.
     *
     * @return string
     */
    public function getDiskPathAttribute(): string
    {
        $sub = (string) config('clientflow.attachments.subdirectory', 'clientflow/projects');
        $projectId = $this->task?->project_id ?? 0;

        return $sub.'/'.$projectId.'/attachments/tasks/'.$this->filename;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determina si el adjunto pertenece al proyecto indicado.
     * Util para verificar `cross-project` en los controladores
     * antes de servir o eliminar.
     *
     * @param  int  $projectId
     * @return bool
     */
    public function belongsToProject(int $projectId): bool
    {
        return $this->task !== null && (int) $this->task->project_id === $projectId;
    }

    /**
     * Formatea un tamano en bytes a una unidad legible. Pensado
     * para los accesores y la vista.
     *
     * @param  int  $bytes
     * @return string
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024) {
                return number_format($value, $value < 10 ? 1 : 0).' '.$unit;
            }
            $value /= 1024;
        }

        return number_format($value, 1).' '.$unit;
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Adjuntos de una tarea concreta. Pensado para reducir el
     * codigo repetido en los controladores.
     *
     * @param  Builder<TaskAttachment>  $query
     * @param  int  $taskId
     * @return Builder<TaskAttachment>
     */
    public function scopeForTask(Builder $query, int $taskId): Builder
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Adjuntos de cualquier tarea de un proyecto. Hace join con
     * `tasks` para filtrar, lo que permite limpiar todos los
     * adjuntos de un proyecto cuando se archiva.
     *
     * @param  Builder<TaskAttachment>  $query
     * @param  int  $projectId
     * @return Builder<TaskAttachment>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->whereHas('task', fn (Builder $q) => $q->where('project_id', $projectId));
    }

    /**
     * Orden por mas reciente primero. Es el orden natural en
     * la vista de detalle de tarea.
     *
     * @param  Builder<TaskAttachment>  $query
     * @return Builder<TaskAttachment>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
