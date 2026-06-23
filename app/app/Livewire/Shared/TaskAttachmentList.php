<?php

namespace App\Livewire\Shared;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\Activity\ProjectActivityLogger;
use App\Services\Attachments\AttachmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente Livewire que renderiza la lista de adjuntos de
 * una tarea con su formulario de subida.
 *
 * Se usa tanto en la vista de detalle admin como en la del
 * portal cliente. La diferencia entre ambos contextos la
 * gestiona la policy: el admin puede subir y borrar; el
 * cliente solo puede descargar.
 *
 * El componente no emite system messages al subir: en la
 * vista de detalle la subida es atomica con la accion del
 * usuario y no aporta ruido al chat. El system message
 * "se subio un adjunto" ya se emite en el modal de creacion
 * de tarea del kanban, que es el momento de descubrimiento
 * para los miembros del proyecto.
 */
class TaskAttachmentList extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Project $project;

    public Task $task;

    /**
     * Adjuntos pendientes de subir. Tras pulsar "Subir
     * archivos" se procesan uno a uno via `AttachmentService`
     * y se limpia el array.
     *
     * @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[]
     */
    public array $pendingAttachments = [];

    /**
     * Inicializa el componente con el proyecto y la tarea.
     * Se valida que la tarea pertenece al proyecto (defensa
     * en profundidad).
     */
    public function mount(Project $project, Task $task): void
    {
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }

        $this->project = $project;
        $this->task = $task;
    }

    /**
     * Sube los archivos pendientes y los asocia a la tarea.
     * Solo admin: la policy `create` lo bloquea para el
     * cliente. Si no hay archivos, no hace nada.
     *
     * @return void
     */
    public function upload(): void
    {
        $this->authorize('create', [TaskAttachment::class, $this->project]);

        if ($this->pendingAttachments === []) {
            return;
        }

        $this->validate([
            'pendingAttachments' => ['nullable', 'array', 'max:'.(int) config('clientflow.attachments.max_files_per_upload', 5)],
            'pendingAttachments.*' => ['file', 'max:'.(int) config('clientflow.attachments.max_size_kb', 10240), 'mimes:'.implode(',', (array) config('clientflow.attachments.allowed_mimes', []))],
        ]);

        $service = app(AttachmentService::class);
        $count = 0;
        foreach ($this->pendingAttachments as $file) {
            $service->store(
                $this->project,
                AttachmentService::CONTEXT_TASK,
                $this->task->id,
                $file,
                Auth::user(),
            );
            $count++;
        }

        if ($count > 0) {
            app(ProjectActivityLogger::class)->attachmentUploadedToTask(
                $this->project,
                $this->task,
                $count,
                Auth::user(),
            );
        }

        $this->reset('pendingAttachments');
        $this->resetErrorBag();
        session()->flash('status', 'Adjuntos subidos correctamente.');
    }

    /**
     * Elimina un adjunto de la tarea. Solo admin.
     *
     * @param  int  $attachmentId
     * @return void
     */
    public function delete(int $attachmentId): void
    {
        $attachment = TaskAttachment::find($attachmentId);
        if ($attachment === null) {
            return;
        }

        if ((int) $attachment->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('delete', $attachment);

        app(AttachmentService::class)->deleteTaskAttachment($attachment);
        session()->flash('status', 'Adjunto eliminado.');
    }

    /**
     * Quita un adjunto pendiente del array (boton X).
     *
     * @param  int  $index
     * @return void
     */
    public function removePending(int $index): void
    {
        if (array_key_exists($index, $this->pendingAttachments)) {
            unset($this->pendingAttachments[$index]);
            $this->pendingAttachments = array_values($this->pendingAttachments);
        }
    }

    /**
     * Adjuntos persistidos, cargados con el autor para evitar
     * N+1 en la vista.
     */
    public function getAttachmentsProperty()
    {
        return $this->task->attachments()->with('user')->get();
    }

    /**
     * Render del componente: lista + formulario (si admin) o
     * solo lista (si cliente).
     */
    public function render(): View
    {
        return view('livewire.shared.task-attachment-list', [
            'attachments' => $this->attachments,
            'canUpload' => Auth::user()?->isAdmin() ?? false,
            'canDelete' => Auth::user()?->isAdmin() ?? false,
        ]);
    }
}
