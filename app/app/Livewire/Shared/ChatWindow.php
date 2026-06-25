<?php

namespace App\Livewire\Shared;

use App\Enums\MessageType;
use App\Enums\NotificationEvent;
use App\Models\MessageRead;
use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Notifications\NewProjectMessage;
use App\Services\Activity\ActivityLogger;
use App\Services\Attachments\AttachmentService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente Livewire compartido del chat de un proyecto.
 *
 * Es el mismo componente para admin y portal: la diferencia
 * (autorizacion, layout) la aplican los controladores que lo
 * montan. Esto evita duplicar la logica de polling, envio de
 * mensajes y tracking de lectura.
 *
 * Comportamiento:
 * - Polling cada 5s (configurable en la vista con `wire:poll.5s`).
 * - Auto-marca como leidos los mensajes hasta el id mas alto
 *   conocido al recibir el primer poll o al mount.
 * - Registra lectura individual de cada mensaje en `message_reads`
 *   para poder mostrar el doble check de "visto".
 * - Al enviar, el mensaje aparece inmediatamente y la vista se
 *   autoscrolla al fondo.
 * - El sistema de notificaciones (in-app + email) se dispara al
 *   persistir el mensaje: destinatarios = miembros del proyecto
 *   + miembros de la org del proyecto, excluyendo al emisor.
 */
class ChatWindow extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Project $project;

    /**
     * Usuario autenticado. Se fija en mount y se reusa en cada
     * render para evitar que un cambio de sesion en mitad de un
     * poll envíe mensajes a nombre de otro usuario.
     */
    public User $user;

    /**
     * Texto del input. Sincronizado con el textarea via `wire:model`.
     */
    public string $newMessage = '';

    /**
     * Adjuntos pendientes de enviar. Livewire los sube a
     * `livewire-tmp/` cuando se asigna el input; aqui los
     * procesamos en `sendMessage`. Es un array porque
     * permitimos hasta `max_files_per_upload` archivos en una
     * sola operacion (consistente con el config).
     *
     * @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[]
     */
    public array $attachments = [];

    /**
     * Numero de mensajes cargados en pantalla. Se incrementa con
     * "Cargar mensajes anteriores" para paginar el historial.
     */
    public int $loadedCount = 50;

    /**
     * Flag de inicializacion: evita que `markAsRead` se dispare
     * antes de tener al menos un mensaje cargado, y que el
     * `refresh` re-marque lecturas de manera innecesaria.
     */
    public bool $initialized = false;

    /**
     * Inicializa el componente con el proyecto. La autorizacion
     * se hace contra el proyecto, no contra cada mensaje, para
     * mantener el rendimiento: cargar la policy de cada mensaje
     * seria O(n) en cada poll.
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->user = Auth::user();
        $this->authorize('view', $project);
    }

    /**
     * Mensajes actualmente cargados. Se cargan los ultimos
     * `$loadedCount` ordenados cronologicamente para que la vista
     * pinte el chat de arriba (antiguo) abajo (nuevo).
     *
     * Se declara como `getMessagesProperty` para que Livewire lo
     * cachee y solo lo recalcule cuando cambia el estado del
     * componente.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectMessage>
     */
    public function getMessagesProperty()
    {
        $ids = ProjectMessage::query()
            ->where('project_id', $this->project->id)
            ->orderByDesc('id')
            ->limit($this->loadedCount)
            ->pluck('id');

        return ProjectMessage::with('user')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    /**
     * Numero total de mensajes del proyecto. Se usa para mostrar
     * el boton "Cargar mensajes anteriores" solo cuando hay mas.
     */
    public function getTotalMessagesProperty(): int
    {
        return ProjectMessage::where('project_id', $this->project->id)->count();
    }

    /**
     * Numero de mensajes no leidos del proyecto para el usuario
     * actual. Despues de marcar como leido en mount/refresh este
     * valor se queda en 0 hasta que llegue un mensaje nuevo.
     */
    public function getUnreadCountProperty(): int
    {
        $read = ProjectChatRead::query()
            ->where('project_id', $this->project->id)
            ->where('user_id', $this->user->id)
            ->first();

        if ($read === null) {
            return ProjectMessage::where('project_id', $this->project->id)->count();
        }

        return ProjectMessage::where('project_id', $this->project->id)
            ->where('id', '>', (int) $read->last_read_message_id)
            ->count();
    }

    /**
     * Ids de los mensajes cargados que han sido leidos por alguien
     * distinto al usuario actual.
     *
     * Se calcula con una unica query para evitar N+1 en la vista:
     * en lugar de preguntar por cada burbuja, pasamos un mapa de
     * ids y la vista consulta ese mapa en tiempo constante.
     *
     * @return array<int, bool>
     */
    public function getReadMessageIdsProperty(): array
    {
        $messageIds = $this->messages->pluck('id')->all();

        if ($messageIds === []) {
            return [];
        }

        $readIds = MessageRead::query()
            ->whereIn('message_id', $messageIds)
            ->where('user_id', '!=', $this->user->id)
            ->distinct()
            ->pluck('message_id')
            ->all();

        return array_fill_keys($readIds, true);
    }

    /**
     * Hook de Livewire: se ejecuta al final del primer render.
     * Lo usamos para marcar como leidos los mensajes existentes
     * en cuanto el usuario ve el chat. Si el chat esta vacio no
     * hace nada (no creamos marcadores vacios).
     */
    public function booted(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->markAsRead();
    }

    /**
     * Llamado desde el polling cada 5s. Simplemente re-renderiza
     * la vista; las propiedades computadas (mensajes, no leidos)
     * se recalculan automaticamente.
     */
    public function refresh(): void
    {
        $this->markAsRead();
    }

    /**
     * Envia un mensaje de texto en este proyecto. Se valida con
     * las mismas reglas que un Form Request tradicional para
     * mantener la consistencia y poder usar mensajes en
     * castellano.
     *
     * Si hay adjuntos pendientes, primero los sube via
     * `AttachmentService` y crea un mensaje con el texto (o
     * vacio si solo hay adjuntos). Si el texto es vacio y no
     * hay adjuntos, muestra un error y no hace nada.
     */
    public function sendMessage(): void
    {
        $content = trim($this->newMessage);
        $hasAttachments = $this->attachments !== [];

        if ($content === '' && ! $hasAttachments) {
            $this->addError('newMessage', 'Escribe un mensaje o adjunta un archivo antes de enviar.');

            return;
        }

        if (mb_strlen($content) > 2000) {
            $this->addError('newMessage', 'El mensaje es demasiado largo.');

            return;
        }

        $this->validate([
            'attachments' => ['nullable', 'array', 'max:'.(int) config('clientflow.attachments.max_files_per_upload', 5)],
            'attachments.*' => ['file', 'max:'.(int) config('clientflow.attachments.max_size_kb', 10240), 'mimes:'.implode(',', (array) config('clientflow.attachments.allowed_mimes', []))],
        ]);

        // Forzamos la policy de ProjectMessage porque
        // authorize('create', $this->project) infiere
        // ProjectPolicy::create (que es admin-only). Aqui
        // queremos permitir clientes que pueden ver el proyecto.
        $this->authorize('create', [ProjectMessage::class, $this->project]);

        $messageType = $hasAttachments && $content === ''
            ? MessageType::File
            : MessageType::Text;

        $message = ProjectMessage::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'content' => $content,
            'type' => $messageType,
        ]);

        // Registramos el mensaje humano en el feed para que el
        // admin y el cliente tengan un timeline unificado.
        app(ActivityLogger::class)->messageSent($this->project, $message);

        // Si hay adjuntos, los subimos uno a uno via el
        // servicio. Esto crea las filas en `message_attachments`
        // y mueve los archivos de `livewire-tmp/` al disco
        // definitivo. El orden de subida se mantiene tal cual
        // el usuario los encolo.
        if ($hasAttachments) {
            $service = app(AttachmentService::class);
            foreach ($this->attachments as $file) {
                $service->store(
                    $this->project,
                    AttachmentService::CONTEXT_MESSAGE,
                    $message->id,
                    $file,
                    $this->user,
                );
            }
        }

        // Marcamos como leido para el emisor: ya "ve" su propio
        // mensaje. Si no lo hicieramos, su propio mensaje le
        // contaria como no leido.
        ProjectChatRead::markAsRead($this->project, $this->user, $message->id);

        // Notificamos a los destinatarios (todos los miembros del
        // proyecto + miembros de la org, excluyendo al emisor).
        // Pasamos por el dispatcher para respetar las preferencias
        // por canal (in-app y email) de cada destinatario.
        $recipients = $this->resolveRecipients($this->user->id);
        if ($recipients->isNotEmpty()) {
            NotificationDispatcher::dispatchToMany(
                $recipients,
                new NewProjectMessage($message, $this->project, $this->user),
                NotificationEvent::NewMessage,
            );
        }

        $this->reset(['newMessage', 'attachments']);
        $this->resetErrorBag();
        $this->dispatch('chat-message-sent', messageId: $message->id);
    }

    /**
     * Quita un adjunto pendiente del array `attachments`. Pensado
     * para los botones "X" en la UI cuando el usuario se
     * arrepiente de un archivo antes de enviar.
     *
     * @param  int  $index
     * @return void
     */
    public function removePendingAttachment(int $index): void
    {
        if (array_key_exists($index, $this->attachments)) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    /**
     * Elimina un adjunto ya enviado. Solo admin. Se invoca desde
     * los botones de la UI en cada burbuja; en el chat del
     * cliente este boton no se renderiza (la policy lo
     * rechaza).
     *
     * @param  int  $attachmentId
     * @return void
     */
    public function deleteMessageAttachment(int $attachmentId): void
    {
        $attachment = \App\Models\MessageAttachment::find($attachmentId);
        if ($attachment === null) {
            return;
        }

        $this->authorize('delete', $attachment);

        $message = $attachment->message;
        $service = app(AttachmentService::class);
        $service->deleteMessageAttachment($attachment);

        // Si el mensaje queda sin texto y sin adjuntos,
        // tambien lo borramos para no dejar una burbuja
        // fantasma.
        if ($message !== null && $message->isEmpty() && $message->attachments()->doesntExist()) {
            $message->delete();
        }
    }

    /**
     * Incrementa el numero de mensajes cargados para paginar el
     * historial. Limita a saltos de 50 para no abusar.
     */
    public function loadMore(): void
    {
        $this->loadedCount += 50;
    }

    /**
     * Marca los mensajes hasta el mas reciente como leidos para
     * el usuario actual. Es idempotente: si no hay mensajes no
     * crea fila vacia.
     *
     * Actualiza dos mecanismos complementarios:
     * 1. `project_chat_reads`: el contador eficiente de no leidos.
     * 2. `message_reads`: el pivot individual que permite saber
     *    quien ha visto cada mensaje (doble check).
     */
    private function markAsRead(): void
    {
        $lastId = (int) ProjectMessage::where('project_id', $this->project->id)->max('id');
        if ($lastId <= 0) {
            return;
        }

        ProjectChatRead::markAsRead($this->project, $this->user, $lastId);
        MessageRead::markMessagesAsRead($this->project, $this->user, $lastId);
    }

    /**
     * Resuelve los destinatarios de un nuevo mensaje: miembros
     * directos del proyecto + miembros de la organizacion del
     * proyecto (clientes), excluyendo al emisor.
     *
     * @param  int  $senderId
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function resolveRecipients(int $senderId)
    {
        $projectMemberIds = $this->project->members()->pluck('users.id');
        $orgMemberIds = $this->project->organization?->members()->pluck('users.id') ?? collect();

        return User::query()
            ->whereIn('id', $projectMemberIds->merge($orgMemberIds)->unique())
            ->where('id', '!=', $senderId)
            ->get();
    }

    /**
     * Renderiza la vista del chat pasando los datos que la vista
     * necesita para pintar mensajes, contadores y estado de lectura.
     */
    public function render(): View
    {
        return view('livewire.shared.chat-window', [
            'messages' => $this->messages,
            'totalMessages' => $this->totalMessages,
            'unreadCount' => $this->unreadCount,
            'readMessageIds' => $this->readMessageIds,
            'canDeleteAttachments' => $this->user->isAdmin(),
        ]);
    }
}
