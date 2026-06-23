<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Services\Attachments\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Gestion de adjuntos de mensajes del chat (panel admin).
 *
 * El grueso del flujo de subida vive en el componente Livewire
 * `Shared\ChatWindow`, que sube el archivo y crea el mensaje
 * en una sola transaccion. Este controlador expone
 * `download` y `destroy`, que son las acciones HTTP que la
 * vista del chat invoca al pulsar "Descargar" o "Eliminar".
 *
 * El borrado dispara ademas una limpieza de filas en la BD
 * del mensaje si este queda sin texto ni adjuntos.
 */
class MessageAttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $attachments,
    ) {
    }

    /**
     * Sirve el archivo al admin tras verificar la policy.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Project $project, ProjectMessage $message, MessageAttachment $attachment)
    {
        $this->ensureMessageBelongsToProject($project, $message);
        $this->ensureAttachmentBelongsToMessage($message, $attachment);
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
     * Elimina el archivo del disco y la fila. Si tras la
     * eliminacion el mensaje queda vacio (sin texto y sin
     * adjuntos), lo eliminamos tambien para no dejar burbujas
     * vacias en el chat.
     *
     * @return RedirectResponse
     */
    public function destroy(Project $project, ProjectMessage $message, MessageAttachment $attachment): RedirectResponse
    {
        $this->ensureMessageBelongsToProject($project, $message);
        $this->ensureAttachmentBelongsToMessage($message, $attachment);
        $this->authorize('delete', $attachment);

        $this->attachments->deleteMessageAttachment($attachment);

        // Si el mensaje no tenia texto y era su ultimo adjunto,
        // lo eliminamos para no dejar una burbuja fantasma.
        if ($message->isEmpty() && $message->attachments()->doesntExist()) {
            $message->delete();
        }

        return back()->with('status', 'Adjunto eliminado.');
    }

    /**
     * Verifica que el mensaje de la URL pertenece al proyecto
     * de la URL. Si no, 404.
     *
     * @return void
     */
    private function ensureMessageBelongsToProject(Project $project, ProjectMessage $message): void
    {
        if ((int) $message->project_id !== (int) $project->id) {
            abort(404);
        }
    }

    /**
     * Verifica que el adjunto pertenece al mensaje de la URL.
     *
     * @return void
     */
    private function ensureAttachmentBelongsToMessage(ProjectMessage $message, MessageAttachment $attachment): void
    {
        if ((int) $attachment->message_id !== (int) $message->id) {
            abort(404);
        }
    }
}
