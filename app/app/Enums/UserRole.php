<?php

namespace App\Enums;

/**
 * Roles de usuario dentro de ClientFlow.
 *
 * Define la separacion fundamental entre la persona que instala y opera la
 * aplicacion (admin) y las personas externas que consumen el portal para
 * seguir sus proyectos (client). Cualquier comprobacion de permisos debe
 * pasar por este enum y nunca por strings sueltos.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Client = 'client';

    /**
     * Etiqueta legible para mostrar en UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Client => 'Cliente',
        };
    }

    /**
     * Determina si el rol es administrador.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Determina si el rol es cliente (portal externo).
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this === self::Client;
    }
}
