<?php

namespace App\Http\Controllers;

use App\Models\ProjectChatRead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Endpoint de "no leidos" para alimentar el polling client-side
 * de la PWA.
 *
 * Devuelve un JSON compacto con los contadores que el service
 * worker necesita para decidir si dispara una notificacion
 * client-side al detectar cambios. La PWA compara el snapshot
 * actual con el anterior y, si los contadores suben, manda un
 * `postMessage` al SW que muestra una notificacion del sistema.
 *
 * El calculo reutiliza la misma logica que los sidebars admin
 * y portal para mantener una sola fuente de verdad: en MVP se
 * cuentan mensajes de chat no leidos y tareas asignadas
 * pendientes. Si en una fase futura se anaden mas canales
 * (calendario, etc.), se anaden aqui.
 */
class NotificationsController extends Controller
{
    /**
     * Devuelve el snapshot actual de contadores para el usuario
     * autenticado. Pensado para que el cliente lo poll-e cada
     * 30s; no cacheamos nada en el servidor para mantener los
     * contadores exactos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $messages = $this->countUnreadMessages($user);
        $tasks = $this->countUnreadTasks($user);

        return response()->json([
            'messages' => $messages,
            'tasks' => $tasks,
            'total' => $messages + $tasks,
            'messages_url' => $this->messagesUrl($user),
            'tasks_url' => $this->tasksUrl($user),
        ]);
    }

    /**
     * Cuenta los mensajes de chat no leidos para el usuario en
     * todos sus proyectos visibles. Sigue el mismo patron que
     * el sidebar: para cada proyecto con marcador, cuenta los
     * mensajes con id mayor al `last_read_message_id`.
     *
     * @return int
     */
    private function countUnreadMessages(User $user): int
    {
        $reads = ProjectChatRead::query()
            ->where('user_id', $user->id)
            ->pluck('last_read_message_id', 'project_id');

        if ($reads->isEmpty()) {
            return 0;
        }

        $total = 0;
        $messages = app(\App\Models\ProjectMessage::class);

        foreach ($reads as $projectId => $lastRead) {
            $total += $messages::query()
                ->where('project_id', $projectId)
                ->where('id', '>', (int) $lastRead)
                ->count();
        }

        return $total;
    }

    /**
     * Cuenta las tareas asignadas al usuario que estan
     * pendientes. Consideramos "pendiente" cualquier tarea raiz
     * (sin parent) sin `completed_at` que tenga `due_date`
     * futuro o sin `due_date`.
     *
     * @return int
     */
    private function countUnreadTasks(User $user): int
    {
        return Task::query()
            ->whereNull('parent_id')
            ->whereNull('completed_at')
            ->where('assignee_id', $user->id)
            ->count();
    }

    /**
     * URL a la que debe llevar la notificacion de un mensaje
     * nuevo. Admin y cliente van a vistas diferentes del
     * chat, asi que elegimos segun el rol.
     */
    private function messagesUrl(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        return route('portal.dashboard');
    }

    /**
     * URL a la que debe llevar la notificacion de tarea
     * asignada. Mismo patron que `messagesUrl`.
     */
    private function tasksUrl(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        return route('portal.dashboard');
    }
}
