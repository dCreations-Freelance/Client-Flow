<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vista de resumen de tiempo de un proyecto en el
 * portal del cliente.
 *
 * El portal es de solo lectura: el cliente nunca
 * puede crear, editar ni eliminar entradas. Ademas,
 * la vista solo muestra totales agregados (total
 * horas del proyecto + breakdown por miembro), sin
 * descripciones individuales ni desglose por tarea,
 * para mantener la privacidad de la informacion
 * interna del equipo.
 */
class ProjectTimeController extends Controller
{
    /**
     * Renderiza la vista de resumen. El componente
     * Livewire `ProjectTimeSummary` se encarga de
     * cargar los agregados y aplicar los filtros.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        return view('portal.projects.time.index', [
            'project' => $project,
        ]);
    }
}
