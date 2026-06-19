<?php

namespace App\Models;

use App\Enums\CalendarEventType;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Evento de calendario asociado a un proyecto.
 *
 * Cada evento pertenece a un proyecto concreto (`project_id` NOT
 * NULL) y puede tener asistentes invitados via el pivot
 * `calendar_event_user`. Los deadlines se renderizan en el
 * calendario desde `tasks.due_date` y nunca se persisten como
 * `CalendarEvent`; el enum reserva el valor `deadline` para que la
 * UI pueda representarlos con la misma forma visual.
 *
 * El flag `is_all_day` se anade respecto al data model original
 * para soportar hitos y eventos de jornada completa sin obligar a
 * introducir horas arbitrarias. Cuando es `true`, `starts_at` se
 * normaliza a `00:00:00` y `ends_at` a `23:59:59` del mismo dia.
 */
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'starts_at',
        'ends_at',
        'is_all_day',
        'created_by',
    ];

    /**
     * Casts: enum para comparaciones type-safe en policies y vistas;
     * fechas a Carbon para formateo en Blade; booleano explicito.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CalendarEventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
        ];
    }

    /**
     * Proyecto al que pertenece el evento.
     *
     * @return BelongsTo<Project, CalendarEvent>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Usuario que creo el evento. Se mantiene `restrictOnDelete` en
     * la FK para preservar la autoria historica.
     *
     * @return BelongsTo<User, CalendarEvent>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuarios invitados al evento. La relacion se mantiene con
     * timestamps para saber cuando se invito a cada uno.
     *
     * @return BelongsToMany<User>
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_event_user')
            ->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    /**
     * Fin efectivo del evento. Si no se ha establecido `ends_at`
     * se devuelve `starts_at` + 30 minutos como duracion
     * razonable por defecto para meetings. Esto evita que la UI
     * tenga que tratar el caso null en cada render.
     */
    public function getEndForQueryAttribute(): Carbon
    {
        if ($this->ends_at !== null) {
            return $this->ends_at;
        }

        return $this->starts_at?->copy()->addMinutes(30) ?? Carbon::now();
    }

    /**
     * Duracion en minutos. Si el evento es all-day devuelve la
     * jornada completa (1440 min) para que las queries de rango
     * no se rompan cuando los eventos cruzan la medianoche.
     *
     * @return int
     */
    public function durationMinutes(): int
    {
        if ($this->is_all_day) {
            return 24 * 60;
        }

        if ($this->starts_at === null || $this->ends_at === null) {
            return 30;
        }

        // Se calcula la diferencia absoluta para que el orden
        // de los argumentos no afecte al resultado.
        $minutes = (int) abs($this->ends_at->diffInMinutes($this->starts_at, true));

        return max(0, $minutes);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determina si el evento es de todo un dia.
     *
     * @return bool
     */
    public function isAllDay(): bool
    {
        return (bool) $this->is_all_day;
    }

    /**
     * Determina si el evento cae en una fecha concreta (compara
     * solo el dia, ignorando la hora). Util para situar el evento
     * en una celda del calendario mensual.
     *
     * @param  Carbon  $date
     * @return bool
     */
    public function isOnDate(Carbon $date): bool
    {
        if ($this->starts_at === null) {
            return false;
        }

        return $this->starts_at->isSameDay($date);
    }

    /**
     * Determina si el evento ya ha terminado (su `ends_at` es
     * anterior a ahora). Si no tiene `ends_at` se considera
     * terminado cuando su `starts_at` ya paso.
     *
     * @return bool
     */
    public function isPast(): bool
    {
        $reference = $this->ends_at ?? $this->starts_at;

        return $reference !== null && $reference->isPast();
    }

    /**
     * Determina si el evento aun no ha empezado.
     *
     * @return bool
     */
    public function isUpcoming(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    /**
     * Determina si el evento coincide con un rango de fechas.
     * Considera tanto `starts_at` como `ends_at` para que eventos
     * multi-dia se incluyan en cualquier dia del rango.
     *
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return bool
     */
    public function occursInRange(Carbon $from, Carbon $to): bool
    {
        if ($this->starts_at === null) {
            return false;
        }

        $end = $this->getEndForQueryAttribute();

        return $this->starts_at->lessThanOrEqualTo($to) && $end->greaterThanOrEqualTo($from);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Solo eventos del proyecto indicado.
     *
     * @param  Builder<CalendarEvent>  $query
     * @param  int  $projectId
     * @return Builder<CalendarEvent>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Eventos cuyo intervalo solapa con el rango dado. Usa
     * `occursInRange` para considerar tanto `starts_at` como
     * `ends_at`, evitando excluir eventos multi-dia.
     *
     * @param  Builder<CalendarEvent>  $query
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return Builder<CalendarEvent>
     */
    public function scopeBetweenDates(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->where(function (Builder $q) use ($from, $to): void {
            $q->whereBetween('starts_at', [$from, $to])
                ->orWhereBetween('ends_at', [$from, $to])
                ->orWhere(function (Builder $sub) use ($from, $to): void {
                    $sub->where('starts_at', '<=', $from)
                        ->where('ends_at', '>=', $to);
                });
        });
    }

    /**
     * Solo eventos de un tipo concreto.
     *
     * @param  Builder<CalendarEvent>  $query
     * @param  string  $type
     * @return Builder<CalendarEvent>
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Proximos eventos a partir de ahora, ordenados ascendente.
     *
     * @param  Builder<CalendarEvent>  $query
     * @param  int  $limit
     * @return Builder<CalendarEvent>
     */
    public function scopeUpcoming(Builder $query, int $limit = 10): Builder
    {
        return $query->where('starts_at', '>=', Carbon::now())
            ->orderBy('starts_at')
            ->limit($limit);
    }

    /**
     * Orden cronologico ascendente. Pensado para el listado del
     * calendario: los eventos mas recientes al final.
     *
     * @param  Builder<CalendarEvent>  $query
     * @return Builder<CalendarEvent>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('starts_at')->orderBy('id');
    }

    // -----------------------------------------------------------------
    // Wrappers de tipo para type-safety en la UI
    // -----------------------------------------------------------------

    /**
     * Determina si el evento es una reunion.
     *
     * @return bool
     */
    public function isMeeting(): bool
    {
        return $this->type?->isMeeting() ?? false;
    }

    /**
     * Determina si el evento es un hito.
     *
     * @return bool
     */
    public function isMilestone(): bool
    {
        return $this->type?->isMilestone() ?? false;
    }
}
