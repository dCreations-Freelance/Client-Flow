<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Database\Factories\ProjectTemplateTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarea predefinida de una plantilla de proyecto.
 *
 * Modela el equivalente a `Task` pero a nivel de
 * plantilla: no tiene `assignee_id`, `due_date`,
 * `parent_id`, `completed_at` ni `actual_hours`
 * (son especificos del proyecto y los rellena el
 * admin despues de aplicar la plantilla).
 *
 * La columna destino se referencia por
 * `column_position` (no por `column_id`) para que
 * la plantilla sea resistente a renombrados: si
 * el admin renombra una columna, las tareas siguen
 * apuntando a la misma posicion, que es lo que
 * queremos preservar.
 */
class ProjectTemplateTask extends Model
{
    /** @use HasFactory<ProjectTemplateTaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'template_id',
        'column_position',
        'title',
        'description',
        'type',
        'priority',
        'estimated_hours',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'priority' => TaskPriority::class,
            'column_position' => 'integer',
            'position' => 'integer',
            'estimated_hours' => 'decimal:2',
        ];
    }

    /**
     * Plantilla a la que pertenece la tarea.
     *
     * @return BelongsTo<ProjectTemplate, ProjectTemplateTask>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    /**
     * Orden por defecto: por columna y luego por
     * posicion dentro de la columna. Mismo orden
     * que las tareas reales del proyecto.
     *
     * @param  Builder<ProjectTemplateTask>  $query
     * @return Builder<ProjectTemplateTask>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('column_position')->orderBy('position');
    }
}
