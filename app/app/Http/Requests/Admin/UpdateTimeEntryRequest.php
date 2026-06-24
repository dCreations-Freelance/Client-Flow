<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para editar una entrada de tiempo
 * existente.
 *
 * - `type` se omite a proposito: una entrada no
 *   puede cambiar de `manual` a `timer` o
 *   viceversa tras crearse. Si el admin se ha
 *   equivocado, debe borrar la entrada y crear
 *   una nueva. Asi se mantiene la integridad
 *   historica (un timer que ya esta cerrado
 *   tiene su `started_at` real).
 * - `entry_date` se omite: la fecha de trabajo
 *   tampoco se modifica. Si es necesario
 *   corregirla, se borra y se vuelve a crear.
 *
 * El resto de campos siguen las mismas reglas
 * que en Store.
 */
class UpdateTimeEntryRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:5000'],
            'minutes' => ['required', 'integer', 'min:1', 'max:60000'],
            'billed' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minutes.required' => 'Indica los minutos dedicados.',
            'minutes.integer' => 'Los minutos deben ser un numero entero.',
            'minutes.min' => 'Los minutos deben ser al menos 1.',
            'minutes.max' => 'Los minutos no pueden superar 60.000 (~1.000 horas).',
        ];
    }
}
