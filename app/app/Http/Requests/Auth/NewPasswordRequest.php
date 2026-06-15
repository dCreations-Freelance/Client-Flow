<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del formulario donde el usuario introduce la nueva contrasena
 * tras pulsar el enlace del email. El token se valida en el controlador
 * porque la regla `Password::resetToken` no esta expuesta en versiones
 * recientes de Laravel y preferimos gestion manual para tener control
 * sobre los mensajes.
 */
class NewPasswordRequest extends FormRequest
{
    /**
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
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
            'password.required' => 'Introduce una contrasena.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
        ];
    }
}
