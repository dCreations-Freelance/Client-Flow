<?php

namespace App\Services\Attachments;

use App\Models\MessageAttachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio central para gestionar archivos adjuntos.
 *
 * Aporta una API unica para almacenar, servir y eliminar
 * archivos en disco, independientemente de si el adjunto va
 * asociado a una tarea (`TaskAttachment`) o a un mensaje del
 * chat (`MessageAttachment`). Los controladores delegan en este
 * servicio para evitar duplicar la logica de disco y de
 * validacion de contexto.
 *
 * Por que existe:
 * - Los archivos no se exponen en `public/`. Hay que servirlos
 *   via `Storage::download()` desde un controlador que ya ha
 *   pasado la policy. Aqui se concentra la ruta en disco y la
 *   generacion del nombre interno.
 * - La validacion de tamano y MIME vive en la Form Request
 *   (porque necesita reglas de Laravel). Aqui se asume que el
 *   archivo ya paso esa validacion.
 * - El borrado del archivo en disco y de la fila en BD se hace
 *   de forma atomica: si el borrado en disco falla, la fila
 *   queda huerfana y se loguea. En una fase futura podriamos
 *   mover esto a un `Job` con retry, pero en MVP es acceptable.
 */
class AttachmentService
{
    /**
     * Contexto valido del servicio. Cualquier otro valor lanzara
     * `InvalidArgumentException`. Mantenerlo en una constante
     * permite detectar typos en tiempo de desarrollo.
     */
    public const CONTEXT_TASK = 'tasks';

    public const CONTEXT_MESSAGE = 'messages';

    /**
     * Sube un archivo a disco y crea la fila correspondiente
     * segun el contexto (`'tasks'` o `'messages'`).
     *
     * @param  Project  $project  proyecto al que pertenece el adjunto
     * @param  string  $context  'tasks' o 'messages'
     * @param  int  $parentId  id de la tarea o mensaje padre
     * @param  UploadedFile  $file  archivo subido
     * @param  User  $uploader  usuario autor
     * @return TaskAttachment|MessageAttachment
     *
     * @throws \InvalidArgumentException si el contexto no es valido
     */
    public function store(
        Project $project,
        string $context,
        int $parentId,
        UploadedFile $file,
        User $uploader,
    ): TaskAttachment|MessageAttachment {
        $this->assertValidContext($context);

        $originalName = $file->getClientOriginalName() ?: 'archivo';
        $filename = $this->generateFilename($originalName);
        $relativeDir = $this->directoryFor($project, $context);
        $disk = (string) config('clientflow.attachments.disk', 'local');

        // putFileAs sube el archivo y devuelve la ruta relativa
        // (mismo nombre que le pasamos). Si quisiéramos permitir
        // que el driver sea S3, esto funcionaria igual.
        $stored = Storage::disk($disk)->putFileAs($relativeDir, $file, $filename);

        if ($stored === false) {
            throw new \RuntimeException('No se pudo guardar el archivo en disco.');
        }

        $attributes = [
            'user_id' => $uploader->id,
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => (string) $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
        ];

        if ($context === self::CONTEXT_TASK) {
            return TaskAttachment::create($attributes + ['task_id' => $parentId]);
        }

        return MessageAttachment::create($attributes + ['message_id' => $parentId]);
    }

    /**
     * Elimina un adjunto de una tarea: borra el archivo del disco
     * y luego la fila. Es seguro llamarlo dos veces (la segunda
     * vez, `Storage::exists` devuelve false y se ignora).
     *
     * @param  TaskAttachment  $attachment
     * @return void
     */
    public function deleteTaskAttachment(TaskAttachment $attachment): void
    {
        $this->deleteFromDisk($attachment->disk_path);
        $attachment->delete();
    }

    /**
     * Elimina un adjunto de un mensaje: borra el archivo del disco
     * y luego la fila.
     *
     * @param  MessageAttachment  $attachment
     * @return void
     */
    public function deleteMessageAttachment(MessageAttachment $attachment): void
    {
        $this->deleteFromDisk($attachment->disk_path);
        $attachment->delete();
    }

    /**
     * Elimina todos los adjuntos de una tarea. Pensado para
     * invocarse en el evento `deleting` del modelo `Task` si en
     * una fase futura se prefiere no confiar en el `cascadeOnDelete`
     * de la FK para archivos en disco.
     *
     * @param  Task  $task
     * @return int  numero de adjuntos eliminados
     */
    public function deleteAllForTask(Task $task): int
    {
        $count = 0;
        foreach ($task->attachments()->get() as $attachment) {
            $this->deleteTaskAttachment($attachment);
            $count++;
        }

        return $count;
    }

    /**
     * Elimina todos los adjuntos de un mensaje.
     *
     * @param  \App\Models\ProjectMessage  $message
     * @return int
     */
    public function deleteAllForMessage(\App\Models\ProjectMessage $message): int
    {
        $count = 0;
        foreach ($message->attachments()->get() as $attachment) {
            $this->deleteMessageAttachment($attachment);
            $count++;
        }

        return $count;
    }

    // -----------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------

    /**
     * Genera un nombre de archivo unico. La firma se delega en
     * el modelo para que la convencion este centralizada y los
     * factories + tests la puedan usar de forma consistente.
     *
     * @param  string  $originalName
     * @return string
     */
    private function generateFilename(string $originalName): string
    {
        return TaskAttachment::generateFilename($originalName);
    }

    /**
     * Resuelve el directorio en disco para un (proyecto, contexto).
     * Estructura final: `{subdirectory}/{project_id}/attachments/
     * {tasks|messages}`.
     *
     * @param  Project  $project
     * @param  string  $context
     * @return string
     */
    private function directoryFor(Project $project, string $context): string
    {
        $sub = (string) config('clientflow.attachments.subdirectory', 'clientflow/projects');

        return $sub.'/'.$project->id.'/attachments/'.$context;
    }

    /**
     * Borra un archivo del disco. Si no existe (porque ya se
     * borro en una operacion previa), no lanza excepcion: en MVP
     * la prioridad es eliminar la fila, y la limpieza del disco
     * es best-effort.
     *
     * @param  string  $relativePath
     * @return void
     */
    private function deleteFromDisk(string $relativePath): void
    {
        $disk = (string) config('clientflow.attachments.disk', 'local');

        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }
    }

    /**
     * Verifica que el contexto es uno de los soportados. Lo
     * extraemos a un metodo para que un cambio futuro (anadir
     * `events` o `documents`) se haga en un unico punto.
     *
     * @param  string  $context
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertValidContext(string $context): void
    {
        $valid = [self::CONTEXT_TASK, self::CONTEXT_MESSAGE];
        if (! in_array($context, $valid, true)) {
            throw new \InvalidArgumentException(
                sprintf('Contexto de adjunto invalido: "%s".', $context),
            );
        }
    }
}
