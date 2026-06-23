<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UploadMessageAttachmentRequest;
use App\Models\MessageAttachment;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Services\Attachments\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Gestion de adjuntos de mensajes del chat (portal cliente).
 *
 * El cliente puede:
 * - Subir adjuntos a sus mensajes (subida integrada en el
 *   componente Livewire del chat; este controlador expone
 *   ademas un endpoint HTTP de respaldo).
 * - Descargar adjuntos de mensajes de sus proyectos.
 *
 * El cliente NO puede:
 * - Eliminar adjuntos. La regla se delega a la policy
 *   (`MessageAttachmentPolicy::delete`) que requiere admin.
 */
class MessageAttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $attachments,
    ) {
    }

    /**
     * Endpoint HTTP de respaldo para que el cliente suba un
     * adjunto a un mensaje ya existente. La mayoria de subidas
     * se hacen via el componente Livewire del chat, pero
     * mantener este endpoint facilita los tests y la
     * interoperabilidad con clientes externos.
     *
     * @return RedirectResponse
     */
    public function store(UploadMessageAttachmentRequest $request, Project $project, ProjectMessage $message): RedirectResponse
    {
        $this->ensureMessageBelongsToProject($project, $message);
        $this->authorize('create', [MessageAttachment::class, $project]);

        $file = $request->file('attachment');
        if ($file === null) {
            return back()->withErrors(['attachment' => 'No se recibio el archivo.']);
        }

        $this->attachments->store(
            $project,
            AttachmentService::CONTEXT_MESSAGE,
            $message->id,
            $file,
            $request->user(),
        );

        return back()->with('status', 'Adjunto enviado.');
    }

    /**
     * Sirve el archivo al cliente tras verificar la policy.
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
     * Verifica que el mensaje pertenece al proyecto de la URL.
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
