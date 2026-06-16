<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Documentos visibles para clientes en el portal.
 *
 * El cliente solo puede listar y leer documentos con `visibility =
 * public` de proyectos a los que ya tiene acceso. La doble barrera
 * es: (1) middleware `client` que garantiza estar en el portal, (2)
 * `ProjectDocumentPolicy::view` que rechaza privados, (3) scope
 * `public()` aplicado aqui para nunca llegar a la policy con un
 * documento privado. Asi un cliente que conozca un ID de documento
 * privado no puede ni listarlo ni leerlo.
 */
class ProjectDocumentController extends Controller
{
    /**
     * Listado de documentos publicos del proyecto. Soporta busqueda
     * por titulo y contenido. La visibilidad nunca se filtra porque
     * solo se devuelven publicos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Project $project): View
    {
        // Reusamos la policy `view` sobre el proyecto (membresia +
        // visibilidad del proyecto) y el listado se construye ya
        // filtrado a publicos.
        $this->authorize('view', $project);

        $search = trim((string) $request->query('search', ''));

        $documents = $project->documents()
            ->public()
            ->with('creator')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->recent()
            ->paginate(15)
            ->withQueryString();

        return view('portal.projects.documents.index', [
            'project' => $project,
            'documents' => $documents,
            'search' => $search,
        ]);
    }

    /**
     * Detalle de un documento publico. La policy `view` del modelo
     * `ProjectDocument` cierra el paso si por algun motivo el scope
     * `public` se ha saltado.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument  $document
     * @return \Illuminate\View\View
     */
    public function show(Project $project, ProjectDocument $document): View
    {
        $this->authorize('view', $document);

        abort_unless($document->project_id === $project->id, 404);

        $document->load('creator');

        return view('portal.projects.documents.show', [
            'project' => $project,
            'document' => $document,
        ]);
    }
}
