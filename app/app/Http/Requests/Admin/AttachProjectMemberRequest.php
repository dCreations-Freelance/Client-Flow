<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para anadir un miembro a un proyecto.
 *
 * El usuario ya debe pertenecer a la organizacion del proyecto: lo
 * verificamos en el controlador antes de asociar, para no
 * introducir accesos cruzados.
 */
class AttachProjectMemberRequest extends FormRequest
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
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Selecciona un miembro.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
        ];
    }
}
