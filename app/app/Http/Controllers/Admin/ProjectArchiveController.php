<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;

/**
 * Acciones dedicadas de archivado y desarchivado.
 *
 * Separar estas acciones del CRUD principal permite usar formularios
 * pequenos y de un solo boton desde la vista show, sin obligar a
 * pasar por el formulario completo de edicion.
 */
class ProjectArchiveController extends Controller
{
    /**
     * Archiva el proyecto. Idempotente: archivar dos veces no
     * modifica la fecha original. Ademas registra un mensaje
     * automatico en el chat del proyecto para que los participantes
     * tengan constancia del cambio.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function archive(Project $project): RedirectResponse
    {
        $this->authorize('archive', $project);

        $project->archive();

        app(ActivityLogger::class)->projectArchived($project, request()->user());

        return back()->with('status', 'Proyecto archivado.');
    }

    /**
     * Desarchiva el proyecto. Tambien registra un mensaje automatico
     * en el chat, aunque menos relevante (el cliente ya no veia el
     * proyecto archivado).
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unarchive(Project $project): RedirectResponse
    {
        $this->authorize('archive', $project);

        $project->unarchive();

        app(ActivityLogger::class)->projectUnarchived($project, request()->user());

        return back()->with('status', 'Proyecto desarchivado.');
    }
}
