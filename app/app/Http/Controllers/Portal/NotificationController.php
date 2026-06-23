<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Gemelo portal de `Admin\NotificationController`.
 *
 * Mismas acciones, mismas reglas, distinto prefijo de ruta y
 * distinta serializacion por defecto (no asumimos que el cliente
 * pueda ir a `/admin/*`).
 */
class NotificationController extends Controller
{
    /**
     * Lista las notificaciones in-app del cliente actual.
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
            ->map(fn (DatabaseNotification $n) => $this->serialize($n, $user));

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Marca una notificacion como leida. Solo afecta a las
     * notificaciones del usuario actual (filtro por
     * `notifiable`).
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
     * Marca todas las notificaciones del cliente actual como
     * leidas.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * Serializa la notificacion en un payload amigable para la
     * campana. Adapta las URLs para que el cliente aterrice en
     * rutas del portal siempre que sea posible.
     *
     * @return array<string, mixed>
     */
    private function serialize(DatabaseNotification $notification, $user): array
    {
        $data = $notification->data ?? [];

        $url = $data['url'] ?? null;
        if ($url !== null && str_contains($url, '/admin/')) {
            // Reescribimos las URLs de admin a portal. Es un
            // mapeo simple: el `notifications_url` se computo en
            // el momento del envio, pero como en MVP los unicos
            // puntos son kanban/chat/docs/calendar, todas cuelgan
            // del mismo proyecto, asi que el cambio de segmento
            // funciona. Si en una fase futura hay URLs que no
            // encajan, se aniade un mapeo explicito en el emisor.
            $url = str_replace('/admin/', '/portal/', $url);
        }

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $data['title'] ?? $this->defaultTitle($data),
            'body' => $data['body'] ?? $this->defaultBody($data),
            'url' => $url,
            'project_id' => $data['project_id'] ?? null,
            'project_name' => $data['project_name'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private function defaultTitle(array $data): string
    {
        return match (true) {
            isset($data['sender_name']) => 'Mensaje de '.$data['sender_name'],
            isset($data['event_title']) => 'Invitacion a '.$data['event_title'],
            default => 'Nueva notificacion',
        };
    }

    private function defaultBody(array $data): string
    {
        if (isset($data['content_preview'])) {
            return (string) $data['content_preview'];
        }

        if (isset($data['event_title']) && isset($data['starts_at_formatted'])) {
            return (string) $data['starts_at_formatted'];
        }

        if (isset($data['project_name'])) {
            return 'Proyecto: '.$data['project_name'];
        }

        return '';
    }
}
