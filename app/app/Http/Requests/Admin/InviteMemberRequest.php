<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationUserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para invitar a un miembro.
 *
 * Normaliza el email a minusculas para evitar duplicados por mayusculas
 * y restringe el rol a los valores del enum. `email` se valida con la
 * regla `lowercase` para que el check coincida con lo almacenado.
 */
class InviteMemberRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:160'],
            'role' => ['required', Rule::in([
                OrganizationUserRole::Owner->value,
                OrganizationUserRole::Member->value,
            ])],
        ];
    }

    /**
     * Normaliza el email a minusculas antes de validar para evitar que
     * `PERSONA@example.com` y `persona@example.com` se traten como
     * cuentas distintas al validar duplicados.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower((string) $this->input('email')),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Introduce un email.',
            'email.email' => 'El email no tiene un formato valido.',
            'role.required' => 'Selecciona un rol.',
            'role.in' => 'El rol seleccionado no es valido.',
        ];
    }
}
