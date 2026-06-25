<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadTaskAttachmentRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\Activity\ActivityLogger;
use App\Services\Attachments\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Gestion de adjuntos de tareas en el panel admin.
 *
 * Acciones:
 * - `store`: sube un archivo nuevo a una tarea. Solo admin.
 *   Dispara un mensaje de sistema en el chat.
 * - `download`: sirve el archivo via `Storage::download()` tras
 *   verificar la policy.
 * - `destroy`: elimina el archivo del disco y la fila de BD.
 *   Solo admin, sin importar quien subio el adjunto.
 *
 * Las tareas estan aisladas por proyecto: comprobamos que el
 * `task` pertenece al `project` de la URL antes de operar, para
 * evitar que un admin manipule IDs cruzados.
 */
class TaskAttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $attachments,
        private ActivityLogger $activity,
    ) {
    }

    /**
     * Sube un archivo y lo asocia a la tarea. El archivo se
     * guarda en disco via `AttachmentService` y la policy ya
     * garantiza que el admin esta autorizado.
     *
     * @return RedirectResponse
     */
    public function store(UploadTaskAttachmentRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->ensureTaskBelongsToProject($project, $task);
        $this->authorize('create', [TaskAttachment::class, $project]);

        $file = $request->file('attachment');
        if ($file === null) {
            return back()->withErrors(['attachment' => 'No se recibio el archivo.']);
        }

        $this->attachments->store(
            $project,
            AttachmentService::CONTEXT_TASK,
            $task->id,
            $file,
            $request->user(),
        );

        // Mensaje de sistema en el chat. La cantidad siempre es
        // 1 en esta ruta porque la subida del modal es de un
        // solo archivo. Si en una fase futura se permite
        // multiple, se pasa el count real.
        $this->activity->attachmentUploadedToTask($project, $task, 1, $request->user());

        return back()->with('status', 'Adjunto subido correctamente.');
    }

    /**
     * Sirve el archivo al admin tras verificar la policy. Usa
     * `Storage::download()` con el nombre original del usuario
     * para que la descarga se vea natural.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Project $project, Task $task, TaskAttachment $attachment)
    {
        $this->ensureTaskBelongsToProject($project, $task);
        $this->ensureAttachmentBelongsToTask($task, $attachment);
        $this->authorize('download', $attachment);

        $disk = (string) config('clientflow.attachments.disk', 'local');

        if (! Storage::disk($disk)->exists($attachment->disk_path)) {
            return back()->withErrors(['attachment' => 'El archivo ya no esta disponible.']);
        }

        return Storage::disk($disk)->download(
            $attachment->disk_path,
            $attachment->download_name,
        );
    }

    /**
     * Elimina el archivo del disco y la fila. Solo admin.
     *
     * @return RedirectResponse
     */
    public function destroy(Project $project, Task $task, TaskAttachment $attachment): RedirectResponse
    {
        $this->ensureTaskBelongsToProject($project, $task);
        $this->ensureAttachmentBelongsToTask($task, $attachment);
        $this->authorize('delete', $attachment);

        $this->attachments->deleteTaskAttachment($attachment);

        return back()->with('status', 'Adjunto eliminado.');
    }

    /**
     * Verifica que la tarea de la URL pertenece al proyecto de
     * la URL. Si no, aborta con 404. Esto cierra el vector
     * "admin manipula la URL con un id de tarea ajeno al
     * proyecto".
     *
     * @return void
     */
    private function ensureTaskBelongsToProject(Project $project, Task $task): void
    {
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }
    }

    /**
     * Verifica que el adjunto pertenece a la tarea de la URL.
     * Si no, 404. Igual que arriba, evita el vector de
     * manipulacion de IDs.
     *
     * @return void
     */
    private function ensureAttachmentBelongsToTask(Task $task, TaskAttachment $attachment): void
    {
        if ((int) $attachment->task_id !== (int) $task->id) {
            abort(404);
        }
    }
}
