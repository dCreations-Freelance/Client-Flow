<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiChatSession;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vista del chat IA de un proyecto desde el panel admin.
 *
 * El admin usa la misma UI que el cliente (componente
 * Livewire `Shared\AiChatWindow`); la diferencia es la
 * zona de layout y la posibilidad de ver sesiones de
 * otros miembros del proyecto (politica).
 *
 * El grueso de la interaccion (envio de mensajes,
 * polling, etc.) se hace desde el componente Livewire
 * para evitar recargas completas de la pagina.
 */
class AiChatController extends Controller
{
    /**
     * Resuelve la sesion activa (la ultima del admin en
     * este proyecto) o, si no hay ninguna, redirige a la
     * pantalla de "nueva conversacion" para que la cree.
     */
    public function index(Request $request, Project $project): View|RedirectResponse
    {
        $this->authorize('view', $project);

        $session = AiChatSession::query()
            ->where('project_id', $project->id)
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->first();

        if ($session === null) {
            return view('admin.projects.ai-empty', [
                'project' => $project,
            ]);
        }

        return redirect()->route('admin.projects.ai.show', [
            'project' => $project->id,
            'session' => $session->id,
        ]);
    }

    /**
     * Muestra una sesion concreta.
     */
    public function show(Request $request, Project $project, AiChatSession $session): View
    {
        $this->authorize('view', $session);

        return view('admin.projects.ai', [
            'project' => $project,
            'session' => $session,
        ]);
    }

    /**
     * Borra una sesion (admin: cualquier sesion del
     * proyecto; el policy ya filtra).
     */
    public function destroy(Request $request, Project $project, AiChatSession $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        $session->delete();

        return redirect()
            ->route('admin.projects.ai', $project)
            ->with('status', 'Sesion de chat IA eliminada.');
    }
}
