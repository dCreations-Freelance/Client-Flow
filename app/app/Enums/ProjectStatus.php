<?php

namespace App\Enums;

/**
 * Estado logico de un proyecto.
 *
 * Los seis valores cubren todo el ciclo de vida: desde la planificacion
 * inicial hasta el archivado tras la entrega. `Archived` se aplica via
 * `archived_at` y no mediante este enum; se conserva como valor del
 * campo `status` para mantener un unico punto de control de los
 * estados visibles.
 */
enum ProjectStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case WaitingClient = 'waiting_client';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * Etiqueta legible en castellano.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planificacion',
            self::InProgress => 'En progreso',
            self::OnHold => 'En pausa',
            self::WaitingClient => 'Esperando cliente',
            self::Completed => 'Completado',
            self::Archived => 'Archivado',
        };
    }

    /**
     * Color semantico para badges y barras de progreso. Mapea a la
     * paleta del design system (`docs/DESIGN.md`).
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Planning => 'blue',
            self::InProgress => 'warning',
            self::OnHold => 'gray',
            self::WaitingClient => 'orange',
            self::Completed => 'green',
            self::Archived => 'gray',
        };
    }

    /**
     * Clases CSS listas para usar en un badge. Se devuelve el string
     * completo para que la vista no tenga que traducir el color.
     *
     * @return string
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Planning => 'bg-[#EFF6FF] text-[#2563EB]',
            self::InProgress => 'bg-[#FFFBEB] text-[#D97706]',
            self::OnHold => 'bg-[#F4F1EA] text-[#6B7280]',
            self::WaitingClient => 'bg-[#FFFBEB] text-[#D97706]',
            self::Completed => 'bg-[#F0FDF4] text-[#16A34A]',
            self::Archived => 'bg-[#F4F1EA] text-[#6B7280]',
        };
    }

    /**
     * Determina si el proyecto esta "abierto" (no cerrado ni
     * archivado). Util para excluir proyectos cerrados de listados
     * por defecto.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Archived], true);
    }
}
