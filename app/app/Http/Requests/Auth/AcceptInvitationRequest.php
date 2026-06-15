<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del formulario publico de aceptacion de invitacion.
 *
 * Se muestra cuando el usuario todavia no tiene cuenta. Tras aceptar,
 * se crea la cuenta con rol `client` y se le une a la organizacion.
 * En fases siguientes se podra reusar la misma vista con un campo de
 * password opcional si el usuario ya existe.
 */
class AcceptInvitationRequest extends FormRequest
{
    /**
     * Cualquier visitante con el token puede enviar este formulario.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Password>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce tu nombre.',
            'password.required' => 'Introduce una contrasena.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
        ];
    }
}
