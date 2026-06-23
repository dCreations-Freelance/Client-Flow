<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Storage;

/**
 * Gestion de adjuntos de tareas en el portal cliente.
 *
 * El cliente solo puede descargar adjuntos. La subida y el
 * borrado son exclusivos del admin. Mantener un controlador
 * portal separado del admin permite que el routing aplique
 * el middleware `client` y que las policies sean las del
 * portal.
 */
class TaskAttachmentController extends Controller
{
    /**
     * Sirve el archivo al cliente tras verificar la policy.
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
     * Verifica que la tarea pertenece al proyecto de la URL.
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
