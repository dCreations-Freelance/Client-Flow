<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Notifications\NewProjectMessage;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Vista del chat de un proyecto en el portal cliente.
 *
 * Misma estructura que el controlador admin: vista estatica que
 * monta el componente Livewire compartido, mas un endpoint POST
 * para envio HTTP (util para tests y fallback).
 */
class ProjectMessageController extends Controller
{
    /**
     * Muestra el chat del proyecto al cliente.
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        return view('portal.projects.chat', [
            'project' => $project,
        ]);
    }

    /**
     * Endpoint HTTP para enviar un mensaje desde el portal. El
     * grueso de los envios se hace via Livewire, pero mantener
     * el endpoint facilita los tests.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        // Forzamos la policy de ProjectMessage porque
        // authorize('create', $project) infiere ProjectPolicy::create
        // (que es admin-only). Aqui queremos permitir clientes que
        // pueden ver el proyecto.
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

        // Registramos el mensaje en el feed de actividad para
        // que el portal tenga un timeline unificado.
        app(ActivityLogger::class)->messageSent($project, $message);

        \App\Models\ProjectChatRead::markAsRead($project, $user, $message->id);

        $recipients = $this->resolveRecipients($project, $user->id);
        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new NewProjectMessage($message, $project, $user),
            );
        }

        return redirect()
            ->route('portal.projects.chat', $project)
            ->with('status', 'Mensaje enviado.');
    }

    /**
     * Resuelve los destinatarios: union de miembros del proyecto
     * y miembros de la organizacion, sin el emisor.
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
