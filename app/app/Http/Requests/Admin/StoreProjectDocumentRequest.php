<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion para crear un documento de proyecto.
 *
 * `title` y `content` son obligatorios. La visibilidad por defecto
 * sera `private` y se aplica en el modelo al persistir; aqui la
 * aceptamos para que el admin pueda publicar desde el primer
 * momento si quiere.
 */
class StoreProjectDocumentRequest extends FormRequest
{
    /**
     * La autorizacion real la gestiona `ProjectDocumentPolicy::create`
     * en el controlador; aqui hacemos una pre-chequeo de rol para
     * defensa en profundidad.
     *
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
     * Datos saneados listos para `create()`. Centraliza la conversion
     * del enum a su valor string para que el modelo lo persista
     * correctamente sin necesidad de conversiones adicionales.
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
