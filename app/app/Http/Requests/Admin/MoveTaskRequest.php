<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para mover una tarea a otra columna/posicion. El
 * servicio `TaskMoveService` se encarga de recalcular posiciones
 * y de marcar/limpiar `completed_at` segun la columna destino.
 */
class MoveTaskRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Exists>>
     */
    public function rules(): array
    {
        return [
            'column_id' => ['required', 'integer', Rule::exists('board_columns', 'id')],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'column_id.required' => 'Selecciona la columna destino.',
            'column_id.exists' => 'La columna seleccionada no existe.',
            'position.required' => 'Indica la posicion destino.',
        ];
    }
}
