<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Tarea de un proyecto (kanban).
 *
 * Una tarea pertenece a un proyecto, vive en una columna y puede
 * tener subtareas via `parent_id`. La posicion determina el orden
 * dentro de la columna. Se marca como completada via
 * `completed_at` (timestamp) para que sea reversible.
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'column_id',
        'parent_id',
        'title',
        'description',
        'priority',
        'type',
        'estimated_hours',
        'actual_hours',
        'total_logged_minutes',
        'due_date',
        'position',
        'assignee_id',
        'completed_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'type' => TaskType::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'position' => 'integer',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'total_logged_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, Task>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<BoardColumn, Task>
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'column_id');
    }

    /**
     * Tarea padre (si es una subtarea).
     *
     * @return BelongsTo<Task, Task>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Subtareas directas. Las subtareas de subtareas se obtienen
     * recorriendo la relacion recursivamente.
     *
     * @return HasMany<Task>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('position');
    }

    /**
     * Usuario asignado.
     *
     * @return BelongsTo<User, Task>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Creador de la tarea.
     *
     * @return BelongsTo<User, Task>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Archivos adjuntos a la tarea. Se cargan con `with('user')`
     * en la vista para evitar N+1 al pintar la lista.
     *
     * @return HasMany<TaskAttachment>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest('created_at');
    }

    /**
     * Entradas de tiempo registradas contra esta tarea.
     * La cache `total_logged_minutes` se mantiene
     * sincronizada por el observer del modelo
     * `TimeEntry`, por lo que esta relacion se usa
     * para los listados y los calculos detallados,
     * no para conocer el total.
     *
     * @return HasMany<TimeEntry>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class)->recent();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determina si la tarea esta marcada como completada.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Marca la tarea como completada. Idempotente.
     *
     * @return void
     */
    public function markCompleted(): void
    {
        if (! $this->isCompleted()) {
            $this->forceFill(['completed_at' => Carbon::now()])->save();
        }
    }

    /**
     * Re-abre la tarea. Idempotente.
     *
     * @return void
     */
    public function markPending(): void
    {
        if ($this->isCompleted()) {
            $this->forceFill(['completed_at' => null])->save();
        }
    }

    /**
     * Determina si la tarea esta vencida (fecha limite pasada y no
     * completada).
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        if ($this->due_date === null || $this->isCompleted()) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Numero total de subtareas (recursivo, no solo directas).
     * Pensado para mostrar el "2/5" en la mini-barra de subtareas.
     */
    public function getSubtasksCountAttribute(): int
    {
        return $this->subtasks()->count();
    }

    /**
     * Tiempo total registrado en la tarea convertido a
     * horas decimales. Es la conversion directa de
     * `total_logged_minutes` (la cache que mantiene el
     * observer de `TimeEntry`).
     *
     * @return float
     */
    public function getTotalLoggedHoursAttribute(): float
    {
        return round(((int) $this->total_logged_minutes) / 60, 2);
    }

    /**
     * Tiempo total registrado en formato `HH:MM` para
     * mostrar en la UI sin que el usuario tenga que
     * dividir entre 60 mentalmente. Reutiliza la
     * misma logica que `TimeEntry::displayMinutes`
     * aplicada a la suma de la tarea.
     *
     * @return string
     */
    public function getTotalLoggedDisplayAttribute(): string
    {
        $total = (int) $this->total_logged_minutes;
        $hours = intdiv($total, 60);
        $mins = $total % 60;

        if ($hours === 0) {
            return $mins.'m';
        }

        if ($mins === 0) {
            return $hours.'h';
        }

        return $hours.'h '.str_pad((string) $mins, 2, '0', STR_PAD_LEFT).'m';
    }

    /**
     * Numero de subtareas completadas.
     */
    public function getSubtasksCompletedCountAttribute(): int
    {
        return $this->subtasks()->whereNotNull('completed_at')->count();
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Tareas raiz (sin padre). Las subtareas se manejan como
     * hijas y no se listan en el kanban principal.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Tareas completadas.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Tareas pendientes.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Tareas vencidas y no completadas.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today()->toDateString());
    }

    /**
     * Tareas ordenadas por position (ascendente).
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
