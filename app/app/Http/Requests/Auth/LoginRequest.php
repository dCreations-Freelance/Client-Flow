<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del formulario de inicio de sesion.
 *
 * Sigue la convencion de Laravel: incluye `email` y `password`, y permite
 * opcionalmente `remember` para mantener la sesion entre cierres de
 * navegador. La validacion de credenciales se hace en el controlador
 * con `Auth::attempt` para poder redirigir segun rol.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario esta autorizado a realizar esta peticion.
     * En la fase 1 cualquier visitante puede intentar autenticarse.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validacion. Se usa `Rule::exists` solo si se quiere
     * informar de emails no registrados; en este MVP se prefiere un
     * mensaje generico de credenciales invalidas.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensajes de error localizados en castellano.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Introduce tu email.',
            'email.email' => 'El email no tiene un formato valido.',
            'password.required' => 'Introduce tu contrasena.',
        ];
    }

    /**
     * Atributos legibles para los mensajes de error.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'email',
            'password' => 'contrasena',
        ];
    }
}
