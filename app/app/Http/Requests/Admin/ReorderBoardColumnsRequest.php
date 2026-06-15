<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para reordenar columnas. Recibe un array `columns`
 * con la lista ordenada de IDs. El controlador actualizara las
 * posiciones a 0..N-1 segun el orden recibido.
 *
 * @return array<string, array<int, string>>
 */
class ReorderBoardColumnsRequest extends FormRequest
{
    /**
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
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'columns.required' => 'Debes enviar el orden de las columnas.',
        ];
    }
}
