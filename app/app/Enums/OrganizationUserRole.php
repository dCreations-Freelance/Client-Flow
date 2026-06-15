<?php

namespace App\Enums;

/**
 * Rol que un usuario desempena dentro de una organizacion concreta.
 *
 * `Owner` puede administrar miembros y proyectos. `Member` solo tiene
 * acceso de lectura/escritura al contenido donde se le ha dado acceso.
 * El admin de ClientFlow no es necesariamente owner de una organizacion;
 * el owner es la persona responsable de esa empresa cliente concreta.
 */
enum OrganizationUserRole: string
{
    case Owner = 'owner';
    case Member = 'member';

    /**
     * Etiqueta legible.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Responsable',
            self::Member => 'Miembro',
        };
    }

    /**
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this === self::Owner;
    }
}
