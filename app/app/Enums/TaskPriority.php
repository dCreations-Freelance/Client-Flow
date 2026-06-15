<?php

namespace App\Enums;

/**
 * Prioridad de una tarea.
 *
 * Mapea a la paleta del design system:
 * - Critical: rojo (urgencia, hay que hacerlo ya).
 * - High: naranja (importante, hacerlo pronto).
 * - Medium: azul (valor por defecto).
 * - Low: gris (nice to have).
 */
enum TaskPriority: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critica',
            self::High => 'Alta',
            self::Medium => 'Media',
            self::Low => 'Baja',
        };
    }

    /**
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Critical => 'red',
            self::High => 'orange',
            self::Medium => 'blue',
            self::Low => 'gray',
        };
    }

    /**
     * @return string
     */
    public function badgeClasses(): string
    {
        return match ($this->color()) {
            'red' => 'bg-[#FEF2F2] text-[#DC2626]',
            'orange' => 'bg-[#FFFBEB] text-[#D97706]',
            'blue' => 'bg-[#EFF6FF] text-[#2563EB]',
            default => 'bg-[#F4F1EA] text-[#6B7280]',
        };
    }
}
