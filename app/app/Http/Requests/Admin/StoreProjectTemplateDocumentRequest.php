<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear / editar un documento
 * esqueleto de una plantilla.
 *
 * - `visibility` se valida contra el enum
 *   `DocumentVisibility` (mismos valores que en
 *   los documentos reales: `private` o `public`).
 * - `content` es `longtext` en BD; aqui se valida
 *   como string con un max muy alto para admitir
 *   documentos extensos.
 */
class StoreProjectTemplateDocumentRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'content' => ['nullable', 'string', 'max:200000'],
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
            'title.required' => 'Indica un titulo para el documento.',
            'visibility.required' => 'Selecciona la visibilidad del documento.',
            'visibility.in' => 'La visibilidad seleccionada no es valida.',
        ];
    }

    /**
     * Normaliza el contenido vacio a `null` para
     * que no se guarde como string vacio en BD.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $content = $this->input('content');
            $content = is_string($content) ? trim($content) : $content;
            $this->merge(['content' => $content === '' ? null : $content]);
        }
    }
}
