<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para editar un template de agente IA.
 *
 * Mismas reglas que `StoreAgentTemplateRequest` pero todos
 * los campos pasan a `sometimes` (o `nullable` si ya lo
 * eran) para soportar PATCH/PUT parciales. La unica
 * excepcion es `system_prompt`, que se mantiene
 * `required` cuando viene en la peticion porque un
 * template sin system prompt no tiene sentido: si llega
 * vacio, es un error del admin.
 */
class UpdateAgentTemplateRequest extends FormRequest
{
    /**
     * Solo el admin puede editar templates.
     *
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
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'system_prompt' => ['sometimes', 'required', 'string', 'min:10', 'max:20000'],
            'tools' => ['sometimes', 'nullable', 'array'],
            'tools.*.name' => ['string', 'max:60'],
            'model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'category' => ['sometimes', 'nullable', 'string', 'max:60'],
        ];
    }

    /**
     * Mensajes en castellano para los errores de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.min' => 'El nombre es demasiado corto.',
            'name.max' => 'El nombre es demasiado largo.',
            'description.max' => 'La descripcion es demasiado larga.',
            'system_prompt.required' => 'Define el system prompt del agente.',
            'system_prompt.min' => 'El system prompt es demasiado corto.',
            'system_prompt.max' => 'El system prompt es demasiado largo.',
            'tools.array' => 'El campo tools debe ser un listado JSON.',
            'tools.*.name.string' => 'Cada herramienta debe tener un nombre.',
            'tools.*.name.max' => 'El nombre de la herramienta es demasiado largo.',
            'model.max' => 'El nombre del modelo es demasiado largo.',
            'category.max' => 'La categoria es demasiado larga.',
        ];
    }
}
