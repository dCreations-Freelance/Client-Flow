<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectDocumentRequest;
use App\Http\Requests\Admin\UpdateProjectDocumentRequest;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD de documentos de proyecto en el panel admin.
 *
 * El admin ve todos los documentos del proyecto (privados y
 * publicos) y puede filtrar por visibilidad y buscar por texto. La
 * autorizacion delega en `ProjectDocumentPolicy`, mientras que la
 * policy de proyecto (`ProjectPolicy::view`) garantiza que el
 * proyecto existe.
 */
class ProjectDocumentController extends Controller
{
    /**
     * Listado paginado con busqueda por titulo/contenido y filtro de
     * visibilidad. Por defecto se ordenan los mas recientes primero.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('viewAny', [ProjectDocument::class, $project]);

        $search = trim((string) $request->query('search', ''));
        $visibility = (string) $request->query('visibility', '');

        $documents = $project->documents()
            ->with('creator')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($visibility !== '', function ($q) use ($visibility): void {
                if ($visibility === DocumentVisibility::Private->value) {
                    $q->private();
                } elseif ($visibility === DocumentVisibility::Public->value) {
                    $q->public();
                }
            })
            ->recent()
            ->paginate(15)
            ->withQueryString();

        return view('admin.projects.documents.index', [
            'project' => $project,
            'documents' => $documents,
            'search' => $search,
            'visibility' => $visibility,
        ]);
    }

    /**
     * Muestra el formulario de creacion. La vista incluye el
     * componente Livewire `DocumentEditor` en modo "create".
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function create(Project $project): View
    {
        $this->authorize('create', [ProjectDocument::class, $project]);

        return view('admin.projects.documents.create', [
            'project' => $project,
        ]);
    }

    /**
     * Persiste un documento nuevo. La autoria se asigna al admin
     * actual para mantener la trazabilidad.
     *
     * @param  \App\Http\Requests\Admin\StoreProjectDocumentRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreProjectDocumentRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [ProjectDocument::class, $project]);

        $data = $request->documentData();
        $data['project_id'] = $project->id;
        $data['created_by'] = $request->user()->id;

        $document = ProjectDocument::create($data);

        return redirect()
            ->route('admin.projects.documents.show', [$project, $document])
            ->with('status', 'Documento creado.');
    }

    /**
     * Vista de lectura del documento: contenido markdown ya
     * renderizado. Util cuando el admin quiere revisar como queda
     * publicado sin entrar al editor.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument  $document
     * @return \Illuminate\View\View
     */
    public function show(Project $project, ProjectDocument $document): View
    {
        $this->authorize('view', $document);

        // La policy ya garantiza que el documento es accesible; aun
        // asi verificamos la integridad de la relacion por si la URL
        // llega manipulada.
        abort_unless($document->project_id === $project->id, 404);

        $document->load('creator');

        return view('admin.projects.documents.show', [
            'project' => $project,
            'document' => $document,
        ]);
    }

    /**
     * Muestra el formulario de edicion con el editor Livewire en
     * modo "edit".
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument  $document
     * @return \Illuminate\View\View
     */
    public function edit(Project $project, ProjectDocument $document): View
    {
        $this->authorize('update', $document);

        abort_unless($document->project_id === $project->id, 404);

        return view('admin.projects.documents.edit', [
            'project' => $project,
            'document' => $document,
        ]);
    }

    /**
     * Actualiza un documento. La autoria (`created_by`) no se
     * modifica: representa quien lo creo, no quien lo edita por
     * ultima vez.
     *
     * @param  \App\Http\Requests\Admin\UpdateProjectDocumentRequest  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument  $document
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectDocumentRequest $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->authorize('update', $document);

        abort_unless($document->project_id === $project->id, 404);

        $document->update($request->documentData());

        return redirect()
            ->route('admin.projects.documents.show', [$project, $document])
            ->with('status', 'Documento actualizado.');
    }

    /**
     * Elimina un documento. La accion es destructiva y se muestra
     * con confirmacion JS en la vista.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectDocument  $document
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        abort_unless($document->project_id === $project->id, 404);

        $document->delete();

        return redirect()
            ->route('admin.projects.documents.index', $project)
            ->with('status', 'Documento eliminado.');
    }
}
