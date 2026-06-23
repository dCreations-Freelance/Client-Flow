<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para subir un archivo adjunto a una tarea.
 *
 * Las reglas leen los limites desde `config/clientflow.php`
 * para que el admin pueda ajustarlos via `.env` sin tocar
 * codigo. Los mensajes estan en castellano para coincidir con
 * el resto de formularios.
 *
 * `attachment` se valida como `file` (no como `string`), lo que
 * activa la validacion de tamano de PHP y la deteccion de tipo
 * MIME real (no la declarada por el cliente, lo que mitiga
 * ataques basicos de suplantacion).
 */
class UploadTaskAttachmentRequest extends FormRequest
{
    /**
     * Solo el admin puede subir adjuntos a tareas. La policy
     * hace el chequeo fino contra el proyecto, pero esta
     * autorizacion ya descarta a clientes desde el routing.
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
        $maxKb = (int) config('clientflow.attachments.max_size_kb', 10240);
        $mimes = implode(',', (array) config('clientflow.attachments.allowed_mimes', []));

        return [
            'attachment' => ['required', 'file', "max:{$maxKb}", "mimes:{$mimes}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachment.required' => 'Selecciona un archivo para subir.',
            'attachment.file' => 'El archivo subido no es valido.',
            'attachment.max' => 'El archivo no puede pesar mas de :max KB.',
            'attachment.mimes' => 'Tipo de archivo no permitido. Permitidos: '.implode(', ', (array) config('clientflow.attachments.allowed_mimes', [])),
        ];
    }
}
