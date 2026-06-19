<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para editar el `system_prompt_override` de
 * una asignacion existente entre proyecto y template.
 *
 * Solo se expone este campo en edicion: el `agent_template_id`
 * no se puede cambiar una vez creada la asignacion (si el
 * admin quiere otro template, debe desasignar y volver a
 * asignar). Esto simplifica la UI y evita ambiguedad sobre
 * que template "original" mantiene el override.
 */
class UpdateProjectAgentRequest extends FormRequest
{
    /**
     * Solo el admin puede editar asignaciones.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
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
            'system_prompt_override.max' => 'El override es demasiado largo.',
        ];
    }
}
