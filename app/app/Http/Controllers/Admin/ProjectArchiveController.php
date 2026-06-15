<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
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
     * modifica la fecha original.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function archive(Project $project): RedirectResponse
    {
        $this->authorize('archive', $project);

        $project->archive();

        return back()->with('status', 'Proyecto archivado.');
    }

    /**
     * Desarchiva el proyecto.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unarchive(Project $project): RedirectResponse
    {
        $this->authorize('archive', $project);

        $project->unarchive();

        return back()->with('status', 'Proyecto desarchivado.');
    }
}
