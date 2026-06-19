<?php

namespace App\Livewire\Shared\AiChat;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

/**
 * Componente compartido (admin y portal) que renderiza la
 * conversacion con el asistente IA y gestiona el envio de
 * nuevos mensajes.
 *
 * Mismo patron que `Shared\ChatWindow` (fase 5): un solo
 * componente para las dos zonas, autorizacion via policy
 * del proyecto y de la sesion.
 *
 * Comportamiento:
 * - Carga los mensajes visibles de la sesion (excluye
 *   `system` que es interno).
 * - Al enviar, valida el input, llama a `AiService` y
 *   re-renderiza.
 * - Los errores del provider (HTTP no-2xx, rate limit)
 *   se muestran como `$error` para que la vista los
 *   renderice inline.
 * - Polling cada 5s (configurable en la vista) para que
 *   varios miembros del equipo viendo la misma sesion
 *   vean los mensajes nuevos. Aqui no se usa porque cada
 *   sesion es personal, pero dejamos el hook para
 *   futuras sesiones compartidas.
 */
class ChatWindow extends Component
{
    use AuthorizesRequests;

    /**
     * Proyecto al que pertenece la sesion.
     */
    public Project $project;

    /**
     * Sesion de chat activa.
     */
    public AiChatSession $session;

    /**
     * Usuario autenticado. Se fija en mount y se reusa en
     * cada render para evitar inconsistencias.
     */
    public User $user;

    /**
     * Contenido del textarea.
     *
     * @var string
     */
    public string $newMessage = '';

    /**
     * Flag de envio en curso. Se usa para deshabilitar el
     * boton mientras llega la respuesta del provider y
     * evitar dobles envios accidentales.
     *
     * @var bool
     */
    public bool $sending = false;

    /**
     * Mensaje de error a mostrar al usuario (rate limit,
     * provider caido, etc.).
     *
     * @var string|null
     */
    public ?string $error = null;

    /**
     * Inicializa el componente: autoriza contra la sesion
     * y captura el usuario autenticado.
     */
    public function mount(Project $project, AiChatSession $session): void
    {
        $this->project = $project;
        $this->session = $session;
        $this->user = Auth::user();
        $this->authorize('view', $session);
    }

    /**
     * Mensajes visibles (sin system) en orden cronologico.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AiChatMessage>
     */
    public function getMessagesProperty()
    {
        return $this->session->messages()
            ->where('role', '!=', \App\Enums\AiChatRole::System->value)
            ->orderBy('id')
            ->get();
    }

    /**
     * Validacion del input del usuario.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'newMessage' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    /**
     * Envia el mensaje a la IA y refresca la conversacion.
     * Captura las excepciones del provider y del rate
     * limit para mostrarlas de forma legible.
     */
    public function sendMessage(AiService $ai): void
    {
        if ($this->sending) {
            return;
        }

        $this->authorize('view', $this->session);

        $this->validate();

        $this->sending = true;
        $this->error = null;

        try {
            $ai->sendMessage(
                $this->project,
                $this->user,
                $this->session,
                $this->newMessage,
            );

            $this->reset(['newMessage']);
            $this->dispatch('ai-chat-message-sent');
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->sending = false;
        }
    }

    /**
     * Hook para refrescar la conversacion cuando llega un
     * mensaje nuevo (futuras sesiones compartidas).
     */
    #[On('ai-chat-refresh')]
    public function refresh(): void
    {
        // No hace nada en MVP porque las sesiones son
        // personales. El hook queda para fases futuras.
    }

    /**
     * Render del componente. Pasa los mensajes a la vista
     * sin aplicar caches de Livewire: los `getMessagesProperty`
     * computed properties se cachean automaticamente.
     */
    public function render(): View
    {
        return view('livewire.shared.ai-chat.chat-window', [
            'messages' => $this->messages,
        ]);
    }
}
