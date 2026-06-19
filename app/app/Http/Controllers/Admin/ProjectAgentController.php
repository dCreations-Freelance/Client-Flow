<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignAgentTemplateRequest;
use App\Http\Requests\Admin\UpdateProjectAgentRequest;
use App\Models\AgentTemplate;
use App\Models\Project;
use App\Models\ProjectAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestion de asignaciones de templates de agentes IA a un
 * proyecto concreto.
 *
 * En MVP la gestion es 100% admin. El cliente del portal no
 * ve ni configura agentes. La policy lo bloquea; este
 * controlador lo respeta.
 *
 * Decisiones:
 * - El listado pagina con 15 elementos y cruza con `creator`
 *   del template para mostrar la trazabilidad del origen.
 * - Los templates disponibles para asignar se calculan
 *   excluyendo los ya asignados, asi el formulario siempre
 *   muestra solo opciones validas. Esto reduce la
 *   necesidad de un mensaje de error "ya esta asignado" a
 *   casos de doble submit.
 * - La validacion de unicidad se hace ademas en el Form
 *   Request (`Rule::unique` con `where project_id = ...`)
 *   para tener 422 limpios en doble submit.
 */
class ProjectAgentController extends Controller
{
    /**
     * Listado de asignaciones del proyecto y formulario de
     * alta con los templates aun no asignados.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Project $project): View
    {
        $this->authorize('viewAny', [ProjectAgent::class, $project]);

        $assignments = ProjectAgent::query()
            ->where('project_id', $project->id)
            ->with('template.creator:id,name')
            ->join('agent_templates', 'agent_templates.id', '=', 'project_agents.agent_template_id')
            ->orderBy('agent_templates.name')
            ->select('project_agents.*')
            ->paginate(15);

        $availableTemplates = AgentTemplate::query()
            ->whereNotIn('id', $project->agents()->pluck('agent_templates.id')->all() ?: [0])
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return view('admin.projects.agents.index', [
            'project' => $project,
            'assignments' => $assignments,
            'availableTemplates' => $availableTemplates,
        ]);
    }

    /**
     * Asigna un template al proyecto. El `project_id` se
     * toma del Route Model Binding, no del request, para
     * evitar manipulacion del cliente.
     *
     * @param  \App\Http\Requests\Admin\AssignAgentTemplateRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AssignAgentTemplateRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [ProjectAgent::class, $project]);

        ProjectAgent::create([
            'project_id' => $project->id,
            'agent_template_id' => $request->validated('agent_template_id'),
            'system_prompt_override' => $request->validated('system_prompt_override'),
        ]);

        return redirect()
            ->route('admin.projects.agents.index', $project)
            ->with('status', 'Agente asignado al proyecto.');
    }

    /**
     * Actualiza el `system_prompt_override` de una asignacion.
     * El `project_id` del URL se compara con el de la asignacion
     * como defensa en profundidad: si por lo que sea el route
     * model binding dejase pasar un id cruzado, el controlador
     * falla rapido con 404.
     *
     * @param  \App\Http\Requests\Admin\UpdateProjectAgentRequest  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectAgent  $agent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectAgentRequest $request, Project $project, ProjectAgent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);

        abort_unless($agent->project_id === $project->id, 404);

        $agent->update([
            'system_prompt_override' => $request->validated('system_prompt_override'),
        ]);

        return redirect()
            ->route('admin.projects.agents.index', $project)
            ->with('status', 'Override actualizado.');
    }

    /**
     * Desasigna el template del proyecto.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectAgent  $agent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project, ProjectAgent $agent): RedirectResponse
    {
        $this->authorize('delete', $agent);

        abort_unless($agent->project_id === $project->id, 404);

        $agent->delete();

        return redirect()
            ->route('admin.projects.agents.index', $project)
            ->with('status', 'Agente desasignado del proyecto.');
    }

    /**
     * Exporta la asignacion como JSON descargable. El
     * payload ya incluye el `system_prompt` efectivo
     * (override si lo hay, si no el del template) para que
     * el IDE destino reciba un archivo listo para usar.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\ProjectAgent  $agent
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function export(Project $project, ProjectAgent $agent): Response
    {
        $this->authorize('view', $agent);

        abort_unless($agent->project_id === $project->id, 404);

        $filename = 'agent-assignment-'.Str::slug($agent->template?->name ?? 'template').'.json';

        return response()->json(
            $agent->toExportArray(),
            200,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
