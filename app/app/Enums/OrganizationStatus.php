<?php

namespace App\Enums;

/**
 * Estado logico de una organizacion.
 *
 * `Active` es el estado normal. `Inactive` permite ocultar la organizacion
 * del listado principal y bloquear nuevas invitaciones, sin necesidad
 * de borrado fisico (que romperia la integridad referencial de proyectos
 * y mensajes).
 */
enum OrganizationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Etiqueta legible en castellano.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Inactive => 'Inactiva',
        };
    }

    /**
     * Color semantico para usar en badges.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Inactive => 'gray',
        };
    }
}
