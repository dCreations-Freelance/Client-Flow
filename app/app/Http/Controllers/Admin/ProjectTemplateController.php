<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectTemplateColumnRequest;
use App\Http\Requests\Admin\StoreProjectTemplateDocumentRequest;
use App\Http\Requests\Admin\StoreProjectTemplateRequest;
use App\Http\Requests\Admin\StoreProjectTemplateTaskRequest;
use App\Http\Requests\Admin\UpdateProjectTemplateRequest;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateColumn;
use App\Models\ProjectTemplateDocument;
use App\Models\ProjectTemplateTask;
use App\Services\ProjectTemplate\ProjectTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD de plantillas de proyecto en el panel
 * admin. La biblioteca de plantillas es una
 * herramienta interna del freelancer / agencia:
 * el cliente no tiene acceso a ninguna de estas
 * rutas (middleware `admin` + policy
 * `ProjectTemplatePolicy`).
 *
 * Ademas del CRUD basico, este controlador
 * expone:
 * - Las acciones anidadas para crear / editar /
 *   eliminar columnas, tareas y documentos
 *   predefinidos (viven en el mismo controlador
 *   para evitar proliferacion de controladores
 *   cuando las acciones son pequenas).
 * - El endpoint de "vista previa" usado por la
 *   vista `show` y el componente Livewire
 *   `TemplateEditor`.
 */
class ProjectTemplateController extends Controller
{
    public function __construct(
        private ProjectTemplateService $service,
    ) {
    }

    // -----------------------------------------------------------------
    // CRUD principal
    // -----------------------------------------------------------------

    /**
     * Listado paginado con busqueda por texto y
     * filtro por categoria (chip). Los conteos de
     * columnas/tareas/documentos se cargan con
     * `withCount` para alimentar las tarjetas del
     * card.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProjectTemplate::class);

        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $templates = $this->service
            ->queryWithFilters($search, $category)
            ->paginate(15)
            ->withQueryString();

        $categories = $this->service->categories();

        return view('admin.project-templates.index', [
            'templates' => $templates,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $category,
        ]);
    }

    /**
     * Form de creacion. Solo nombre, descripcion y
     * categoria; el resto se rellena despues desde
     * el editor.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $this->authorize('create', ProjectTemplate::class);

        return view('admin.project-templates.create', [
            'template' => new ProjectTemplate(),
        ]);
    }

    /**
     * Crea la plantilla con los metadatos basicos
     * y redirige al editor para que el admin anada
     * columnas, tareas y documentos. Decision de
     * UX: crear primero y editar despues, en vez
     * de un wizard multi-paso, porque la
     * "primera fila" del editor necesita un id
     * valido para las acciones anidadas.
     *
     * @return RedirectResponse
     */
    public function store(StoreProjectTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', ProjectTemplate::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $template = ProjectTemplate::create($data);

        return redirect()
            ->route('admin.project-templates.edit', $template)
            ->with('status', 'Plantilla creada. Anade columnas, tareas y documentos.');
    }

    /**
     * Detalle / preview readonly de la plantilla.
     * El admin usa este endpoint para revisar el
     * contenido antes de aplicarla a un proyecto.
     *
     * @return \Illuminate\View\View
     */
    public function show(ProjectTemplate $projectTemplate): View
    {
        $this->authorize('view', $projectTemplate);

        $projectTemplate->load(['columns', 'tasks', 'documents', 'creator']);

        return view('admin.project-templates.show', [
            'template' => $projectTemplate,
        ]);
    }

    /**
     * Form de edicion de metadatos. Las columnas,
     * tareas y documentos se gestionan via las
     * acciones anidadas (mismo formulario, otras
     * secciones).
     *
     * @return \Illuminate\View\View
     */
    public function edit(ProjectTemplate $projectTemplate): View
    {
        $this->authorize('update', $projectTemplate);

        $projectTemplate->load(['columns', 'tasks', 'documents']);

        return view('admin.project-templates.edit', [
            'template' => $projectTemplate,
        ]);
    }

    /**
     * Actualiza los metadatos de la plantilla.
     * Las columnas / tareas / documentos se
     * actualizan por separado (acciones anidadas).
     *
     * @return RedirectResponse
     */
    public function update(UpdateProjectTemplateRequest $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);

        $projectTemplate->update($request->validated());

        return redirect()
            ->route('admin.project-templates.edit', $projectTemplate)
            ->with('status', 'Metadatos actualizados.');
    }

    /**
     * Elimina la plantilla. Las columnas, tareas y
     * documentos se eliminan en cascada por las FK
     * (`cascadeOnDelete`).
     *
     * @return RedirectResponse
     */
    public function destroy(ProjectTemplate $projectTemplate): RedirectResponse
    {
        $this->authorize('delete', $projectTemplate);

        $projectTemplate->delete();

        return redirect()
            ->route('admin.project-templates.index')
            ->with('status', 'Plantilla eliminada.');
    }

    // -----------------------------------------------------------------
    // CRUD anidado: columnas
    // -----------------------------------------------------------------

    /**
     * Anade una columna a la plantilla. La
     * `position` se calcula automaticamente al
     * final (helper del servicio).
     *
     * @return RedirectResponse
     */
    public function storeColumn(StoreProjectTemplateColumnRequest $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);

        $data = $request->validated();
        $data['template_id'] = $projectTemplate->id;
        $data['position'] = $this->service->nextColumnPosition($projectTemplate);

        ProjectTemplateColumn::create($data);

        return back()->with('status', 'Columna anadida.');
    }

    /**
     * Actualiza una columna de la plantilla. La
     * `position` no se cambia desde aqui (seria un
     * reorder, fuera de scope del MVP).
     *
     * @return RedirectResponse
     */
    public function updateColumn(StoreProjectTemplateColumnRequest $request, ProjectTemplate $projectTemplate, ProjectTemplateColumn $column): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);
        $this->ensureColumnBelongsToTemplate($projectTemplate, $column);

        $column->update($request->validated());

        return back()->with('status', 'Columna actualizada.');
    }

    /**
     * Elimina una columna de la plantilla.
     *
     * @return RedirectResponse
     */
    public function destroyColumn(ProjectTemplate $projectTemplate, ProjectTemplateColumn $column): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);
        $this->ensureColumnBelongsToTemplate($projectTemplate, $column);

        $column->delete();

        return back()->with('status', 'Columna eliminada.');
    }

    // -----------------------------------------------------------------
    // CRUD anidado: tareas predefinidas
    // -----------------------------------------------------------------

    /**
     * Anade una tarea predefinida. `column_position`
     * viene del form (seleccion de la columna
     * destino). `position` se calcula al final de
     * esa columna.
     *
     * @return RedirectResponse
     */
    public function storeTask(StoreProjectTemplateTaskRequest $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);

        $data = $request->validated();
        $data['template_id'] = $projectTemplate->id;
        $data['position'] = $this->service->nextTaskPosition(
            $projectTemplate,
            (int) $data['column_position'],
        );

        ProjectTemplateTask::create($data);

        return back()->with('status', 'Tarea anadida.');
    }

    /**
     * Actualiza una tarea predefinida. Permite
     * cambiar `column_position` (mover entre
     * columnas); en ese caso la `position` se
     * recalcula al final de la nueva columna.
     *
     * @return RedirectResponse
     */
    public function updateTask(StoreProjectTemplateTaskRequest $request, ProjectTemplate $projectTemplate, ProjectTemplateTask $task): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);
        $this->ensureTaskBelongsToTemplate($projectTemplate, $task);

        $data = $request->validated();
        $newColumn = (int) $data['column_position'];
        $oldColumn = (int) $task->column_position;
        if ($newColumn !== $oldColumn) {
            $data['position'] = $this->service->nextTaskPosition($projectTemplate, $newColumn);
        }
        $task->update($data);

        return back()->with('status', 'Tarea actualizada.');
    }

    /**
     * Elimina una tarea predefinida.
     *
     * @return RedirectResponse
     */
    public function destroyTask(ProjectTemplate $projectTemplate, ProjectTemplateTask $task): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);
        $this->ensureTaskBelongsToTemplate($projectTemplate, $task);

        $task->delete();

        return back()->with('status', 'Tarea eliminada.');
    }

    // -----------------------------------------------------------------
    // CRUD anidado: documentos esqueleto
    // -----------------------------------------------------------------

    /**
     * Anade un documento esqueleto. La `position`
     * se calcula al final.
     *
     * @return RedirectResponse
     */
    public function storeDocument(StoreProjectTemplateDocumentRequest $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);

        $data = $request->validated();
        $data['template_id'] = $projectTemplate->id;
        $data['position'] = $this->service->nextDocumentPosition($projectTemplate);

        ProjectTemplateDocument::create($data);

        return back()->with('status', 'Documento anadido.');
    }

    /**
     * Actualiza un documento esqueleto.
     *
     * @return RedirectResponse
     */
    public function updateDocument(StoreProjectTemplateDocumentRequest $request, ProjectTemplate $projectTemplate, ProjectTemplateDocument $document): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);
        $this->ensureDocumentBelongsToTemplate($projectTemplate, $document);

        $document->update($request->validated());

        return back()->with('status', 'Documento actualizado.');
    }

    /**
     * Elimina un documento esqueleto.
     *
     * @return RedirectResponse
     */
    public function destroyDocument(ProjectTemplate $projectTemplate, ProjectTemplateDocument $document): RedirectResponse
    {
        $this->authorize('update', $projectTemplate);
        $this->ensureDocumentBelongsToTemplate($projectTemplate, $document);

        $document->delete();

        return back()->with('status', 'Documento eliminado.');
    }

    // -----------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------

    /**
     * Verifica que la columna pertenece a la
     * plantilla de la URL. Cierra el vector "admin
     * manipula la URL con un id ajeno".
     *
     * @return void
     */
    private function ensureColumnBelongsToTemplate(ProjectTemplate $template, ProjectTemplateColumn $column): void
    {
        if ((int) $column->template_id !== (int) $template->id) {
            abort(404);
        }
    }

    /**
     * @return void
     */
    private function ensureTaskBelongsToTemplate(ProjectTemplate $template, ProjectTemplateTask $task): void
    {
        if ((int) $task->template_id !== (int) $template->id) {
            abort(404);
        }
    }

    /**
     * @return void
     */
    private function ensureDocumentBelongsToTemplate(ProjectTemplate $template, ProjectTemplateDocument $document): void
    {
        if ((int) $document->template_id !== (int) $template->id) {
            abort(404);
        }
    }
}
