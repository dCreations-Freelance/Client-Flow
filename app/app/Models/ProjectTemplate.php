<?php

namespace App\Models;

use Database\Factories\ProjectTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Plantilla reutilizable de proyecto.
 *
 * Una plantilla es un "esqueleto" de proyecto: un
 * conjunto de columnas, tareas predefinidas y
 * documentos iniciales. Al aplicarla a un proyecto
 * nuevo (via `ProjectTemplateService::applyToProject`),
 * esos elementos se copian y el admin puede editarlos
 * o borrarlos segun necesite.
 *
 * Decisiones de diseno:
 * - `name` y `slug` se generan automaticamente al
 *   crear. El slug se usa en URLs amigables del
 *   estilo `/admin/plantillas-proyecto/lanzamiento-web`.
 * - `category` es texto libre (no FK a una tabla de
 *   categorias) para que el admin pueda agrupar
 *   plantillas con sus propias etiquetas. Se
 *   indexa porque el filtro del listado es
 *   frecuente.
 * - `created_by` con `restrictOnDelete` en la FK:
 *   autoria historica, no se puede borrar al admin
 *   que las creo sin reasignar primero.
 */
class ProjectTemplate extends Model
{
    /** @use HasFactory<ProjectTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'created_by',
    ];

    /**
     * Genera el slug automaticamente al crear. Se
     * anade un sufijo numerico si ya existe para
     * garantizar unicidad sin intervencion del
     * usuario.
     */
    protected static function booted(): void
    {
        static::creating(function (ProjectTemplate $template): void {
            if (blank($template->slug)) {
                $template->slug = static::generateUniqueSlug($template->name);
            }
        });
    }

    /**
     * Genera un slug unico para el nombre de la
     * plantilla. Patron identico al usado en
     * `Project` para coherencia interna.
     *
     * @param  string  $name
     * @return string
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * Creador de la plantilla.
     *
     * @return BelongsTo<User, ProjectTemplate>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Columnas predefinidas de la plantilla, en
     * orden de izquierda a derecha.
     *
     * @return HasMany<ProjectTemplateColumn>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(ProjectTemplateColumn::class, 'template_id')->ordered();
    }

    /**
     * Tareas predefinidas de la plantilla, en
     * orden por columna y luego por position.
     *
     * @return HasMany<ProjectTemplateTask>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class, 'template_id')
            ->orderBy('column_position')
            ->orderBy('position');
    }

    /**
     * Documentos esqueleto de la plantilla, en
     * orden de aparicion.
     *
     * @return HasMany<ProjectTemplateDocument>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectTemplateDocument::class, 'template_id')->ordered();
    }

    /**
     * Numero de columnas predefinidas. Pensado para
     * mostrar en el listado sin cargar la coleccion.
     *
     * @return int
     */
    public function getColumnCountAttribute(): int
    {
        return (int) $this->columns()->count();
    }

    /**
     * Numero de tareas predefinidas.
     *
     * @return int
     */
    public function getTaskCountAttribute(): int
    {
        return (int) $this->tasks()->count();
    }

    /**
     * Numero de documentos esqueleto.
     *
     * @return int
     */
    public function getDocumentCountAttribute(): int
    {
        return (int) $this->documents()->count();
    }

    /**
     * Etiqueta legible de la categoria (o "Sin
     * categoria" si es nula). Pensada para los
     * chips del filtro y la cabecera del detalle.
     *
     * @return string
     */
    public function getCategoryLabelAttribute(): string
    {
        return $this->category !== null && $this->category !== ''
            ? $this->category
            : 'Sin categoria';
    }

    /**
     * Lista de categorias distintas presentes en
     * la biblioteca. Se cachea implicitamente por
     * Eloquent, no necesitamos mas.
     *
     * @param  Builder<ProjectTemplate>  $query
     * @return Builder<ProjectTemplate>
     */
    public function scopeInCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || $category === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Filtra por texto libre en `name` y
     * `description`. La busqueda es `LIKE` simple
     * (sin full-text) porque las plantillas son
     * pocas y el rendimiento no es problema.
     *
     * @param  Builder<ProjectTemplate>  $query
     * @return Builder<ProjectTemplate>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search): void {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
