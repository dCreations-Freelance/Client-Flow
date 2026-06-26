<?php

namespace App\Http\Requests\Admin;

use App\Enums\AiProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del formulario de configuracion de la IA en
 * el panel admin (`/admin/configuracion/ia`).
 *
 * Cubre dos modos:
 * - Configuracion global: `project_id` vacio o `null`. Es
 *   la fila fallback que se usa cuando un proyecto no
 *   tiene la suya.
 * - Configuracion por proyecto: `project_id` con un id
 *   valido. Permite sobreescribir provider, API key y
 *   limites solo para ese proyecto.
 *
 * Reglas:
 * - `provider` debe ser uno de los del enum `AiProvider`.
 * - `api_key` requerida y con un tamano minimo razonable.
 *   En modo edicion se puede omitir para conservar la
 *   clave anterior (eso lo gestiona el controlador).
 * - `model` opcional: si esta vacio, `AiConfig::effectiveModel()`
 *   usa el default del provider.
 * - Los limites se validan como enteros positivos acotados.
 */
class UpdateAiConfigRequest extends FormRequest
{
    /**
     * Solo el admin puede tocar la configuracion IA.
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
        $providerValues = array_map(
            static fn (AiProvider $provider): string => $provider->value,
            AiProvider::cases(),
        );

        return [
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'provider' => ['required', Rule::in($providerValues)],
            'api_key' => ['nullable', 'string', 'min:10', 'max:500'],
            'model' => ['nullable', 'string', 'max:120'],
            'system_prompt' => ['nullable', 'string', 'max:8000'],
            'is_active' => ['nullable', 'boolean'],
            'max_messages_per_hour' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_sessions_per_day' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * Normaliza el booleano del checkbox antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'Selecciona un provider de IA.',
            'provider.in' => 'El provider seleccionado no es valido.',
            'api_key.min' => 'La API key parece demasiado corta.',
            'max_messages_per_hour.min' => 'El limite de mensajes por hora debe ser al menos 1.',
            'max_sessions_per_day.min' => 'El limite de sesiones diarias debe ser al menos 1.',
        ];
    }
}
