<?php

namespace App\Livewire\Admin\AiChat;

use App\Models\AiChatSession;
use App\Models\Project;
use App\Services\Ai\AiService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

/**
 * Sidebar de sesiones de chat IA para el panel admin.
 *
 * Es un mirror de `Portal\AiChat\SessionList` pero pensado
 * para que el admin gestione sus propias conversaciones de
 * prueba. Si en una fase futura el admin quiere ver las
 * sesiones de cualquier miembro del proyecto, este es el
 * sitio donde anadir el filtro por usuario.
 */
class SessionList extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public ?int $currentSessionId = null;

    public ?string $error = null;

    public function mount(Project $project, ?int $currentSessionId = null): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
        $this->currentSessionId = $currentSessionId;
    }

    /**
     * Sesiones del admin en el proyecto.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AiChatSession>
     */
    public function getSessionsProperty()
    {
        return AiChatSession::query()
            ->forUserInProject(Auth::id(), $this->project->id)
            ->get();
    }

    public function createSession(AiService $ai): void
    {
        $this->authorize('create', [\App\Models\AiChatSession::class, $this->project]);

        try {
            $session = $ai->createSession($this->project, Auth::user());
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirectRoute('admin.projects.ai.show', [
            'project' => $this->project->id,
            'session' => $session->id,
        ]);
    }

    public function deleteSession(int $sessionId): void
    {
        $session = AiChatSession::find($sessionId);
        if ($session === null) {
            return;
        }

        $this->authorize('delete', $session);

        $session->delete();

        if ($this->currentSessionId === $sessionId) {
            $this->redirectRoute('admin.projects.ai', ['project' => $this->project->id]);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.ai-chat.session-list', [
            'sessions' => $this->sessions,
        ]);
    }
}
