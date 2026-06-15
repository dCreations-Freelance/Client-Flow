<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion del formulario para solicitar un enlace de recuperacion de
 * contrasena. No se comprueba que el email exista para no filtrar que
 * cuentas estan registradas; el mensaje de exito es generico.
 */
class PasswordResetLinkRequest extends FormRequest
{
    /**
     * Cualquier visitante puede pedir el enlace.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Introduce tu email.',
            'email.email' => 'El email no tiene un formato valido.',
        ];
    }
}
