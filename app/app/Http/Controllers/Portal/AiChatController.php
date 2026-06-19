<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\CreateAiChatSessionRequest;
use App\Models\AiChatSession;
use App\Models\Project;
use App\Services\Ai\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Vista del chat IA de un proyecto desde el portal del
 * cliente.
 *
 * Mantiene la misma forma que el chat del admin pero
 * filtrando siempre por el usuario autenticado: cada
 * cliente solo ve sus propias sesiones.
 *
 * La interaccion fina (envio de mensajes, polling) se
 * hace desde el componente Livewire `Portal\AiChat\ChatWindow`.
 * Este controlador solo resuelve el "envelope" (que
 * sesion abrir, redirigir al crear, etc.).
 */
class AiChatController extends Controller
{
    /**
     * Abre la sesion mas reciente del cliente en el
     * proyecto. Si no hay ninguna, muestra la pantalla
     * de "nueva conversacion" con el boton para crearla.
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
            return view('portal.projects.ai-empty', [
                'project' => $project,
            ]);
        }

        return redirect()->route('portal.projects.ai.show', [
            'project' => $project->id,
            'session' => $session->id,
        ]);
    }

    /**
     * Muestra una sesion concreta del cliente.
     */
    public function show(Request $request, Project $project, AiChatSession $session): View
    {
        $this->authorize('view', $session);

        return view('portal.projects.ai', [
            'project' => $project,
            'session' => $session,
        ]);
    }

    /**
     * Crea una nueva sesion de chat para el cliente en el
     * proyecto. Valida el rate limit diario y redirige a
     * la sesion creada.
     */
    public function store(
        CreateAiChatSessionRequest $request,
        Project $project,
        AiService $ai,
    ): RedirectResponse {
        $this->authorize('create', [\App\Models\AiChatSession::class, $project]);

        $data = $request->validated();

        try {
            $session = $ai->createSession(
                $project,
                $request->user(),
                $data['title'] ?? null,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('portal.projects.ai', $project)
                ->with('ai_error', $e->getMessage());
        }

        return redirect()->route('portal.projects.ai.show', [
            'project' => $project->id,
            'session' => $session->id,
        ]);
    }

    /**
     * Borra una sesion del cliente.
     */
    public function destroy(Request $request, Project $project, AiChatSession $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        $session->delete();

        return redirect()
            ->route('portal.projects.ai', $project)
            ->with('status', 'Sesion de chat IA eliminada.');
    }
}
