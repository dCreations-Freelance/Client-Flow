<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del formulario de edicion de proyecto. Anade `progress`
 * (0-100) y la posibilidad de cambiar el `status` a `archived` para
 * soportar el archivado desde el propio formulario.
 */
class UpdateProjectRequest extends FormRequest
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
                ProjectStatus::Archived->value,
            ])],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'estimated_ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_visible_to_client' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_visible_to_client' => $this->boolean('is_visible_to_client'),
            'progress' => (int) $this->input('progress', 0),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce un nombre para el proyecto.',
            'progress.required' => 'Indica el progreso del proyecto.',
            'progress.min' => 'El progreso no puede ser negativo.',
            'progress.max' => 'El progreso maximo es 100.',
            'status.in' => 'El estado seleccionado no es valido.',
            'estimated_ends_at.after_or_equal' => 'La fecha estimada de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
