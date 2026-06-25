<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Notifications\NewProjectMessage;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Vista del chat de un proyecto en el panel admin.
 *
 * El grueso de la logica interactiva (envio, polling, mark as
 * read) vive en el componente Livewire compartido
 * `Shared\ChatWindow`. Este controlador solo renderiza la vista
 * estatica y expone un endpoint POST para que los tests HTTP
 * (y futuros integraciones externas) puedan enviar mensajes
 * sin pasar por Livewire.
 */
class ProjectMessageController extends Controller
{
    /**
     * Muestra el chat del proyecto. La autorizacion la hace la
     * policy via el componente Livewire, pero la hacemos aqui
     * tambien para que un acceso directo por URL no devuelva 500
     * si algo del componente falla.
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        return view('admin.projects.chat', [
            'project' => $project,
        ]);
    }

    /**
     * Endpoint HTTP para enviar un mensaje. La mayoria de los
     * envios se hacen via Livewire (sin pasar por aqui), pero
     * mantener el endpoint es util para tests y como fallback
     * si Livewire falla en el cliente.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        // Forzamos la policy de ProjectMessage para que
        // authorize('create', $project) no infiera la policy
        // de Project (que es admin-only para create).
        $this->authorize('create', [\App\Models\ProjectMessage::class, $project]);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $user = Auth::user();

        $message = $project->messages()->create([
            'user_id' => $user->id,
            'content' => trim($data['content']),
            'type' => \App\Enums\MessageType::Text,
        ]);

        // Registramos el mensaje humano en el feed para que el
        // admin y el cliente tengan un timeline unificado de la
        // conversacion.
        app(ActivityLogger::class)->messageSent($project, $message);

        // Marcamos como leido para el emisor: ya "ve" su propio
        // mensaje, no debe contar como no leido.
        \App\Models\ProjectChatRead::markAsRead($project, $user, $message->id);

        // Notificamos a los destinatarios (miembros del proyecto
        // + miembros de la org, excluyendo al emisor). Pasamos
        // por el dispatcher para respetar las preferencias por
        // canal de cada destinatario.
        $recipients = $this->resolveRecipients($project, $user->id);
        if ($recipients->isNotEmpty()) {
            NotificationDispatcher::dispatchToMany(
                $recipients,
                new NewProjectMessage($message, $project, $user),
                NotificationEvent::NewMessage,
            );
        }

        return redirect()
            ->route('admin.projects.chat', $project)
            ->with('status', 'Mensaje enviado.');
    }

    /**
     * Resuelve los destinatarios: union de miembros directos del
     * proyecto y miembros de la organizacion, sin el emisor.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    private function resolveRecipients(Project $project, int $senderId)
    {
        $projectMemberIds = $project->members()->pluck('users.id');
        $orgMemberIds = $project->organization?->members()->pluck('users.id') ?? collect();

        return \App\Models\User::query()
            ->whereIn('id', $projectMemberIds->merge($orgMemberIds)->unique())
            ->where('id', '!=', $senderId)
            ->get();
    }
}
