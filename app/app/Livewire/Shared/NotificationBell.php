<?php

namespace App\Livewire\Shared;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Campana de notificaciones in-app (header).
 *
 * Renderiza el icono de campana con un badge rojo mostrando el
 * numero de notificaciones no leidas del usuario. Al hacer click
 * se despliega un dropdown con las ultimas 20 notificaciones
 * recibidas (mezcla de las distintas clases: `NewProjectMessage`,
 * `TaskAssigned`, `CalendarEventInvitation`, etc.).
 *
 * Comportamiento:
 * - Polling cada 30s (mismo intervalo que la PWA) para mantener
 *   el contador fresco sin necesidad de WebSockets.
 * - Click en una notificacion la marca como leida y navega al
 *   `url` del payload. Si el `url` es null (caso raro), la
 *   marca como leida sin navegar.
 * - "Marcar todas como leidas" vacia la bandeja.
 * - Refresco manual cuando otro componente Livewire dispara el
 *   evento `notifications-updated` (por ejemplo, despues de
 *   enviar un mensaje: el emisor podria recibir notificaciones
 *   propias que no cuentan, pero el polling cubre el resto).
 *
 * Decisiones:
 * - Limitamos a 20 entradas para no cargar la vista. Si el
 *   usuario quiere ver mas, puede ir a una pantalla de "todas"
 *   que se implementara en una fase futura.
 * - El polling usa `wire:poll.30s` (declarado en la vista) en
 *   lugar de un setInterval en JS: el primero aprovecha el ciclo
 *   de Livewire y respeta el cache de propiedades.
 */
class NotificationBell extends Component
{
    /**
     * Limite de notificaciones mostradas en el dropdown. Es
     * una constante para que la vista y la query la compartan
     * sin tener que pasarla por parametro.
     */
    public const INBOX_LIMIT = 20;

    /**
     * Si el dropdown esta abierto. Lo controla la vista con
     * `wire:click="toggleOpen"`.
     */
    public bool $open = false;

    /**
     * Usuario autenticado. Se fija en mount y se reusa para
     * todas las queries del componente.
     */
    public User $user;

    /**
     * Inicializa el componente. Solo se ejecuta una vez por
     * montaje; el polling se gestiona via `wire:poll` en la vista.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->user = $user;
    }

    /**
     * Devuelve el numero de notificaciones no leidas. Se
     * declara como propiedad computada para que Livewire lo
     * cachee y solo lo recalcule cuando cambia el estado.
     *
     * @return int
     */
    public function getUnreadCountProperty(): int
    {
        return $this->user->unreadNotifications()->count();
    }

    /**
     * Devuelve la lista de notificaciones a mostrar en el
     * dropdown. Limitada a `INBOX_LIMIT` y ordenadas de la
     * mas reciente a la mas antigua.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DatabaseNotification>
     */
    public function getNotificationsProperty()
    {
        return $this->user->notifications()
            ->latest('created_at')
            ->limit(self::INBOX_LIMIT)
            ->get();
    }

    /**
     * Alterna el estado del dropdown. Pensado para el icono
     * de la campana: `wire:click="toggleOpen"`.
     */
    public function toggleOpen(): void
    {
        $this->open = ! $this->open;
    }

    /**
     * Cierra el dropdown. Util para el handler de "click fuera"
     * que la vista dispara con un evento `close-bell`.
     */
    public function close(): void
    {
        $this->open = false;
    }

    /**
     * Marca una notificacion concreta como leida y devuelve
     * la URL a la que debe navegar la vista. Si la URL esta
     * vacia, devuelve `null` y la vista se queda en la pagina
     * actual (util cuando se quiere solo limpiar la campana).
     */
    public function openNotification(string $id): ?string
    {
        $notification = $this->user->notifications()->where('id', $id)->first();
        if ($notification === null) {
            return null;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = $notification->data['url'] ?? null;

        $this->open = false;

        if ($url !== null && str_contains($url, '/admin/') && ! $this->user->isAdmin()) {
            // Defensa: si por un error de payload la URL
            // apunta a /admin/ y el usuario es cliente, la
            // reescribimos a /portal/ en tiempo de click.
            $url = str_replace('/admin/', '/portal/', $url);
        }

        return $url;
    }

    /**
     * Marca todas las notificaciones del usuario como leidas.
     * El boton "Marcar todas" del footer del dropdown llama a
     * este metodo.
     */
    public function markAllAsRead(): void
    {
        $this->user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Refresca el componente cuando se dispara el evento
     * `notifications-updated` desde otro componente Livewire
     * (por ejemplo, al enviar un mensaje). Asi el emisor ve
     * la campana actualizada sin esperar al proximo poll.
     */
    #[On('notifications-updated')]
    public function refreshInbox(): void
    {
        // El unset + re-acceso fuerza a Livewire a recalcular
        // las propiedades computadas. Sin esto el cache de
        // Livewire devolveria la lista anterior.
        unset($this->notifications);
        unset($this->unreadCount);
    }

    /**
     * Serializa una notificacion en un array apto para la
     * vista. Mantener el render aqui (en lugar de inline en
     * Blade) facilita el testing y la lectura.
     *
     * @return array<string, mixed>
     */
    public function serialize(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? $this->defaultTitle($data),
            'body' => $data['body'] ?? $this->defaultBody($data),
            'url' => $data['url'] ?? null,
            'project_name' => $data['project_name'] ?? null,
            'is_unread' => $notification->read_at === null,
            'created_at' => $notification->created_at?->diffForHumans() ?? '',
        ];
    }

    /**
     * Titulo por defecto cuando la notificacion no incluye
     * `title` en su payload. Se deriva de los campos
     * disponibles.
     */
    private function defaultTitle(array $data): string
    {
        return match (true) {
            isset($data['task_title']) => 'Tarea asignada: '.$data['task_title'],
            isset($data['sender_name']) => 'Mensaje de '.$data['sender_name'],
            isset($data['event_title']) => 'Invitacion a '.$data['event_title'],
            default => 'Nueva notificacion',
        };
    }

    /**
     * Cuerpo por defecto derivado de los campos comunes del
     * payload.
     */
    private function defaultBody(array $data): string
    {
        if (isset($data['content_preview'])) {
            return (string) $data['content_preview'];
        }

        if (isset($data['project_name'])) {
            return 'Proyecto: '.$data['project_name'];
        }

        if (isset($data['event_title']) && isset($data['starts_at_formatted'])) {
            return (string) $data['starts_at_formatted'];
        }

        return '';
    }

    /**
     * Renderiza la vista con la lista serializada de
     * notificaciones. Mantenemos la transformacion en PHP
     * (no en Blade) por la misma razon que el helper
     * `serialize()`: testabilidad y legibilidad.
     */
    public function render(): View
    {
        $items = $this->notifications
            ->map(fn (DatabaseNotification $n) => $this->serialize($n))
            ->all();

        return view('livewire.shared.notification-bell', [
            'items' => $items,
            'unreadCount' => $this->unreadCount,
            'preferencesUrl' => $this->user->isAdmin()
                ? route('admin.notifications.preferences')
                : route('portal.notifications.preferences'),
        ]);
    }
}
