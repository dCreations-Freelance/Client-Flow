<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

/**
 * Vista del feed de actividad de un proyecto en el portal
 * cliente.
 *
 * Mismo componente compartido que el admin
 * (`Shared\ProjectActivityFeed`), pero montado con
 * `portalMode = true`. Esto aplica automaticamente el scope
 * `public` del modelo: el cliente solo ve los eventos marcados
 * como publicos en `ActivityType::isPublic()` + el filtro fino
 * de `properties.visibility` para documentos.
 *
 * La autorizacion delega en `ProjectPolicy::view` que ya
 * verifica que el cliente sea miembro de la org y que el
 * proyecto este visible y no archivado. Si no, devuelve 403.
 */
class ProjectActivityController extends Controller
{
    /**
     * Muestra el feed de actividad del proyecto en el portal.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Project $project): View
    {
        $this->authorize('view', $project);

        return view('portal.projects.activity', [
            'project' => $project,
        ]);
    }
}
