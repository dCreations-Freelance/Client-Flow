<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para crear / editar una columna de
 * una plantilla. Compartido por el store y el
 * update porque los campos son los mismos; la
 * policy se encarga del control de acceso.
 *
 * - `name` obligatorio, max 50 (las columnas del
 *   kanban tienen nombres cortos: "Por hacer",
 *   "En curso", etc.).
 * - `color` opcional en formato hex (#RRGGBB). La
 *   vista sugiere colores predefinidos; si el
 *   admin lo deja vacio se aplica `null` (la UI
 *   muestra un punto gris).
 * - `position` se calcula en el servicio si no se
 *   envia, para simplificar el formulario (el
 *   admin solo rellena nombre y color).
 */
class StoreProjectTemplateColumnRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Indica un nombre para la columna.',
            'color.regex' => 'El color debe tener formato hexadecimal (#RRGGBB).',
        ];
    }
}
