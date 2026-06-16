<?php

namespace App\Livewire\Shared;

use App\Enums\MessageType;
use App\Models\Project;
use App\Models\ProjectChatRead;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Notifications\NewProjectMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

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
 * - Al enviar, el mensaje aparece inmediatamente y la vista se
 *   autoscrolla al fondo.
 * - El sistema de notificaciones (in-app + email) se dispara al
 *   persistir el mensaje: destinatarios = miembros del proyecto
 *   + miembros de la org del proyecto, excluyendo al emisor.
 */
class ChatWindow extends Component
{
    use AuthorizesRequests;

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
     */
    public function sendMessage(): void
    {
        $content = trim($this->newMessage);

        if ($content === '') {
            $this->addError('newMessage', 'Escribe un mensaje antes de enviarlo.');

            return;
        }

        if (mb_strlen($content) > 2000) {
            $this->addError('newMessage', 'El mensaje es demasiado largo.');

            return;
        }

        // Forzamos la policy de ProjectMessage porque
        // authorize('create', $this->project) infiere
        // ProjectPolicy::create (que es admin-only). Aqui
        // queremos permitir clientes que pueden ver el proyecto.
        $this->authorize('create', [ProjectMessage::class, $this->project]);

        $message = ProjectMessage::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'content' => $content,
            'type' => MessageType::Text,
        ]);

        // Marcamos como leido para el emisor: ya "ve" su propio
        // mensaje. Si no lo hicieramos, su propio mensaje le
        // contaria como no leido.
        ProjectChatRead::markAsRead($this->project, $this->user, $message->id);

        // Notificamos a los destinatarios (todos los miembros del
        // proyecto + miembros de la org, excluyendo al emisor).
        $recipients = $this->resolveRecipients($this->user->id);
        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new NewProjectMessage($message, $this->project, $this->user),
            );
        }

        $this->reset('newMessage');
        $this->resetErrorBag();
        $this->dispatch('chat-message-sent', messageId: $message->id);
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
     */
    private function markAsRead(): void
    {
        $lastId = (int) ProjectMessage::where('project_id', $this->project->id)->max('id');
        if ($lastId <= 0) {
            return;
        }

        ProjectChatRead::markAsRead($this->project, $this->user, $lastId);
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
     * necesita para pintar mensajes y mensajes cargados.
     */
    public function render(): View
    {
        return view('livewire.shared.chat-window', [
            'messages' => $this->messages,
            'totalMessages' => $this->totalMessages,
            'unreadCount' => $this->unreadCount,
        ]);
    }
}
