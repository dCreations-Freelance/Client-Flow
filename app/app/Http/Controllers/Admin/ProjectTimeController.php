<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\TimeTracking\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dashboard de horas de un proyecto en el panel admin.
 *
 * Renderiza la vista con el componente Livewire
 * `ProjectTimeDashboard`, que se encarga de toda la
 * interaccion (filtros, marcado como facturable,
 * exportacion a CSV). El controlador solo resuelve:
 * - Autorizacion contra el proyecto.
 * - Calculo del filtro de fechas por defecto
 *   (ultimo mes) y conversion a Carbon para que la
 *   vista tenga siempre instancias validas.
 * - Endpoint de exportacion CSV: descarga un CSV
 *   plano con las entradas filtradas. Asi el admin
 *   puede importarlo en una hoja de calculo o un
 *   programa de facturacion.
 *
 * El componente Livewire gestiona la UI, pero el
 * CSV se genera aqui para que la descarga no
 * dependa de Livewire (las descargas via
 * `Response::streamDownload` desde Livewire son
 * posibles pero mas fragiles).
 */
class ProjectTimeController extends Controller
{
    public function __construct(
        private TimeTrackingService $timeTracking,
    ) {
    }

    /**
     * Muestra el dashboard del proyecto. El componente
     * Livewire se monta con los parametros por defecto
     * (ultimo mes, todas las entradas, todos los miembros).
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        $defaultFrom = Carbon::now()->subMonth()->startOfDay();
        $defaultTo = Carbon::now()->endOfDay();

        return view('admin.projects.time.index', [
            'project' => $project,
            'defaultFrom' => $defaultFrom,
            'defaultTo' => $defaultTo,
        ]);
    }

    /**
     * Exporta las entradas filtradas a CSV. La query
     * string replica los filtros del dashboard para
     * que el admin pueda descargar exactamente lo que
     * esta viendo.
     *
     * El CSV usa `;` como separador (estilo "CSV
     * espanol", compatible con Excel/LibreOffice en
     * locales europeos) y codificacion UTF-8 con BOM
     * para que los acentos se vean correctamente al
     * abrir.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request, Project $project): StreamedResponse
    {
        $this->authorize('view', $project);

        $filters = $this->resolveFilters($request);
        $summary = $this->timeTracking->getProjectSummary(
            $project,
            $filters['from'],
            $filters['to'],
            $filters['billable'],
        );

        $entries = $project->timeEntries()
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
            ->get();

        $filename = 'tiempo-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->slug).'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($entries, $summary): void {
            $output = fopen('php://output', 'w');
            // BOM para que Excel respete la codificacion UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Fecha', 'Tarea', 'Persona', 'Minutos', 'Horas', 'Tipo', 'Facturable', 'Descripcion'], ';');

            foreach ($entries as $entry) {
                fputcsv($output, [
                    $entry->created_at?->format('Y-m-d H:i') ?? '',
                    $entry->task?->title ?? '',
                    $entry->user?->name ?? '',
                    $entry->minutes,
                    number_format($entry->hours, 2, ',', '.'),
                    $entry->type?->label() ?? '',
                    $entry->isBillable() ? 'Si' : 'No',
                    $entry->description ?? '',
                ], ';');
            }

            // Linea en blanco + totales.
            fputcsv($output, [], ';');
            fputcsv($output, ['TOTAL', '', '', $summary['total_minutes'], number_format($summary['total_minutes'] / 60, 2, ',', '.'), '', '', ''], ';');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Resuelve los filtros de la query string a tipos
     * consistentes (Carbon, bool, int). Pensado para
     * reutilizar entre la vista del dashboard y la
     * exportacion CSV.
     *
     * @return array{from: ?Carbon, to: ?Carbon, billable: ?bool, user_id: ?int}
     */
    private function resolveFilters(Request $request): array
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $billable = $request->query('billable');
        $userId = $request->query('user_id');

        return [
            'from' => is_string($from) && $from !== '' ? Carbon::parse($from)->startOfDay() : null,
            'to' => is_string($to) && $to !== '' ? Carbon::parse($to)->endOfDay() : null,
            'billable' => match ($billable) {
                '1', 'true' => true,
                '0', 'false' => false,
                default => null,
            },
            'user_id' => is_string($userId) && $userId !== '' ? (int) $userId : null,
        ];
    }
}
