<?php

namespace App\Models;

use Database\Factories\BoardColumnFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Columna de un tablero kanban.
 *
 * Cada proyecto tiene sus propias columnas configurables: nombre,
 * color opcional, posicion y un flag `is_default` para distinguir
 * las que crea `DefaultBoardColumnsService` de las que el admin
 * anade despues.
 */
class BoardColumn extends Model
{
    /** @use HasFactory<BoardColumnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'color',
        'position',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Genera el slug automaticamente al crear si no se ha
     * establecido.
     */
    protected static function booted(): void
    {
        static::creating(function (BoardColumn $column): void {
            if (blank($column->slug)) {
                $column->slug = static::generateUniqueSlug($column->project_id, $column->name);
            }
        });
    }

    /**
     * Slug unico dentro del proyecto. Si ya existe uno igual se
     * anade sufijo numerico.
     *
     * @return string
     */
    public static function generateUniqueSlug(int $projectId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('project_id', $projectId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<Project, BoardColumn>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'column_id');
    }

    /**
     * Tareas raiz (sin parent) ordenadas por position.
     *
     * @return HasMany<Task>
     */
    public function rootTasks(): HasMany
    {
        return $this->tasks()->whereNull('parent_id')->orderBy('position');
    }

    /**
     * Scope: columnas ordenadas por su position.
     *
     * @param  Builder<BoardColumn>  $query
     * @return Builder<BoardColumn>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }

    /**
     * Determina si la columna es de las creadas automaticamente.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }
}
