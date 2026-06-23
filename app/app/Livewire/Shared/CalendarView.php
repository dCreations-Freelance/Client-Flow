<?php

namespace App\Livewire\Shared;

use App\Enums\CalendarEventType;
use App\Enums\NotificationEvent;
use App\Http\Requests\Admin\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CalendarEventInvitation;
use App\Services\Activity\ProjectActivityLogger;
use App\Services\Calendar\CalendarQueryService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Componente Livewire compartido del calendario por proyecto.
 *
 * Es el mismo componente para admin y portal: la diferencia la
 * aplica el flag `readOnly` (que oculta los botones de creacion
 * y edicion) y la autorizacion que hace cada policy al guardar.
 * Esto evita duplicar la logica de navegacion, queries y modal
 * entre las dos zonas.
 *
 * Comportamiento:
 * - Vista mensual por defecto, switch a semanal.
 * - Navegacion: anterior / siguiente / hoy.
 * - Estado del periodo actual en query string (`#[Url]`) para que
 *   la URL sea compartible.
 * - Modal de crear/editar evento con campos basicos, selector
 *   all-day y multi-select de asistentes.
 * - Modal "ver mas" para dias con muchos eventos.
 * - Al guardar, dispara `ProjectActivityLogger` para el chat y
 *   `CalendarEventInvitation` a los asistentes.
 */
class CalendarView extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * Usuario autenticado. Se fija en mount y se reusa en cada
     * render para evitar inconsistencias si la sesion cambia a
     * mitad de un request.
     */
    public User $user;

    /**
     * Si es true, la UI oculta los controles de creacion,
     * edicion y eliminacion. Lo activa el controlador del portal.
     */
    public bool $readOnly = false;

    /**
     * Vista activa: 'month' o 'week'. Sincronizada con la URL
     * para que sea compartible.
     */
    #[Url(as: 'view')]
    public string $view = 'month';

    /**
     * Fecha de referencia (cualquier dia del mes/semana que se
     * esta mostrando). Se pasa a Carbon en mount.
     */
    #[Url(as: 'date')]
    public string $currentDate = '';

    /**
     * Estado del modal de evento. null = cerrado. Si esta
     * presente contiene los campos del formulario.
     *
     * @var array<string, mixed>|null
     */
    public ?array $eventForm = null;

    /**
     * Ids de asistentes seleccionados en el formulario. Se
     * mantienen separados de `eventForm` para simplificar el
     * binding del multi-select y permitir la gestion por chunks.
     *
     * @var array<int, int>
     */
    public array $attendeeIds = [];

    /**
     * Inicializa el componente con el proyecto. La autorizacion
     * se hace contra el proyecto, no contra cada evento, para
     * mantener la query inicial eficiente.
     */
    public function mount(Project $project, bool $readOnly = false): void
    {
        $this->project = $project;
        $this->user = Auth::user();
        $this->readOnly = $readOnly;

        $this->authorize('view', $project);

        if ($this->currentDate === '') {
            $this->currentDate = Carbon::now()->format('Y-m-d');
        }
    }

    // -----------------------------------------------------------------
    // Navegacion del periodo
    // -----------------------------------------------------------------

    /**
     * Avanza o retrocede un mes/semana segun la vista activa.
     *
     * @param  int  $delta  +1 o -1
     */
    public function shiftPeriod(int $delta): void
    {
        $current = $this->resolveCurrentDate();

        if ($this->view === 'week') {
            $this->currentDate = $current->addWeeks($delta)->format('Y-m-d');
        } else {
            $this->currentDate = $current->addMonthsNoOverflow($delta)->format('Y-m-d');
        }
    }

    /**
     * Vuelve a la fecha actual.
     */
    public function goToToday(): void
    {
        $this->currentDate = Carbon::now()->format('Y-m-d');
    }

    /**
     * Cambia la vista activa (month/week).
     *
     * @param  string  $view
     */
    public function setView(string $view): void
    {
        if (in_array($view, ['month', 'week'], true)) {
            $this->view = $view;
        }
    }

    /**
     * Resuelve la fecha actual como Carbon. Centraliza la
     * conversion para no repetirla en cada handler.
     */
    public function resolveCurrentDate(): Carbon
    {
        return Carbon::parse($this->currentDate);
    }

    // -----------------------------------------------------------------
    // Modal de crear/editar
    // -----------------------------------------------------------------

    /**
     * Abre el modal de creacion de evento. La fecha inicial se
     * pre-rellena con el dia clicado en la grilla.
     *
     * @param  string  $date  formato Y-m-d
     */
    public function openCreateForm(string $date): void
    {
        $this->guardNotReadOnly();

        $this->authorize('create', CalendarEvent::class);

        $this->resetFormState();

        $this->eventForm = [
            'mode' => 'create',
            'event_id' => null,
            'title' => '',
            'description' => '',
            'type' => CalendarEventType::Meeting->value,
            'is_all_day' => false,
            'starts_at' => $date.' 09:00',
            'ends_at' => $date.' 10:00',
        ];
    }

    /**
     * Abre el modal de edicion con los datos de un evento
     * existente. Carga los asistentes actuales.
     */
    public function openEditForm(int $eventId): void
    {
        $this->guardNotReadOnly();

        $event = CalendarEvent::with('attendees')->find($eventId);
        if ($event === null) {
            return;
        }

        $this->authorize('update', $event);

        abort_unless($event->project_id === $this->project->id, 404);

        $this->resetFormState();

        $this->eventForm = [
            'mode' => 'edit',
            'event_id' => $event->id,
            'title' => $event->title,
            'description' => $event->description ?? '',
            'type' => $event->type?->value ?? CalendarEventType::Meeting->value,
            'is_all_day' => (bool) $event->is_all_day,
            'starts_at' => $event->starts_at?->format('Y-m-d H:i') ?? '',
            'ends_at' => $event->ends_at?->format('Y-m-d H:i') ?? '',
        ];

        $this->attendeeIds = $event->attendees->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Cierra el modal y resetea el estado del formulario.
     */
    public function closeForm(): void
    {
        $this->eventForm = null;
        $this->attendeeIds = [];
        $this->resetErrorBag();
    }

    /**
     * Anade un asistente a la seleccion actual. Pensado para el
     * handler del `<select>` del modal. Se valida contra la lista
     * de usuarios disponibles para evitar ids manipulados.
     *
     * @param  int|string  $userId
     */
    public function addAttendee(int|string $userId): void
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return;
        }

        $validIds = app(CalendarQueryService::class)
            ->availableAttendees($this->project, $this->user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array($userId, $validIds, true)) {
            return;
        }

        if (! in_array($userId, $this->attendeeIds, true)) {
            $this->attendeeIds[] = $userId;
        }
    }

    /**
     * Resetea el estado del formulario a los valores por
     * defecto. Pensado para llamarse antes de abrir el modal
     * en cualquier modo.
     */
    private function resetFormState(): void
    {
        $this->eventForm = null;
        $this->attendeeIds = [];
        $this->resetErrorBag();
    }

    /**
     * Persiste el evento (crear o editar). Se valida a traves de
     * la misma Form Request que el endpoint HTTP, para mantener
     * una sola fuente de verdad sobre las reglas.
     */
    public function saveEvent(): void
    {
        $this->guardNotReadOnly();

        if ($this->eventForm === null) {
            return;
        }

        $isEdit = ($this->eventForm['mode'] ?? null) === 'edit';

        $formData = $this->collectFormData();

        $request = new StoreCalendarEventRequest();
        $request->merge($formData);
        // Sincronizamos manualmente el validador: Livewire
        // normalmente usa `rules()` del componente, pero al
        // reusar la Form Request hacemos la validacion contra
        // sus reglas.
        $validator = validator($formData, $request->rules(), $request->messages());
        $this->resetErrorBag();
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError('eventForm.'.$field, $message);
                }
            }

            return;
        }

        $data = $this->normalizeFormData($formData);

        if ($isEdit) {
            $event = CalendarEvent::find($this->eventForm['event_id']);
            if ($event === null) {
                $this->closeForm();

                return;
            }
            $this->authorize('update', $event);
            abort_unless($event->project_id === $this->project->id, 404);

            $event->update($data);
        } else {
            $this->authorize('create', CalendarEvent::class);
            $data['project_id'] = $this->project->id;
            $data['created_by'] = $this->user->id;
            $event = CalendarEvent::create($data);
        }

        $attendees = $this->resolveAttendees();
        $event->attendees()->sync($attendees);

        $actor = $this->user;
        if ($isEdit) {
            app(ProjectActivityLogger::class)->eventUpdated($this->project, $event, $actor);
        } else {
            app(ProjectActivityLogger::class)->eventCreated($this->project, $event, $actor);
        }

        // Notificamos solo a los attendees invitados que no son
        // el emisor, para evitar auto-notificaciones. Pasamos por
        // el dispatcher para respetar el opt-out por canal.
        $recipients = $this->project->organization?->members()
            ->whereIn('users.id', $attendees)
            ->where('users.id', '!=', $actor->id)
            ->get() ?? collect();
        $projectRecipients = $this->project->members()
            ->whereIn('users.id', $attendees)
            ->where('users.id', '!=', $actor->id)
            ->get();
        $recipients = $recipients->merge($projectRecipients)->unique('id');

        if ($recipients->isNotEmpty()) {
            NotificationDispatcher::dispatchToMany(
                $recipients,
                new CalendarEventInvitation($event, $this->project, $actor),
                NotificationEvent::EventInvitation,
            );
        }

        $this->closeForm();
        $this->dispatch('calendar-event-saved', eventId: $event->id);
    }

    /**
     * Elimina un evento. Misma logica de policy + project
     * ownership que el resto de mutaciones.
     */
    public function deleteEvent(int $eventId): void
    {
        $this->guardNotReadOnly();

        $event = CalendarEvent::find($eventId);
        if ($event === null) {
            return;
        }

        $this->authorize('delete', $event);
        abort_unless($event->project_id === $this->project->id, 404);

        $title = $event->title;
        $event->delete();

        app(ProjectActivityLogger::class)->eventDeleted($this->project, $title, $this->user);

        $this->dispatch('calendar-event-deleted', eventId: $eventId);
    }

    /**
     * Recoge los datos del formulario en un array asociativo
     * listo para validar. Pensado para compartir la logica entre
     * alta y edicion.
     *
     * @return array<string, mixed>
     */
    private function collectFormData(): array
    {
        $form = $this->eventForm ?? [];

        return [
            'title' => $form['title'] ?? '',
            'description' => $form['description'] ?? null,
            'type' => $form['type'] ?? CalendarEventType::Meeting->value,
            'is_all_day' => (bool) ($form['is_all_day'] ?? false),
            'starts_at' => $form['starts_at'] ?? null,
            'ends_at' => $form['ends_at'] ?? null,
            'attendees' => $this->attendeeIds,
        ];
    }

    /**
     * Normaliza los datos validados para la persistencia.
     * Aplica las mismas reglas que `StoreCalendarEventRequest::
     * eventData()`: all-day lleva a 00:00 / 23:59.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeFormData(array $data): array
    {
        $normalized = [
            'title' => trim($data['title']),
            'description' => $data['description'] ?: null,
            'type' => $data['type'],
            'is_all_day' => (bool) $data['is_all_day'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?: null,
        ];

        if ($normalized['is_all_day']) {
            $start = Carbon::parse($normalized['starts_at']);
            $normalized['starts_at'] = $start->copy()->startOfDay();
            $normalized['ends_at'] = $normalized['ends_at']
                ? Carbon::parse($normalized['ends_at'])->endOfDay()
                : $start->copy()->endOfDay();
        }

        return $normalized;
    }

    /**
     * Resuelve los ids de asistentes validos: usuarios que
     * pertenecen al proyecto o a la organizacion. Esto cierra
     * un vector de fuga donde un admin pasara un id de usuario
     * ajeno al proyecto y este quedaria visible en el evento.
     *
     * @return array<int, int>
     */
    private function resolveAttendees(): array
    {
        $validIds = app(CalendarQueryService::class)
            ->availableAttendees($this->project)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_intersect($this->attendeeIds, $validIds));
    }

    /**
     * Lanza una excepcion si el componente esta en modo
     * read-only. Es una salvaguarda de UI: aunque la policy
     * rechace la accion, queremos cortar antes para no
     * ensuciar el log con 403 esperados.
     */
    private function guardNotReadOnly(): void
    {
        if ($this->readOnly) {
            abort(403);
        }
    }

    // -----------------------------------------------------------------
    // Propiedades computadas (accesores Livewire)
    // -----------------------------------------------------------------

    /**
     * Datos del calendario: grilla de dias, eventos por dia y
     * deadlines virtuales por dia. Se cachea en la instancia
     * del componente para que multiples referencias en la vista
     * (header, grid, modal) no recalculen.
     *
     * @return array<string, mixed>
     */
    public function getCalendarDataProperty(): array
    {
        return app(CalendarQueryService::class)
            ->getCalendarData($this->project, $this->resolveCurrentDate(), $this->view);
    }

    /**
     * Lista de usuarios disponibles para invitar al evento.
     * Reutiliza el helper del servicio para no duplicar la
     * logica de union de miembros.
     *
     * @return Collection<int, User>
     */
    public function getAvailableAttendeesProperty(): Collection
    {
        return app(CalendarQueryService::class)
            ->availableAttendees($this->project, $this->user->id);
    }

    /**
     * Eventos de los proximos 7 dias como badge del header.
     * Pensado para anadirse en una fase futura (notificaciones
     * in-app) y por ahora se mantiene el calculo accesible.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getUpcomingEventsProperty(): Collection
    {
        $now = Carbon::now();
        $to = $now->copy()->addDays(7);

        return $this->project->calendarEvents()
            ->with('attendees')
            ->whereBetween('starts_at', [$now, $to])
            ->ordered()
            ->get();
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    /**
     * Renderiza la vista del calendario pasando los datos
     * calculados por el servicio. Se pasa `currentDate` como
     * Carbon y `view` como string: el resto se computa en el
     * servicio.
     */
    public function render(): View
    {
        $data = $this->calendarData;
        $currentDate = $this->resolveCurrentDate();

        return view('livewire.shared.calendar-view', [
            'project' => $this->project,
            'carbonDate' => $currentDate,
            'view' => $this->view,
            'readOnly' => $this->readOnly,
            'days' => $data['days'],
            'eventsByDay' => $data['events_by_day'],
            'deadlinesByDay' => $data['deadlines_by_day'],
            'availableAttendees' => $this->availableAttendees,
            'eventTypes' => [
                CalendarEventType::Meeting->value => CalendarEventType::Meeting->label(),
                CalendarEventType::Milestone->value => CalendarEventType::Milestone->label(),
            ],
            'isCurrentMonth' => fn (Carbon $date): bool => $date->isSameMonth($currentDate),
        ]);
    }
}
