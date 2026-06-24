<?php

namespace App\Livewire\Portal\TimeTracking;

use App\Models\Project;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Resumen de tiempo de un proyecto desde la
 * perspectiva del cliente del portal.
 *
 * El portal es de solo lectura y aplica la regla de
 * privacidad: se muestran los totales agregados
 * (total de horas del proyecto + breakdown por
 * miembro) pero NO las descripciones individuales
 * ni el desglose por tarea. Asi se respeta la
 * confidencialidad de la informacion interna del
 * equipo (por ejemplo, "Refactor del modulo de
 * auth" podria no querer compartirla literalmente
 * con el cliente).
 */
class ProjectTimeSummary extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * Fecha desde (inclusive) en formato Y-m-d.
     */
    #[Url(as: 'from')]
    public string $fromDate = '';

    /**
     * Fecha hasta (inclusive) en formato Y-m-d.
     */
    #[Url(as: 'to')]
    public string $toDate = '';

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
     * Convierte los filtros a Carbon.
     *
     * @return array{from: ?Carbon, to: ?Carbon}
     */
    public function resolvedFilters(): array
    {
        return [
            'from' => $this->fromDate !== '' ? Carbon::parse($this->fromDate)->startOfDay() : null,
            'to' => $this->toDate !== '' ? Carbon::parse($this->toDate)->endOfDay() : null,
        ];
    }

    /**
     * Resumen agregado del proyecto aplicando los
     * filtros. Aqui solo se consumen los campos
     * `total_minutes`, `total_entries` y
     * `by_member`; los demas campos del array no
     * se exponen al cliente por privacidad.
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
        );
    }

    public function clearFilters(): void
    {
        $this->fromDate = Carbon::now()->subMonth()->toDateString();
        $this->toDate = Carbon::now()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.portal.time-tracking.project-time-summary', [
            'project' => $this->project,
            'summary' => $this->summary,
        ]);
    }
}
