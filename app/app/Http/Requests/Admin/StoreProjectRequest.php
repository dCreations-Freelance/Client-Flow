<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del formulario de creacion de proyecto.
 *
 * El campo `cover_path` se omite en fase 2: la columna existe en BD
 * pero el upload se anadira cuando el modulo de storage este
 * maduro. Los demas campos siguen `docs/USER_FLOWS.md`.
 */
class StoreProjectRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In|\Illuminate\Validation\Rules\Exists>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([
                ProjectStatus::Planning->value,
                ProjectStatus::InProgress->value,
                ProjectStatus::OnHold->value,
                ProjectStatus::WaitingClient->value,
                ProjectStatus::Completed->value,
            ])],
            'starts_at' => ['nullable', 'date'],
            'estimated_ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_visible_to_client' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Normaliza el checkbox `is_visible_to_client` para que llegue como
     * booleano explicito aunque el cliente no lo envie (caso tipico
     * del checkbox desmarcado).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_visible_to_client' => $this->boolean('is_visible_to_client'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce un nombre para el proyecto.',
            'name.min' => 'El nombre es demasiado corto.',
            'name.max' => 'El nombre es demasiado largo.',
            'organization_id.required' => 'Selecciona una organizacion.',
            'organization_id.exists' => 'La organizacion seleccionada no existe.',
            'status.required' => 'Selecciona un estado.',
            'status.in' => 'El estado seleccionado no es valido.',
            'estimated_ends_at.after_or_equal' => 'La fecha estimada de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
