<?php

namespace App\Livewire\Admin\TimeTracking;

use App\Enums\TimeEntryType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Componente Livewire de registro de tiempo de una tarea.
 *
 * Se monta en la vista de detalle admin de tarea
 * (`admin/projects/tasks/show.blade.php`). Reune en un
 * solo lugar la pieza de cronometro (start/stop con
 * auto-stop del timer anterior) y la pieza de
 * administracion manual de entradas (crear, editar,
 * eliminar y marcar como facturable).
 *
 * Decisiones de diseno:
 * - El cronometro es global por usuario: solo puede
 *   haber un timer activo a la vez. Si el admin
 *   arranca uno en otra tarea, el anterior se cierra
 *   y se persiste con los minutos calculados.
 * - El contador del cronometro se actualiza en
 *   cliente (JS) cada segundo para no castigar al
 *   servidor. La fuente de verdad sigue siendo
 *   `TimeEntry::started_at`: el JS solo pinta.
 * - Los filtros del dashboard no viven aqui: este
 *   componente se centra en la tarea concreta. Los
 *   filtros y agregados del proyecto estan en
 *   `ProjectTimeDashboard`.
 */
class TimeTracker extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public Task $task;

    /**
     * Id del timer activo del usuario actual (si lo
     * tiene corriendo). Se inicializa en `mount` y se
     * actualiza en cada start/stop.
     *
     * Lo inicializamos a 0 (no nullable) porque Livewire
     * no serializa `null` en propiedades primitivas;
     * la vista interpreta `0` como "sin timer".
     */
    public int $activeTimerId = 0;

    /**
     * Estado del modal de entrada manual. null = cerrado.
     * array con campos: mode (`create` o `edit`),
     * `entry_id` (solo en edit), `description`, `minutes`,
     * `billed`.
     *
     * @var array<string, mixed>|null
     */
    public ?array $entryForm = null;

    /**
     * Timestamp de inicio del timer activo en formato
     * ISO. La vista lo usa como origen para el
     * contador en cliente. Vacio si no hay timer.
     */
    public string $activeTimerStartedAt = '';

    public function mount(Project $project, Task $task): void
    {
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }

        $this->project = $project;
        $this->task = $task;

        $this->authorize('view', $task);

        $this->loadActiveTimer();
    }

    /**
     * Carga el timer activo del usuario actual y
     * rellena las propiedades relacionadas. Pensado
     * para llamarse tras cada mutacion (start/stop).
     *
     * @return void
     */
    private function loadActiveTimer(): void
    {
        $active = app(TimeTrackingService::class)->getActiveTimer(Auth::user());
        $this->activeTimerId = $active?->id ?? 0;
        $this->activeTimerStartedAt = $active?->started_at?->toIso8601String() ?? '';
    }

    /**
     * Arranca el cronometro en la tarea actual. Si ya
     * habia un timer activo (en cualquier tarea), el
     * servicio lo cierra automaticamente con los minutos
     * calculados.
     */
    public function startTimer(): void
    {
        $this->authorize('create', [TimeEntry::class, $this->project]);

        $service = app(TimeTrackingService::class);
        $service->startTimer($this->task, Auth::user());
        $this->loadActiveTimer();
        $this->dispatch('time-tracker-updated');
    }

    /**
     * Para el timer activo. No-op si no hay timer.
     */
    public function stopTimer(): void
    {
        $service = app(TimeTrackingService::class);
        $service->stopTimer(Auth::user());
        $this->loadActiveTimer();
        $this->dispatch('time-tracker-updated');
    }

    /**
     * Abre el modal de entrada manual en modo
     * creacion. Rellena valores por defecto.
     */
    public function openManualEntryForm(): void
    {
        $this->authorize('create', [TimeEntry::class, $this->project]);

        $this->entryForm = [
            'mode' => 'create',
            'entry_id' => null,
            'description' => '',
            'minutes' => null,
            'billed' => false,
        ];
        $this->resetErrorBag();
    }

    /**
     * Abre el modal en modo edicion con los datos
     * actuales de la entrada. El campo `type` no se
     * expone a la UI: una entrada no cambia de
     * `manual` a `timer` ni viceversa tras crearse.
     */
    public function openEditForm(int $entryId): void
    {
        $entry = TimeEntry::find($entryId);
        if ($entry === null || (int) $entry->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('update', $entry);

        $this->entryForm = [
            'mode' => 'edit',
            'entry_id' => $entry->id,
            'description' => $entry->description ?? '',
            'minutes' => $entry->minutes,
            'billed' => $entry->isBillable(),
        ];
        $this->resetErrorBag();
    }

    /**
     * Cierra el modal y resetea el estado del
     * formulario.
     */
    public function closeForm(): void
    {
        $this->entryForm = null;
        $this->resetErrorBag();
    }

    /**
     * Persiste la entrada (crear o editar). Las
     * reglas se replican desde `StoreTimeEntryRequest`
     * y `UpdateTimeEntryRequest` para mantener una
     * sola fuente de verdad en la capa HTTP.
     */
    public function saveManualEntry(): void
    {
        if ($this->entryForm === null) {
            return;
        }

        $isEdit = ($this->entryForm['mode'] ?? null) === 'edit';
        $rules = [
            'entryForm.description' => ['nullable', 'string', 'max:5000'],
            'entryForm.minutes' => ['required', 'integer', 'min:1', 'max:60000'],
            'entryForm.billed' => ['nullable', 'boolean'],
        ];
        $this->validate($rules);

        $service = app(TimeTrackingService::class);
        $payload = [
            'description' => $this->entryForm['description'] ?? null,
            'minutes' => (int) $this->entryForm['minutes'],
            'billed' => (bool) ($this->entryForm['billed'] ?? false),
        ];

        if ($isEdit) {
            $entry = TimeEntry::find($this->entryForm['entry_id']);
            if ($entry === null) {
                $this->closeForm();

                return;
            }
            $this->authorize('update', $entry);
            $service->updateEntry($entry, $payload);
        } else {
            $this->authorize('create', [TimeEntry::class, $this->project]);
            $payload['type'] = TimeEntryType::Manual->value;
            $payload['entry_date'] = Carbon::now()->toDateString();
            $service->createManualEntry($this->task, Auth::user(), $payload);
        }

        $this->closeForm();
        $this->dispatch('time-tracker-updated');
    }

    /**
     * Elimina una entrada. La cache
     * `total_logged_minutes` se actualiza via observer.
     */
    public function deleteEntry(int $entryId): void
    {
        $entry = TimeEntry::find($entryId);
        if ($entry === null || (int) $entry->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('delete', $entry);
        $entry->delete();
        $this->dispatch('time-tracker-updated');
    }

    /**
     * Alterna el flag `billed` de una entrada.
     * Pensado para el boton "Marcar como facturable"
     * de cada fila de la lista.
     */
    public function toggleBilled(int $entryId): void
    {
        $entry = TimeEntry::find($entryId);
        if ($entry === null || (int) $entry->task_id !== (int) $this->task->id) {
            abort(404);
        }

        $this->authorize('update', $entry);

        if ($entry->isBillable()) {
            $entry->markAsUnbilled();
        } else {
            $entry->markAsBilled();
        }
        $this->dispatch('time-tracker-updated');
    }

    /**
     * Hook para refrescar el estado del timer cuando
     * otro componente lo modifica (por ejemplo, si en
     * una fase futura se permite control desde el
     * dashboard o un shortcut de teclado). No es
     * necesario en el flujo normal.
     */
    #[On('time-tracker-updated')]
    public function refreshActiveTimer(): void
    {
        $this->loadActiveTimer();
    }

    /**
     * Entradas de la tarea en orden cronologico
     * inverso. Se computa cada vez que el componente
     * se re-renderiza; el coste es bajo (pocas
     * entradas por tarea) y asi nos ahorramos una
     * cache de propiedades.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TimeEntry>
     */
    #[Computed]
    public function entries()
    {
        return $this->task->timeEntries()->with('user')->get();
    }

    /**
     * Render del componente. Pasa la coleccion de
     * entradas y el contexto de usuario (admin) a la
     * vista.
     */
    public function render(): View
    {
        return view('livewire.admin.time-tracking.time-tracker', [
            'entries' => $this->entries,
        ]);
    }
}
