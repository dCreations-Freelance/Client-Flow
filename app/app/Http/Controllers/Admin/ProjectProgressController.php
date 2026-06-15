<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProjectProgressRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

/**
 * Endpoint rapido para actualizar solo el progreso del proyecto.
 *
 * Pensado para llamadas puntuales (Livewire o AJAX) que vienen del
 * panel inline de la vista show. No devuelve JSON porque la version
 * minima del portal sigue funcionando con redirects y flashes.
 */
class ProjectProgressController extends Controller
{
    /**
     * Actualiza el campo `progress` dentro del rango 0-100.
     *
     * @param  \App\Http\Requests\Admin\UpdateProjectProgressRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectProgressRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update([
            'progress' => (int) $request->integer('progress'),
        ]);

        return back()->with('status', 'Progreso actualizado.');
    }
}
