<?php

namespace App\Http\Requests\Admin;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para crear una organizacion.
 *
 * `name` es obligatorio y se trimea. `description` opcional. La
 * generacion del slug es automatica en el modelo, por lo que no se
 * pide en el formulario: evita errores y mantiene la URL limpia.
 */
class StoreOrganizationRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce un nombre para la organizacion.',
            'name.min' => 'El nombre es demasiado corto.',
            'name.max' => 'El nombre es demasiado largo.',
            'description.max' => 'La descripcion es demasiado larga.',
        ];
    }
}
