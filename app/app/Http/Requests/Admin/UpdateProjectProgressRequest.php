<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion del endpoint rapido de actualizacion de progreso.
 *
 * Pensado para peticiones pequenas (Livewire / AJAX) que actualizan
 * solo el campo `progress` desde la vista show. Mantener un
 * Form Request dedicado evita arrastrar validaciones del form
 * completo y limita el payload.
 */
class UpdateProjectProgressRequest extends FormRequest
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
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'progress.required' => 'Indica el progreso del proyecto.',
            'progress.min' => 'El progreso no puede ser negativo.',
            'progress.max' => 'El progreso maximo es 100.',
        ];
    }
}
