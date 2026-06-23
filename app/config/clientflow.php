<?php

/*
|--------------------------------------------------------------------------
| ClientFlow Configuration
|--------------------------------------------------------------------------
|
| Configuracion transversal del producto. Aqui centralizamos los
| parametros que afectan a varios modulos pero que conviene poder
| ajustar desde `.env` sin tocar codigo. Cada seccion incluye un
| comentario explicando que modulo la consume y por que el valor
| por defecto es el que es.
|
| Las secciones actuales son:
| - `attachments`: limites y rutas del modulo de adjuntos de
|   tareas y mensajes (fase 10).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Adjuntos
    |--------------------------------------------------------------------------
    |
    | Parametros del modulo de archivos adjuntos en tareas y
    | mensajes. El admin no los edita desde la UI en el MVP: si
    | quiere cambiarlos, edita `.env` y reinicia el servidor.
    | Esto evita exponer controles de configuracion que podrian
    | usarse para aceptar archivos maliciosos.
    |
    */

    'attachments' => [

        /*
        | Disco de almacenamiento donde se guardan los archivos.
        | Por defecto `local`, que apunta a
        | `storage/app/private/`. Los archivos NUNCA se exponen
        | en `public/`; se sirven via controlador con policy.
        | Si en el futuro se quiere usar S3 u otro driver,
        | basta con cambiar este valor.
        */
        'disk' => env('CLIENTFLOW_ATTACHMENTS_DISK', 'local'),

        /*
        | Tamano maximo permitido por archivo, en kilobytes. Por
        | defecto 10 MB (10240 KB), suficiente para PDFs de
        | propuestas, capturas y disenos. Si el hosting tiene
        | limites menores en `upload_max_filesize` /
        | `post_max_size`, el admin debera reducir este valor.
        */
        'max_size_kb' => (int) env('CLIENTFLOW_ATTACHMENTS_MAX_SIZE_KB', 10240),

        /*
        | Numero maximo de archivos por envio. En el chat se
        | aplica a la subida multiple; en tareas se reserva
        | para una fase futura con subida multiple en el
        | modal.
        */
        'max_files_per_upload' => (int) env('CLIENTFLOW_ATTACHMENTS_MAX_FILES', 5),

        /*
        | Lista de extensiones permitidas (sin punto, en
        | minusculas). El servicio valida contra esta lista en
        | combinacion con el `mimetype` del archivo. Mantener
        | la lista pequena y explicita reduce superficie de
        | ataque (sin `.exe`, `.bat`, `.scr`, etc.).
        */
        'allowed_mimes' => array_values(array_filter(array_map(
            fn (string $ext): string => strtolower(trim($ext)),
            explode(',', (string) env(
                'CLIENTFLOW_ATTACHMENTS_MIMES',
                'pdf,png,jpg,jpeg,gif,webp,docx,xlsx,pptx,zip,txt,csv,md,json'
            ))
        ))),

        /*
        | Subdirectorio base donde se guardan los archivos. El
        | servicio lo completa con `{project_id}/attachments/
        | {tasks|messages}/`. Si el admin quiere moverlo a
        | otra ruta (por ejemplo, para usar el mismo patron
        | que el resto del proyecto), solo tiene que cambiar
        | este valor.
        */
        'subdirectory' => env('CLIENTFLOW_ATTACHMENTS_SUBDIR', 'clientflow/projects'),

    ],

];
