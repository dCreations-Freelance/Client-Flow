<?php

namespace App\Enums;

/**
 * Tipo de un evento de calendario.
 *
 * - `meeting`: reunion con hora de inicio y fin concretos.
 * - `milestone`: hito del proyecto (entrega, lanzamiento, etc.),
 *   normalmente de todo un dia.
 * - `deadline`: reservado para la representacion virtual derivada
 *   de `tasks.due_date`. Nunca se persiste un `CalendarEvent` con
 *   este tipo; el valor existe en el enum para que la UI pueda
 *   representarlo consistentemente (badge, color, etc.) cuando el
 *   `CalendarQueryService` lo sintetiza a partir de tareas.
 */
enum CalendarEventType: string
{
    case Meeting = 'meeting';

    case Milestone = 'milestone';

    case Deadline = 'deadline';

    /**
     * Etiqueta legible en castellano.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Meeting => 'Reunion',
            self::Milestone => 'Hito',
            self::Deadline => 'Fecha limite',
        };
    }

    /**
     * Color semantico para badges y dot del calendario. Mapea a
     * la paleta del design system.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Meeting => 'blue',
            self::Milestone => 'green',
            self::Deadline => 'orange',
        };
    }

    /**
     * Clases CSS completas para un badge segun el tipo. Pensado
     * para que las vistas no tengan que mantener el mapeo de
     * color -> clases y se mantenga la consistencia con el resto
     * de enums del proyecto (ver `ProjectStatus`, `TaskPriority`).
     *
     * @return string
     */
    public function badgeClasses(): string
    {
        return match ($this->color()) {
            'blue' => 'bg-[#EFF6FF] text-[#2563EB]',
            'green' => 'bg-[#F0FDF4] text-[#16A34A]',
            'orange' => 'bg-[#FFFBEB] text-[#D97706]',
            default => 'bg-[#F4F1EA] text-[#6B7280]',
        };
    }

    /**
     * Determina si el evento es una reunion.
     *
     * @return bool
     */
    public function isMeeting(): bool
    {
        return $this === self::Meeting;
    }

    /**
     * Determina si el evento es un hito del proyecto.
     *
     * @return bool
     */
    public function isMilestone(): bool
    {
        return $this === self::Milestone;
    }

    /**
     * Determina si el evento es una fecha limite virtual derivada
     * de una tarea. Nunca se persiste, pero se usa en UI cuando
     * el servicio sintetiza entradas desde `tasks.due_date`.
     *
     * @return bool
     */
    public function isDeadline(): bool
    {
        return $this === self::Deadline;
    }
}
