<?php

namespace App\Models;

use Database\Factories\ProjectTemplateColumnFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Columna predefinida de una plantilla de proyecto.
 *
 * Modela el equivalente a `BoardColumn` pero a
 * nivel de plantilla: tiene `name`, `color` y
 * `position`, pero NO `slug` ni `is_default` (el
 * slug no es necesario porque las tareas se
 * mapean a la columna por su `position` en la
 * plantilla, no por slug).
 */
class ProjectTemplateColumn extends Model
{
    /** @use HasFactory<ProjectTemplateColumnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'template_id',
        'name',
        'color',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Plantilla a la que pertenece la columna.
     *
     * @return BelongsTo<ProjectTemplate, ProjectTemplateColumn>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    /**
     * Orden por defecto: posicion ascendente.
     *
     * @param  Builder<ProjectTemplateColumn>  $query
     * @return Builder<ProjectTemplateColumn>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
