<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del formulario de preferencias de notificacion.
 *
 * Acepta un payload con la forma:
 * ```json
 * {
 *   "preferences": [
 *     {"event": "new_message", "in_app": true, "email": true},
 *     {"event": "task_assigned", "in_app": true, "email": false}
 *   ]
 * }
 * ```
 *
 * Validamos que `event` sea uno de los casos del enum y que los
 * booleanos sean efectivamente booleanos. El controlador se
 * encargara de hacer upsert fila a fila (no usamos `updateOrCreate`
 * en lote porque queremos respetar `firstOrCreate` y porque el
 * numero de filas es siempre seis, manejable sin optimizacion).
 *
 * Se usa `sometimes` en las reglas para que un payload vacio o
 * parcial (por ejemplo, una entrada sin `email`) no falle la
 * validacion; el controlador tratara los nulos como `false`.
 */
class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede gestionar sus
     * preferencias. La policy comprueba que las filas afecten
     * solo al usuario actual.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        $validEvents = array_map(fn (NotificationEvent $e) => $e->value, NotificationEvent::cases());

        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*' => ['required', 'array'],
            'preferences.*.event' => ['required', 'string', Rule::in($validEvents)],
            'preferences.*.in_app' => ['sometimes', 'boolean'],
            'preferences.*.email' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'No se han recibido preferencias para actualizar.',
            'preferences.*.event.required' => 'Cada preferencia debe indicar el evento.',
            'preferences.*.event.in' => 'El evento indicado no es valido.',
        ];
    }
}
