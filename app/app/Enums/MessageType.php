<?php

namespace App\Enums;

/**
 * Tipo de un mensaje en el chat de proyecto.
 *
 * - `text`: mensaje normal escrito por un usuario (admin o cliente).
 * - `system`: mensaje generado automaticamente por la app para
 *   reflejar cambios relevantes (tarea creada, completada, etc.).
 *   No tiene autor humano.
 * - `file`: reservado para una fase futura en la que se permitira
 *   adjuntar archivos. Por ahora no se persisten mensajes de este
 *   tipo, pero el enum lo declara para no tener que migrar cuando
 *   se anada la feature.
 */
enum MessageType: string
{
    case Text = 'text';

    case System = 'system';

    case File = 'file';

    /**
     * Etiqueta legible en castellano.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::System => 'Sistema',
            self::File => 'Archivo',
        };
    }

    /**
     * Color semantico para posibles badges o filtros. Mapea a la
     * paleta del design system.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Text => 'blue',
            self::System => 'gray',
            self::File => 'info',
        };
    }

    /**
     * Determina si es un mensaje de texto normal.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this === self::Text;
    }

    /**
     * Determina si es un mensaje automatico del sistema.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this === self::System;
    }

    /**
     * Determina si es un mensaje con archivo adjunto.
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return $this === self::File;
    }
}
