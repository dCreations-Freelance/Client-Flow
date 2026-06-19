<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para crear un template de agente IA.
 *
 * Reglas:
 * - `name` obligatorio, longitud acotada para encajar en
 *   listados y badges.
 * - `description` opcional pero con limite para evitar
 *   descripciones absurdas.
 * - `system_prompt` obligatorio y de tamano realista: el
 *   minimo evita que el admin guarde placeholders tipo "x"
 *   y el maximo protege contra subidas accidentales de
 *   documentos completos.
 * - `tools` opcional: si llega, validamos solo la forma
 *   basica (array de elementos con `name`). El esquema
 *   concreto de cada tool (parametros, tipos) lo define
 *   el IDE destino; aqui no se valida para no atar el
 *   formato a un estandar.
 * - `model` opcional: pista para el IDE destino, sin
 *   validacion contra catalogo.
 * - `category` opcional: string libre de tamano acotado
 *   para mantenerlo manejable en listados y badges.
 */
class StoreAgentTemplateRequest extends FormRequest
{
    /**
     * Solo el admin puede dar de alta templates.
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
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'system_prompt' => ['required', 'string', 'min:10', 'max:20000'],
            'tools' => ['nullable', 'array'],
            'tools.*.name' => ['string', 'max:60'],
            'model' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
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
            'name.required' => 'Introduce un nombre para el template.',
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
