<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Endpoints in-app del admin para la campana de notificaciones.
 *
 * Pensados para alimentar al componente Livewire `NotificationBell`
 * y al cliente PWA: `markAsRead` consume la cola de un usuario
 * concreto, `markAllAsRead` vacia la bandeja in-app, `index`
 * devuelve la lista paginada.
 *
 * Se exponen bajo el grupo admin porque solo el admin tiene
 * campana con badge rojo en MVP. Cuando se anada el portal, el
 * gemelo `Portal\NotificationController` reutilizara la misma
 * logica (con su propio prefijo de ruta).
 */
class NotificationController extends Controller
{
    /**
     * Devuelve las ultimas 20 notificaciones in-app del admin
     * actual en formato JSON. Pensado para alimentar la campana
     * cuando la pagina no usa Livewire (por ejemplo, una vista
     * server-rendered de detalle de proyecto).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DatabaseNotification $n) => $this->serialize($n));

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Marca una notificacion concreta como leida. Solo el dueno
     * puede marcarla: la consulta se hace con `whereNotifiable`
     * para que un id manipulado por otro usuario no le marque
     * sus notificaciones.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        $user->notifications()
            ->where('id', $notification)
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * Marca todas las notificaciones in-app del usuario actual
     * como leidas. Util cuando el usuario abre la campana y
     * dice "vale, ya las he visto todas".
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * Serializa una notificacion de la tabla `notifications` en
     * un payload compacto para el frontend. El `data` se
     * almacena como JSON con la informacion minima (titulo,
     * body, url, etc.) que la campana necesita.
     *
     * @return array<string, mixed>
     */
    private function serialize(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $data['title'] ?? $this->defaultTitle($data),
            'body' => $data['body'] ?? $this->defaultBody($data),
            'url' => $data['url'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'project_name' => $data['project_name'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    /**
     * Titulo por defecto cuando la notificacion no incluye un
     * campo `title` en su payload. Se deriva del `type` para
     * mantener un fallback razonable.
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
     * Cuerpo por defecto derivado de los campos comunes.
     */
    private function defaultBody(array $data): string
    {
        if (isset($data['content_preview'])) {
            return (string) $data['content_preview'];
        }

        if (isset($data['task_title']) && isset($data['project_name'])) {
            return sprintf('Proyecto: %s', $data['project_name']);
        }

        if (isset($data['event_title']) && isset($data['starts_at_formatted'])) {
            return (string) $data['starts_at_formatted'];
        }

        return '';
    }
}
