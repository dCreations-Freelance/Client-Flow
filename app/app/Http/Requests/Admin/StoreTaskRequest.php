<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear una tarea en un proyecto. `parent_id` se
 * valida con `exists` y luego el controlador comprobara que
 * pertenezca al mismo proyecto. `assignee_id` debe ser miembro
 * del proyecto (no de la org necesariamente).
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In|\Illuminate\Validation\Rules\Exists>>
     */
    public function rules(): array
    {
        return [
            'column_id' => ['required', 'integer', Rule::exists('board_columns', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['required', Rule::in([
                TaskPriority::Critical->value,
                TaskPriority::High->value,
                TaskPriority::Medium->value,
                TaskPriority::Low->value,
            ])],
            'type' => ['required', Rule::in([
                TaskType::Feature->value,
                TaskType::Bug->value,
                TaskType::Improvement->value,
                TaskType::Task->value,
            ])],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'due_date' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Introduce un titulo para la tarea.',
            'priority.required' => 'Selecciona una prioridad.',
            'priority.in' => 'La prioridad seleccionada no es valida.',
            'type.required' => 'Selecciona un tipo.',
            'type.in' => 'El tipo seleccionado no es valido.',
            'column_id.exists' => 'La columna seleccionada no existe.',
            'parent_id.exists' => 'La tarea padre seleccionada no existe.',
            'assignee_id.exists' => 'El usuario asignado no existe.',
        ];
    }
}
