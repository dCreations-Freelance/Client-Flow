<?php

namespace App\Livewire\Portal\AiChat;

use App\Models\AiChatSession;
use App\Models\Project;
use App\Services\Ai\AiService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

/**
 * Sidebar de sesiones de chat IA para el portal del cliente.
 *
 * Lista las sesiones del usuario autenticado en el proyecto
 * actual, ordenadas por la mas reciente primero. Permite
 * iniciar una nueva conversacion (con rate limit) y borrar
 * sesiones existentes.
 *
 * Se monta desde las vistas `portal/projects/ai.blade.php` y
 * `portal/projects/ai-empty.blade.php`.
 */
class SessionList extends Component
{
    use AuthorizesRequests;

    /**
     * Proyecto del que se listan las sesiones.
     */
    public Project $project;

    /**
     * Sesion actualmente abierta. Se usa para resaltarla
     * en la lista. `null` = ninguna (caso de la vista
     * "empty").
     *
     * @var int|null
     */
    public ?int $currentSessionId = null;

    /**
     * Mensaje de error a mostrar al usuario (rate limit).
     *
     * @var string|null
     */
    public ?string $error = null;

    /**
     * Inicializa el componente.
     */
    public function mount(Project $project, ?int $currentSessionId = null): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
        $this->currentSessionId = $currentSessionId;
    }

    /**
     * Sesiones del usuario en el proyecto.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AiChatSession>
     */
    public function getSessionsProperty()
    {
        return AiChatSession::query()
            ->forUserInProject(Auth::id(), $this->project->id)
            ->get();
    }

    /**
     * Crea una nueva sesion y redirige a ella. Delega en
     * `AiService::createSession` para que el rate limit se
     * aplique de forma consistente con el resto del modulo.
     *
     * Usa `$this->redirectRoute()` (helper nativo de
     * Livewire) en vez de `return redirect()->route(...)`
     * porque la segunda devuelve un `Redirector` que no es
     * compatible con un return type estricto.
     */
    public function createSession(AiService $ai): void
    {
        $this->authorize('create', [\App\Models\AiChatSession::class, $this->project]);

        try {
            $session = $ai->createSession($this->project, Auth::user());
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirectRoute('portal.projects.ai.show', [
            'project' => $this->project->id,
            'session' => $session->id,
        ]);
    }

    /**
     * Borra una sesion y, si era la actual, redirige a la
     * pantalla principal del chat del proyecto.
     */
    public function deleteSession(int $sessionId): void
    {
        $session = AiChatSession::find($sessionId);
        if ($session === null) {
            return;
        }

        $this->authorize('delete', $session);

        $session->delete();

        if ($this->currentSessionId === $sessionId) {
            $this->redirectRoute('portal.projects.ai', ['project' => $this->project->id]);
        }
    }

    /**
     * Render del componente.
     */
    public function render(): View
    {
        return view('livewire.portal.ai-chat.session-list', [
            'sessions' => $this->sessions,
        ]);
    }
}
