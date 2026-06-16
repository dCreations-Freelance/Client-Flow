<?php

namespace App\Enums;

/**
 * Visibilidad de un documento de proyecto.
 *
 * Determina quien puede leer un documento en el portal cliente.
 * - `private`: solo visible por administradores (y accesible via MCP en
 *   fases futuras).
 * - `public`: visible por administradores y por clientes miembros de
 *   la organizacion del proyecto (siempre que el proyecto sea visible
 *   al cliente y no este archivado, comprobaciones que se hacen en la
 *   policy y el scope del modelo).
 *
 * La eleccion de visibilidad se hace al crear/editar el documento y
 * no se cambia de forma implicita por ninguna otra operacion.
 */
enum DocumentVisibility: string
{
    case Private = 'private';

    case Public = 'public';

    /**
     * Etiqueta legible en castellano para mostrar en badges y listados.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privado',
            self::Public => 'Publico',
        };
    }

    /**
     * Color semantico asociado a la visibilidad. Mapea a la paleta
     * del design system (`docs/DESIGN.md`).
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Private => 'gray',
            self::Public => 'blue',
        };
    }

    /**
     * Clases CSS listas para usar en un badge. Centralizamos el mapeo
     * a Tailwind aqui para que la vista no tenga que traducir el
     * color.
     *
     * @return string
     */
    public function badgeClasses(): string
    {
        return match ($this->color()) {
            'blue' => 'bg-[#EFF6FF] text-[#2563EB]',
            default => 'bg-[#F4F1EA] text-[#6B7280]',
        };
    }

    /**
     * Determina si el documento es visible para clientes.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this === self::Public;
    }

    /**
     * Determina si el documento es de uso interno.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this === self::Private;
    }
}
