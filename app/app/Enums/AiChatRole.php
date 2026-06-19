<?php

namespace App\Enums;

/**
 * Rol de un mensaje dentro de una sesion de chat con IA.
 *
 * - `User`: mensaje escrito por el usuario humano.
 * - `Assistant`: respuesta generada por el modelo.
 * - `System`: instrucciones persistentes que el modelo
 *   recibe antes que el resto. En ClientFlow no se exponen
 *   al usuario; los genera internamente
 *   `ProjectContextBuilder`.
 */
enum AiChatRole: string
{
    case User = 'user';

    case Assistant = 'assistant';

    case System = 'system';

    /**
     * Etiqueta legible en castellano.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::User => 'Usuario',
            self::Assistant => 'Asistente',
            self::System => 'Sistema',
        };
    }

    /**
     * Color semantico para badges en la UI.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::User => 'blue',
            self::Assistant => 'info',
            self::System => 'gray',
        };
    }

    /**
     * Determina si el mensaje fue escrito por el humano.
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this === self::User;
    }

    /**
     * Determina si el mensaje es una respuesta del modelo.
     *
     * @return bool
     */
    public function isAssistant(): bool
    {
        return $this === self::Assistant;
    }

    /**
     * Determina si el mensaje es una instruccion de sistema
     * (no se muestra en el chat visible al usuario).
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this === self::System;
    }
}
