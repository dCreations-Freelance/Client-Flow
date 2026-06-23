<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para subir un archivo adjunto a un mensaje del
 * chat (panel admin).
 *
 * Reglas identicas a `UploadTaskAttachmentRequest` (mismo
 * config) pero sin la restriccion de rol: la policy ya hace
 * ese chequeo contra el proyecto. La autorizacion por rol
 * se delega a la policy.
 */
class UploadMessageAttachmentRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede llegar aqui; la
     * policy se encarga de exigir que pueda ver el proyecto.
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
            'attachment.mimes' => 'Tipo de archivo no permitido.',
        ];
    }
}
