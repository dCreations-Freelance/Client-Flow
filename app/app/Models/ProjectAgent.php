<?php

namespace App\Models;

use Database\Factories\ProjectAgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asignacion de un template de agente IA a un proyecto concreto.
 *
 * Es la pivot `project_agents` modelada como entidad propia
 * porque necesitamos editar `system_prompt_override` y, en
 * fases futuras, anadir mas columnas (e.g. `is_active`,
 * `assigned_by`, `display_order`). El modelo `Project` y
 * `AgentTemplate` exponen su relacion con `belongsToMany` para
 * el caso de uso de "leer todas las asignaciones"; este modelo
 * cubre el caso "leer y mutar una asignacion concreta".
 *
 * El nombre del modelo es singular (`ProjectAgent`) siguiendo
 * la convencion de la pivot `McpMessage` y otras ya presentes
 * en el proyecto: tabla = singular, modelo = singular.
 */
class ProjectAgent extends Model
{
    /** @use HasFactory<ProjectAgentFactory> */
    use HasFactory;

    /**
     * Atributos asignables. `project_id` y `agent_template_id`
     * se rellenan en el controlador y se protegen con la
     * validacion de `AssignAgentTemplateRequest`.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'agent_template_id',
        'system_prompt_override',
    ];

    /**
     * Proyecto al que pertenece la asignacion.
     *
     * @return BelongsTo<Project, ProjectAgent>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Template asignado. Se nombra `template` y no
     * `agentTemplate` para que la lectura del codigo sea
     * natural en el IDE destino.
     *
     * @return BelongsTo<AgentTemplate, ProjectAgent>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentTemplate::class, 'agent_template_id');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * System prompt efectivo que el IDE debe usar.
     *
     * Prioridad:
     * 1. `system_prompt_override` no vacio (texto custom para
     *    este proyecto).
     * 2. `system_prompt` del template.
     *
     * Si el template fue borrado en cascada, la relacion
     * `template` devolvera `null` y se devuelve el propio
     * override (que puede ser null) para que el caller decida
     * como tratar el caso en vez de recibir un null
     * ambiguo.
     */
    public function effectiveSystemPrompt(): ?string
    {
        $override = $this->system_prompt_override;

        if (is_string($override) && trim($override) !== '') {
            return $override;
        }

        return $this->template?->system_prompt;
    }

    /**
     * Serializa la asignacion al formato JSON que consumen los
     * endpoints de export. Incluye los datos del template
     * (que son los mismos que el export de template) pero con
     * el `system_prompt` ya sustituido por el override si lo
     * hay. Asi el IDE destino recibe un unico archivo listo
     * para usar.
     *
     * @return array<string, mixed>
     */
    public function toExportArray(): array
    {
        $template = $this->template;

        return [
            'name' => $template?->name,
            'description' => $template?->description,
            'system_prompt' => $this->effectiveSystemPrompt(),
            'tools' => $template?->tools,
            'model' => $template?->model,
            'category' => $template?->category,
        ];
    }
}
