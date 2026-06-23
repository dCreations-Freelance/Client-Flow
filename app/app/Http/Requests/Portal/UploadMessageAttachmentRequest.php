<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para que un cliente suba un archivo adjunto a un
 * mensaje del chat (portal).
 *
 * Las reglas son identicas a la version admin y leen del
 * mismo `config/clientflow.php`. Lo que cambia es la
 * autorizacion: aqui un cliente puede subir si la policy
 * `MessageAttachmentPolicy::create` le concede acceso al
 * proyecto, lo que se evalua en el controlador.
 */
class UploadMessageAttachmentRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede llegar aqui. La
     * policy del controlador se encarga del resto.
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
