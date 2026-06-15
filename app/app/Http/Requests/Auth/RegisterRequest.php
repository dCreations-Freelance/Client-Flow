<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del registro publico.
 *
 * Solo permite crear cuentas con rol `client`. El registro de administradores
 * se hace por tinker, seeder o comando artisan, nunca por formulario web
 * abierto, para evitar que cualquier visitante se proclame admin.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Cualquier visitante puede intentar registrarse.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validacion. `email` se valida como unico en la tabla
     * `users` para evitar duplicados.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * Mensajes localizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce tu nombre.',
            'name.max' => 'El nombre es demasiado largo.',
            'email.required' => 'Introduce tu email.',
            'email.email' => 'El email no tiene un formato valido.',
            'email.unique' => 'Ya existe una cuenta con este email.',
            'password.required' => 'Introduce una contrasena.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
        ];
    }

    /**
     * Crea el usuario asignando siempre el rol `client`. Se aplica de forma
     * centralizada para que sea imposible (incluso desde el codigo) crear
     * un admin por esta via.
     *
     * @return User
     */
    public function createUser(): User
    {
        $data = $this->validated();
        $data['role'] = UserRole::Client;

        return User::create($data);
    }
}
