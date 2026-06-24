<?php

namespace App\Livewire\Admin\TimeTracking;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dashboard de horas de un proyecto en el panel admin.
 *
 * Muestra los totales y breakdowns del proyecto
 * aplicando los filtros activos (rango de fechas,
 * facturable, miembro). La interaccion con cada
 * entrada individual (editar, eliminar, toggle
 * facturable) se hace desde el componente
 * `TimeTracker` montado en la vista de detalle de
 * tarea; aqui se ofrece unicamente el toggle de
 * facturable en lote.
 *
 * Los filtros se mantienen en query string con
 * `#[Url]` para que la URL sea compartible y la
 * exportacion CSV pueda usar la misma query.
 */
class ProjectTimeDashboard extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * Fecha desde (inclusive) en formato Y-m-d.
     * Sincronizada con la URL para que la vista y
     * la exportacion CSV compartan el filtro.
     */
    #[Url(as: 'from')]
    public string $fromDate = '';

    /**
     * Fecha hasta (inclusive) en formato Y-m-d.
     */
    #[Url(as: 'to')]
    public string $toDate = '';

    /**
     * Filtro de facturabilidad. Valores:
     * - '' (vacio): todas las entradas.
     * - 'billable': solo facturables.
     * - 'not_billable': solo no facturables.
     */
    #[Url(as: 'billable')]
    public string $billableFilter = '';

    /**
     * Filtro por miembro. Vacio = todos.
     */
    #[Url(as: 'user_id')]
    public ?int $userFilter = null;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;

        if ($this->fromDate === '') {
            $this->fromDate = Carbon::now()->subMonth()->toDateString();
        }
        if ($this->toDate === '') {
            $this->toDate = Carbon::now()->toDateString();
        }
    }

    /**
     * Resetea los filtros a los valores por defecto
     * (ultimo mes, todas las entradas).
     */
    public function clearFilters(): void
    {
        $this->fromDate = Carbon::now()->subMonth()->toDateString();
        $this->toDate = Carbon::now()->toDateString();
        $this->billableFilter = '';
        $this->userFilter = null;
    }

    /**
     * Convierte los filtros de la query string a
     * tipos consistentes para el servicio. Encapsula
     * la conversion a Carbon y la normalizacion del
     * flag de facturabilidad.
     *
     * @return array{from: ?Carbon, to: ?Carbon, billable: ?bool, user_id: ?int}
     */
    public function resolvedFilters(): array
    {
        return [
            'from' => $this->fromDate !== '' ? Carbon::parse($this->fromDate)->startOfDay() : null,
            'to' => $this->toDate !== '' ? Carbon::parse($this->toDate)->endOfDay() : null,
            'billable' => match ($this->billableFilter) {
                'billable' => true,
                'not_billable' => false,
                default => null,
            },
            'user_id' => $this->userFilter,
        ];
    }

    /**
     * Resumen agregado del proyecto con los filtros
     * aplicados. Se computa en cada render; el
     * servicio lanza tres queries (totales, por
     * miembro, por tarea) que son baratas incluso
     * con miles de entradas gracias a los indices.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        $filters = $this->resolvedFilters();

        return app(TimeTrackingService::class)->getProjectSummary(
            $this->project,
            $filters['from'],
            $filters['to'],
            $filters['billable'],
        );
    }

    /**
     * Entradas individuales filtradas, para la tabla
     * inferior del dashboard. Cargadas con autor y
     * tarea para evitar N+1 al pintar la lista.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TimeEntry>
     */
    #[Computed]
    public function entries()
    {
        $filters = $this->resolvedFilters();

        return $this->project->timeEntries()
            ->with(['user:id,name', 'task:id,title'])
            ->inDateRange($filters['from'], $filters['to'])
            ->when(
                $filters['billable'] === true,
                fn ($q) => $q->billable(),
            )
            ->when(
                $filters['billable'] === false,
                fn ($q) => $q->notBillable(),
            )
            ->when(
                $filters['user_id'] !== null,
                fn ($q) => $q->where('user_id', $filters['user_id']),
            )
            ->recent()
            ->limit(100)
            ->get();
    }

    /**
     * Lista de miembros con tiempo registrado en el
     * proyecto, para alimentar el filtro por
     * persona. Es una union entre los miembros del
     * proyecto y los miembros de la organizacion
     * (porque una entrada puede pertenecer a alguien
     * que no es miembro directo del proyecto, p. ej.
     * el admin que registra tiempo en una tarea).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    #[Computed]
    public function availableMembers()
    {
        // Union de: miembros del proyecto + miembros de
        // la organizacion que han registrado tiempo.
        $projectMembers = $this->project->members;
        $orgMemberIdsWithTime = TimeEntry::query()
            ->where('project_id', $this->project->id)
            ->pluck('user_id')
            ->unique();
        $orgMembers = \App\Models\User::query()
            ->whereIn('id', $orgMemberIdsWithTime)
            ->get();

        return $projectMembers->merge($orgMembers)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Render principal del dashboard.
     */
    public function render(): View
    {
        return view('livewire.admin.time-tracking.project-time-dashboard', [
            'project' => $this->project,
            'summary' => $this->summary,
            'entries' => $this->entries,
            'members' => $this->availableMembers,
            'filters' => $this->resolvedFilters(),
        ]);
    }
}
