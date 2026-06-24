<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear una plantilla de proyecto.
 *
 * - `name` es obligatorio y unico. El slug se
 *   genera a partir del nombre en el observer del
 *   modelo.
 * - `description` opcional como texto largo
 *   (markdown). Limite amplio para que el admin
 *   pueda documentar la plantilla con detalle.
 * - `category` opcional y de texto libre: si se
 *   da, se normaliza a `trim`. La longitud maxima
 *   evita categorias absurdamente largas.
 * - `created_by` se asigna en el controlador (con
 *   el usuario autenticado), no desde el form.
 */
class StoreProjectTemplateRequest extends FormRequest
{
    /**
     * Solo el admin puede crear plantillas. El
     * cliente queda fuera de toda la biblioteca.
     *
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
        return [
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('project_templates', 'name')],
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
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar 100 caracteres.',
            'name.unique' => 'Ya existe una plantilla con ese nombre.',
            'description.max' => 'La descripcion no puede superar 5000 caracteres.',
            'category.max' => 'La categoria no puede superar 50 caracteres.',
        ];
    }

    /**
     * Normaliza la categoria antes de validar: la
     * convierte en `null` si queda vacia tras el
     * trim, para que no se guarde como string
     * vacio en BD.
     *
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
