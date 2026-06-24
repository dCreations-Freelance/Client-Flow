<?php

namespace App\Enums;

/**
 * Tipo de una entrada de tiempo.
 *
 * - `manual`: entrada creada a mano por el admin con una
 *   cantidad de minutos fija. Es el caso habitual cuando
 *   se registran tiempos de periodos ya cerrados (por
 *   ejemplo, "ayer dedique 90 minutos a la tarea X").
 * - `timer`: entrada creada por el temporizador. Lleva
 *   `started_at` con la marca de inicio; los minutos se
 *   calculan al parar el cronometro.
 */
enum TimeEntryType: string
{
    case Manual = 'manual';

    case Timer = 'timer';

    /**
     * Etiqueta legible en castellano para la UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Timer => 'Cronometro',
        };
    }

    /**
     * Color semantico del badge. Mapea a la paleta del
     * design system para mantener consistencia con el
     * resto de badges de ClientFlow.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Timer => 'blue',
        };
    }

    /**
     * Determina si la entrada es de tipo manual.
     *
     * @return bool
     */
    public function isManual(): bool
    {
        return $this === self::Manual;
    }

    /**
     * Determina si la entrada proviene del temporizador.
     *
     * @return bool
     */
    public function isTimer(): bool
    {
        return $this === self::Timer;
    }
}
