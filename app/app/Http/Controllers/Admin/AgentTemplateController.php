<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgentTemplateRequest;
use App\Http\Requests\Admin\UpdateAgentTemplateRequest;
use App\Models\AgentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD de templates de agentes IA en el panel admin.
 *
 * Los templates son la biblioteca que el admin exporta a sus
 * IDEs (Cursor, Claude Code, etc.) y, desde la fase transversal,
 * la fuente de la que se nutren las asignaciones a proyectos.
 *
 * Decisiones:
 * - El listado pagina con 15 elementos y conserva los filtros
 *   de la query string con `withQueryString()` para que las
 *   acciones (editar, eliminar) no pierdan el contexto.
 * - La lista de categorias para el filtro se calcula de la
 *   tabla con `distinct`, sin cache. Son pocos registros y
 *   el coste de la query es despreciable.
 * - El endpoint `export` devuelve JSON con cabeceras de
 *   descarga. Es admin-only y esta pensado para que el admin
 *   lo descargue y lo guarde en su maquina o en su IDE.
 */
class AgentTemplateController extends Controller
{
    /**
     * Listado paginado con busqueda y filtro por categoria.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AgentTemplate::class);

        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $templates = AgentTemplate::query()
            ->search($search !== '' ? $search : null)
            ->byCategory($category !== '' ? $category : null)
            ->with('creator:id,name')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = AgentTemplate::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.agent-templates.index', [
            'templates' => $templates,
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    /**
     * Muestra el formulario de creacion. La lista de categorias
     * se pasa al datalist del input para sugerir valores
     * consistentes sin obligar a elegir uno.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $this->authorize('create', AgentTemplate::class);

        $categories = AgentTemplate::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.agent-templates.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Persiste el template. `created_by` se rellena aqui (no
     * viene del cliente) para que el admin actual figure como
     * autor sin depender de manipulacion del request.
     *
     * @param  \App\Http\Requests\Admin\StoreAgentTemplateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAgentTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', AgentTemplate::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $template = AgentTemplate::create($data);

        return redirect()
            ->route('admin.agent-templates.show', $template)
            ->with('status', 'Template creado.');
    }

    /**
     * Detalle del template con la lista de proyectos donde
     * esta asignado. Se pagina para que un template popular
     * no produzca un scroll infinito en la vista.
     *
     * @param  \App\Models\AgentTemplate  $agentTemplate
     * @return \Illuminate\View\View
     */
    public function show(AgentTemplate $agentTemplate): View
    {
        $this->authorize('view', AgentTemplate::class);

        $agentTemplate->load('creator:id,name');

        $assignmentsCount = $agentTemplate->projects()->count();
        $projects = $agentTemplate->projects()
            ->orderBy('projects.name')
            ->paginate(15, ['*'], 'page_projects');

        return view('admin.agent-templates.show', [
            'template' => $agentTemplate,
            'projects' => $projects,
            'assignmentsCount' => $assignmentsCount,
        ]);
    }

    /**
     * Formulario de edicion. Reutiliza la misma lista de
     * categorias que en `create` para que el datalist
     * muestre lo mismo.
     *
     * @param  \App\Models\AgentTemplate  $agentTemplate
     * @return \Illuminate\View\View
     */
    public function edit(AgentTemplate $agentTemplate): View
    {
        $this->authorize('update', AgentTemplate::class);

        $categories = AgentTemplate::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.agent-templates.edit', [
            'template' => $agentTemplate,
            'categories' => $categories,
        ]);
    }

    /**
     * Actualiza el template. `created_by` no se sobreescribe
     * porque no esta en `validated()`.
     *
     * @param  \App\Http\Requests\Admin\UpdateAgentTemplateRequest  $request
     * @param  \App\Models\AgentTemplate  $agentTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAgentTemplateRequest $request, AgentTemplate $agentTemplate): RedirectResponse
    {
        $this->authorize('update', AgentTemplate::class);

        $agentTemplate->update($request->validated());

        return redirect()
            ->route('admin.agent-templates.show', $agentTemplate)
            ->with('status', 'Template actualizado.');
    }

    /**
     * Elimina el template. Las asignaciones a proyectos
     * caen en cascada por el FK de la migracion.
     *
     * @param  \App\Models\AgentTemplate  $agentTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AgentTemplate $agentTemplate): RedirectResponse
    {
        $this->authorize('delete', AgentTemplate::class);

        $agentTemplate->delete();

        return redirect()
            ->route('admin.agent-templates.index')
            ->with('status', 'Template eliminado.');
    }

    /**
     * Devuelve el template como JSON descargable. Pensado
     * para que el admin lo guarde y lo use en su IDE.
     *
     * @param  \App\Models\AgentTemplate  $agentTemplate
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function export(AgentTemplate $agentTemplate): Response
    {
        $this->authorize('view', AgentTemplate::class);

        $filename = 'agent-template-'.Str::slug((string) $agentTemplate->name).'.json';

        return response()->json(
            $agentTemplate->toExportArray(),
            200,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
