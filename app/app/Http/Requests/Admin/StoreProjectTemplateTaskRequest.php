<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear / editar una tarea
 * predefinida de una plantilla.
 *
 * - `column_position` identifica la columna destino
 *   por su `position` en la plantilla (no por id).
 *   Asi la plantilla es resistente a renombrados.
 * - `type` y `priority` se validan contra los
 *   enums del proyecto (mismos valores que en las
 *   tareas reales).
 * - `position` se calcula en el servicio si no se
 *   envia, para simplificar el formulario.
 */
class StoreProjectTemplateTaskRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'column_position' => ['required', 'integer', 'min:0', 'max:1000'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in([
                TaskType::Feature->value,
                TaskType::Bug->value,
                TaskType::Improvement->value,
                TaskType::Task->value,
            ])],
            'priority' => ['required', Rule::in([
                TaskPriority::Critical->value,
                TaskPriority::High->value,
                TaskPriority::Medium->value,
                TaskPriority::Low->value,
            ])],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'column_position.required' => 'Indica la columna destino.',
            'title.required' => 'Indica un titulo para la tarea.',
            'type.required' => 'Selecciona un tipo de tarea.',
            'priority.required' => 'Selecciona una prioridad.',
        ];
    }
}
