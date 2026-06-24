<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use Database\Factories\ProjectTemplateDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento "esqueleto" de una plantilla de proyecto.
 *
 * Equivalente a `ProjectDocument` pero a nivel de
 * plantilla: solo los campos que tienen sentido
 * antes de aplicar la plantilla (no `project_id`,
 * `created_by` ni timestamps de modificacion).
 */
class ProjectTemplateDocument extends Model
{
    /** @use HasFactory<ProjectTemplateDocumentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'template_id',
        'title',
        'content',
        'visibility',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'position' => 'integer',
        ];
    }

    /**
     * Plantilla a la que pertenece el documento.
     *
     * @return BelongsTo<ProjectTemplate, ProjectTemplateDocument>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    /**
     * Orden por defecto: posicion ascendente.
     *
     * @param  Builder<ProjectTemplateDocument>  $query
     * @return Builder<ProjectTemplateDocument>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Determina si el documento es publico (visible
     * por el cliente del portal tras aplicar la
     * plantilla). Atajo para evitar
     * `instanceof DocumentVisibility` en la vista.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->visibility?->isPublic() ?? false;
    }
}
