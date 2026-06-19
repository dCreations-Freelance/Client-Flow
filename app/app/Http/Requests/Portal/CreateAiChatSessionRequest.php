<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de la peticion para crear una nueva sesion
 * de chat IA. Se usa desde el portal del cliente; el
 * admin tambien la usa cuando crea sesiones desde su
 * propio panel.
 *
 * El titulo es opcional: si esta vacio,
 * `AiChatSession::displayTitle()` genera uno a partir del
 * primer mensaje del usuario.
 */
class CreateAiChatSessionRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado con acceso al proyecto
     * (admin o cliente) puede iniciar una sesion. La
     * autorizacion fina la hace la policy en el
     * controlador.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:200'],
        ];
    }
}
