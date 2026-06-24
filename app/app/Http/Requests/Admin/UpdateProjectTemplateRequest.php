<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para editar una plantilla existente.
 *
 * Mismos campos que `StoreProjectTemplateRequest`
 * pero la regla `unique` sobre `name` ignora la fila
 * actual (para permitir guardar sin cambiar el
 * nombre).
 */
class UpdateProjectTemplateRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var \App\Models\ProjectTemplate|null $template */
        $template = $this->route('project_template');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('project_templates', 'name')->ignore($template?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'min:2', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Indica un nombre para la plantilla.',
            'name.unique' => 'Ya existe otra plantilla con ese nombre.',
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('category')) {
            $category = $this->input('category');
            $category = is_string($category) ? trim($category) : $category;
            $this->merge(['category' => $category === '' ? null : $category]);
        }
    }
}
