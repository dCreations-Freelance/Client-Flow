<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para editar un documento existente.
 *
 * Mismas reglas que `StoreProjectDocumentRequest` para mantener el
 * formulario consistente entre crear y editar.
 */
class UpdateProjectDocumentRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'content' => ['required', 'string', 'min:1', 'max:100000'],
            'visibility' => ['required', Rule::in([
                DocumentVisibility::Private->value,
                DocumentVisibility::Public->value,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Introduce un titulo para el documento.',
            'title.min' => 'El titulo es demasiado corto.',
            'title.max' => 'El titulo es demasiado largo.',
            'content.required' => 'El contenido del documento no puede estar vacio.',
            'content.max' => 'El documento excede el tamano maximo permitido.',
            'visibility.required' => 'Selecciona una visibilidad para el documento.',
            'visibility.in' => 'La visibilidad seleccionada no es valida.',
        ];
    }

    /**
     * Datos saneados listos para `update()`.
     *
     * @return array<string, mixed>
     */
    public function documentData(): array
    {
        return [
            'title' => trim($this->validated('title')),
            'content' => $this->validated('content'),
            'visibility' => $this->validated('visibility'),
        ];
    }
}
