<?php

namespace App\Enums;

/**
 * Tipo de tarea.
 *
 * El tipo es independiente de la prioridad: una tarea puede ser un
 * bug critico o un feature de prioridad baja. El tipo ayuda a
 * clasificar la naturaleza del trabajo y a filtrarlo en el kanban.
 */
enum TaskType: string
{
    case Feature = 'feature';
    case Bug = 'bug';
    case Improvement = 'improvement';
    case Task = 'task';

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Feature => 'Caracteristica',
            self::Bug => 'Error',
            self::Improvement => 'Mejora',
            self::Task => 'Tarea',
        };
    }

    /**
     * Icono Heroicons asociado al tipo. Se devuelve el nombre del
     * componente Blade de Heroicons para que la vista lo instancie.
     *
     * @return string
     */
    public function icon(): string
    {
        return match ($this) {
            self::Feature => 'sparkles',
            self::Bug => 'bug',
            self::Improvement => 'arrow-trending-up',
            self::Task => 'check',
        };
    }

    /**
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Feature => 'info',
            self::Bug => 'red',
            self::Improvement => 'orange',
            self::Task => 'gray',
        };
    }

    /**
     * @return string
     */
    public function badgeClasses(): string
    {
        return match ($this->color()) {
            'info' => 'bg-[#F5F3FF] text-[#8B5CF6]',
            'red' => 'bg-[#FEF2F2] text-[#DC2626]',
            'orange' => 'bg-[#FFFBEB] text-[#D97706]',
            default => 'bg-[#F4F1EA] text-[#6B7280]',
        };
    }
}
