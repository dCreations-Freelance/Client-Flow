<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear una columna en un proyecto. La columna se
 * crea al final (position automatica) y se marca como no-default.
 */
class StoreBoardColumnRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $projectId = (int) $this->route('project')->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'color' => ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{3}){1,2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Introduce un nombre para la columna.',
            'color.regex' => 'El color debe estar en formato hex (ej. #2563EB).',
        ];
    }
}
