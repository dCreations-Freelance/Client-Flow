<?php

namespace App\Models;

use App\Enums\TimeEntryType;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Entrada de tiempo registrada contra una tarea.
 *
 * Cada fila representa un periodo de trabajo (manual o
 * capturado por el temporizador) que el admin imputa a
 * una tarea concreta de un proyecto. Las entradas de
 * tipo `timer` llevan `started_at` con la marca de
 * inicio; las manuales solo tienen `minutes`.
 *
 * El campo `project_id` se persiste aunque `task_id`
 * ya implica la pertenencia al proyecto: lo usamos
 * para acelerar los calculos del dashboard (agregados
 * por proyecto sin JOIN con `tasks`).
 *
 * La cache `tasks.total_logged_minutes` se mantiene
 * sincronizada automaticamente desde el `boot()` del
 * modelo (observers en `created`, `updated` y
 * `deleted`).
 */
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'project_id',
        'description',
        'type',
        'minutes',
        'started_at',
        'billed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TimeEntryType::class,
            'started_at' => 'datetime',
            'billed' => 'boolean',
            'minutes' => 'integer',
        ];
    }

    /**
     * Sincroniza la cache `tasks.total_logged_minutes`
     * cada vez que una entrada se crea, actualiza o
     * elimina.
     *
     * Usamos observers a nivel de modelo en vez de un
     * observer dedicado en `App\Observers` para mantener
     * la dependencia cerca de la logica que la dispara.
     * Asi, si en una fase futura se elimina o refactoriza
     * `TimeEntry`, la sincronizacion se va con el.
     *
     * Solo recalculamos cuando cambian los campos que
     * afectan al total: `task_id` y `minutes`. Cualquier
     * otra actualizacion (por ejemplo, marcar como
     * facturable) no dispara el recálculo.
     */
    protected static function booted(): void
    {
        static::created(function (TimeEntry $entry): void {
            self::recalculateTaskTotal($entry->task_id);
        });

        static::updated(function (TimeEntry $entry): void {
            if ($entry->wasChanged(['task_id', 'minutes'])) {
                $originalTaskId = (int) $entry->getOriginal('task_id');
                $newTaskId = (int) $entry->task_id;

                self::recalculateTaskTotal($originalTaskId);
                if ($originalTaskId !== $newTaskId) {
                    self::recalculateTaskTotal($newTaskId);
                }
            }
        });

        static::deleted(function (TimeEntry $entry): void {
            self::recalculateTaskTotal($entry->task_id);
        });
    }

    /**
     * Recalcula y persiste el total de minutos de una
     * tarea a partir de sus entradas vivas. Es un metodo
     * estatico para que el observer pueda llamarlo sin
     * necesidad de recargar el modelo.
     *
     * Si la tarea ya no existe (porque se borro en la
     * misma transaccion, por ejemplo), `save()` no hace
     * nada y se ignora silenciosamente.
     *
     * @param  int  $taskId
     * @return void
     */
    public static function recalculateTaskTotal(int $taskId): void
    {
        $task = Task::find($taskId);
        if ($task === null) {
            return;
        }

        $total = (int) Task::query()
            ->whereKey($taskId)
            ->withSum('timeEntries', 'minutes')
            ->value('time_entries_sum_minutes');

        $task->forceFill(['total_logged_minutes' => $total])->save();
    }

    // -----------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------

    /**
     * Tarea a la que pertenece la entrada.
     *
     * @return BelongsTo<Task, TimeEntry>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Usuario que registro la entrada.
     *
     * @return BelongsTo<User, TimeEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Proyecto al que pertenece la entrada. Es una copia
     * desnormalizada de `task.project_id` para acelerar
     * los calculos del dashboard.
     *
     * @return BelongsTo<Project, TimeEntry>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Minutos en formato `HH:MM` para mostrar en la UI.
     * Por ejemplo, 90 minutos -> "1h 30m", 45 -> "45m",
     * 125 -> "2h 05m". Es el formato que entiende el
     * usuario medio sin calculos mentales.
     *
     * @return string
     */
    public function getDisplayMinutesAttribute(): string
    {
        $total = (int) $this->minutes;
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
     * Minutos convertidos a horas en formato decimal con
     * dos decimales. Pensado para el dashboard y para
     * el campo `tasks.actual_hours` si en una fase
     * futura se sincronizan.
     *
     * @return float
     */
    public function getHoursAttribute(): float
    {
        return round(((int) $this->minutes) / 60, 2);
    }

    /**
     * Determina si la entrada es de tipo manual.
     *
     * @return bool
     */
    public function isManual(): bool
    {
        return $this->type?->isManual() ?? false;
    }

    /**
     * Determina si la entrada proviene del temporizador.
     *
     * @return bool
     */
    public function isTimer(): bool
    {
        return $this->type?->isTimer() ?? false;
    }

    /**
     * Determina si la entrada esta marcada como facturable.
     *
     * @return bool
     */
    public function isBillable(): bool
    {
        return (bool) $this->billed;
    }

    /**
     * Marca la entrada como facturable.
     *
     * @return void
     */
    public function markAsBilled(): void
    {
        if (! $this->isBillable()) {
            $this->forceFill(['billed' => true])->save();
        }
    }

    /**
     * Quita la marca de facturable.
     *
     * @return void
     */
    public function markAsUnbilled(): void
    {
        if ($this->isBillable()) {
            $this->forceFill(['billed' => false])->save();
        }
    }

    /**
     * Duracion del timer en segundos, calculada entre
     * `started_at` y `now()`. Solo tiene sentido para
     * entradas de tipo `timer`. Para las manuales
     * devuelve 0.
     *
     * @return int
     */
    public function liveElapsedSeconds(): int
    {
        if (! $this->isTimer() || $this->started_at === null) {
            return 0;
        }

        return max(0, Carbon::now()->diffInSeconds($this->started_at, true));
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Entradas de un proyecto.
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Entradas de un usuario.
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Entradas de una tarea concreta.
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeForTask(Builder $query, int $taskId): Builder
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Solo entradas marcadas como facturables.
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('billed', true);
    }

    /**
     * Solo entradas no facturables (o sin marcar).
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeNotBillable(Builder $query): Builder
    {
        return $query->where('billed', false);
    }

    /**
     * Entradas manuales (sin `started_at`).
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('type', TimeEntryType::Manual->value);
    }

    /**
     * Entradas de temporizador (con `started_at`).
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeTimer(Builder $query): Builder
    {
        return $query->where('type', TimeEntryType::Timer->value);
    }

    /**
     * Entradas creadas en un rango de fechas (inclusivo).
     * Si alguno de los limites es null, no se aplica ese
     * limite.
     *
     * @param  Builder<TimeEntry>  $query
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return Builder<TimeEntry>
     */
    public function scopeInDateRange(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Orden por mas reciente primero.
     *
     * @param  Builder<TimeEntry>  $query
     * @return Builder<TimeEntry>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
