<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para editar una organizacion.
 *
 * Anade `status` al conjunto de campos y aplica la regla `Rule::in`
 * contra los valores del enum para evitar entradas invalidas.
 */
class UpdateOrganizationRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([
                OrganizationStatus::Active->value,
                OrganizationStatus::Inactive->value,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce un nombre para la organizacion.',
            'status.required' => 'Selecciona un estado.',
            'status.in' => 'El estado seleccionado no es valido.',
        ];
    }
}
