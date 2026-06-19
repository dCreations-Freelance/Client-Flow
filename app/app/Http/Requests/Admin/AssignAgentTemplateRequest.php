<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para asignar un template de agente IA a un
 * proyecto concreto.
 *
 * Reglas:
 * - `agent_template_id` debe existir en `agent_templates`.
 *   Tambien validamos con `Rule::unique` contra la pivot
 *   `project_agents` para devolver un 422 limpio si el
 *   template ya estaba asignado a este proyecto: asi el
 *   admin ve el error en el formulario en vez de un 500
 *   por violacion de constraint. El where filtra por el
 *   `project_id` actual, que se resuelve en el controlador
 *   y se pasa al validador via `Route::bind` + `route()`.
 * - `system_prompt_override` opcional: si esta vacio o
 *   no se envia, la asignacion usara el prompt del
 *   template. Si trae texto, ese texto manda.
 */
class AssignAgentTemplateRequest extends FormRequest
{
    /**
     * Solo el admin puede asignar templates a proyectos.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>>
     */
    public function rules(): array
    {
        $projectId = $this->route('project')?->id;

        return [
            'agent_template_id' => [
                'required',
                'integer',
                'exists:agent_templates,id',
                \Illuminate\Validation\Rule::unique('project_agents', 'agent_template_id')
                    ->where('project_id', $projectId),
            ],
            'system_prompt_override' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * Mensajes en castellano para los errores de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agent_template_id.required' => 'Selecciona un template.',
            'agent_template_id.exists' => 'El template seleccionado no existe.',
            'agent_template_id.unique' => 'Este template ya esta asignado a este proyecto.',
            'system_prompt_override.max' => 'El override es demasiado largo.',
        ];
    }
}
