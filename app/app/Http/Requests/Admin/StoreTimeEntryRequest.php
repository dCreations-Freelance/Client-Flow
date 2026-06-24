<?php

namespace App\Http\Requests\Admin;

use App\Enums\TimeEntryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear una entrada de tiempo manual
 * contra una tarea de un proyecto.
 *
 * - `description` es opcional: una entrada puede ser
 *   solo "30 minutos" sin contexto adicional.
 * - `minutes` es un entero positivo. El limite de
 *   60.000 (~1.000 horas) es una salvaguarda para
 *   detectar typos obvios sin limitar entradas reales.
 * - `type` se valida contra los valores del enum
 *   `TimeEntryType`. En la practica, desde el form de
 *   entrada manual siempre sera `manual`, pero el
 *   campo se acepta para mantener consistencia con
 *   el resto de entradas.
 * - `entry_date` es la fecha del trabajo. No puede ser
 *   futura (no tiene sentido registrar tiempo que aun
 *   no se ha dedicado).
 * - `billed` es opcional; por defecto las nuevas
 *   entradas no estan facturadas.
 */
class StoreTimeEntryRequest extends FormRequest
{
    /**
     * Solo el admin puede crear entradas. El cliente
     * queda fuera de toda la API de tiempo.
     *
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
            'type' => ['required', Rule::in([
                TimeEntryType::Manual->value,
                TimeEntryType::Timer->value,
            ])],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
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
            'type.required' => 'Indica el tipo de entrada.',
            'type.in' => 'El tipo de entrada no es valido.',
            'entry_date.required' => 'Indica la fecha del trabajo.',
            'entry_date.date' => 'La fecha no tiene un formato valido.',
            'entry_date.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}
