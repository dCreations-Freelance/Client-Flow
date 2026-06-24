<?php

namespace App\Services\TimeTracking;

use App\Enums\TimeEntryType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Servicio central de registro de tiempo.
 *
 * Aporta una API unica para:
 * - Crear entradas manuales.
 * - Arrancar y parar el temporizador (con la regla de
 *   un solo timer activo a la vez por usuario: si se
 *   inicia uno nuevo y existe otro activo, el anterior
 *   se cierra automaticamente).
 * - Consultar el estado del timer activo.
 * - Generar los agregados del dashboard de horas por
 *   proyecto (total, por miembro, por tarea).
 *
 * Centralizar esta logica aqui tiene dos objetivos:
 * 1. Evitar duplicar las reglas (auto-stop, recálculo
 *    de cache, redondeo de minutos) en cada componente
 *    Livewire o controlador que la necesite.
 * 2. Permitir que los tests unitarios verifiquen el
 *    comportamiento sin tener que levantar HTTP.
 */
class TimeTrackingService
{
    /**
     * Minutos minimos que debe tener una entrada cerrada
     * desde el timer. Por debajo de este umbral
     * redondeamos a 1 minuto para no tener entradas de
     * 0 minutos (que ensucian el dashboard y no aportan
     * informacion).
     */
    private const MIN_TIMER_MINUTES = 1;

    /**
     * Crea una entrada manual contra una tarea. Se
     * persiste con `started_at = null` y `type = manual`.
     * La fecha del trabajo se guarda en `created_at`
     * (reflejando cuando se registro en la app); si en
     * una fase futura se quiere distinguir entre fecha
     * de registro y fecha de trabajo, se anade un
     * campo `entry_date` y se rellena aqui.
     *
     * La cache `total_logged_minutes` se actualiza
     * automaticamente desde el observer del modelo.
     *
     * @param  Task  $task  tarea a la que se imputa la entrada
     * @param  User  $user  usuario autor
     * @param  array<string, mixed>  $data  campos validados por Form Request
     * @return TimeEntry
     */
    public function createManualEntry(Task $task, User $user, array $data): TimeEntry
    {
        $minutes = (int) $data['minutes'];
        if ($minutes < 1) {
            throw new InvalidArgumentException('Los minutos deben ser al menos 1.');
        }

        $description = $data['description'] ?? null;
        $description = $description !== null ? trim((string) $description) : null;
        $description = $description === '' ? null : $description;

        $billed = array_key_exists('billed', $data) ? (bool) $data['billed'] : false;

        return TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'project_id' => $task->project_id,
            'description' => $description,
            'type' => TimeEntryType::Manual,
            'minutes' => $minutes,
            'started_at' => null,
            'billed' => $billed,
        ]);
    }

    /**
     * Actualiza los campos editables de una entrada.
     * `type`, `task_id`, `project_id` y `started_at`
     * quedan fuera de proposito (ver UpdateTimeEntryRequest).
     * La cache de la tarea se actualiza via observer
     * si `minutes` cambia.
     *
     * @param  TimeEntry  $entry
     * @param  array<string, mixed>  $data
     * @return TimeEntry
     */
    public function updateEntry(TimeEntry $entry, array $data): TimeEntry
    {
        $payload = [];

        if (array_key_exists('description', $data)) {
            $description = $data['description'] !== null ? trim((string) $data['description']) : null;
            $payload['description'] = $description === '' ? null : $description;
        }

        if (array_key_exists('minutes', $data)) {
            $minutes = (int) $data['minutes'];
            if ($minutes < 1) {
                throw new InvalidArgumentException('Los minutos deben ser al menos 1.');
            }
            $payload['minutes'] = $minutes;
        }

        if (array_key_exists('billed', $data)) {
            $payload['billed'] = (bool) $data['billed'];
        }

        if ($payload !== []) {
            $entry->update($payload);
        }

        return $entry->fresh();
    }

    /**
     * Inicia un nuevo temporizador para la combinacion
     * (usuario, tarea). Si el usuario ya tiene un timer
     * activo en cualquier otra tarea, se cierra primero
     * (regla de un timer activo por usuario).
     *
     * Devuelve la entrada recien creada. La cache
     * `total_logged_minutes` de la tarea afectada se
     * actualizara cuando se pare el timer (aqui solo
     * se inserta la fila con `minutes = 0` como marcador
     * temporal, que el observer no recalcula porque
     * `total_logged_minutes` se rellena al cerrar).
     *
     * En la practica, para evitar contadores raros, la
     * entrada se crea con `minutes = 0` mientras esta
     * activa. El dashboard no la cuenta (porque el
     * scope `recent()` las ordena por fecha pero el
     * calculo de totales usa `sum(minutes)` que ya
     * recoge 0 sin afectar a la suma).
     *
     * @param  Task  $task
     * @param  User  $user
     * @return TimeEntry
     */
    public function startTimer(Task $task, User $user): TimeEntry
    {
        // Si ya hay un timer activo en cualquier tarea,
        // lo cerramos primero.
        $existing = $this->getActiveTimer($user);
        if ($existing !== null) {
            $this->stopTimer($user);
        }

        return TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'project_id' => $task->project_id,
            'description' => null,
            'type' => TimeEntryType::Timer,
            'minutes' => 0,
            'started_at' => Carbon::now(),
            'billed' => false,
        ]);
    }

    /**
     * Para el timer activo del usuario (si existe) y
     * calcula los minutos reales entre `started_at` y
     * el momento de la parada. Devuelve la entrada
     * cerrada o `null` si no habia timer activo.
     *
     * @param  User  $user
     * @return TimeEntry|null
     */
    public function stopTimer(User $user): ?TimeEntry
    {
        $active = $this->getActiveTimer($user);
        if ($active === null) {
            return null;
        }

        $minutes = $this->resolveTimerMinutes($active);
        $active->forceFill([
            'minutes' => $minutes,
        ])->save();

        // El observer de `updated` recalcula la cache
        // de la tarea afectada (porque `minutes` cambio).
        return $active->fresh();
    }

    /**
     * Devuelve el timer activo del usuario, o `null`
     * si no tiene ninguno. "Activo" significa
     * `type = timer` y `started_at != null`. En la
     * practica, si el timer se ha cerrado, `minutes`
     * sera > 0; pero la regla operativa es que solo
     * dejamos UNA entrada con `minutes = 0` y
     * `type = timer` por usuario.
     *
     * @param  User  $user
     * @return TimeEntry|null
     */
    public function getActiveTimer(User $user): ?TimeEntry
    {
        return TimeEntry::query()
            ->where('user_id', $user->id)
            ->where('type', TimeEntryType::Timer->value)
            ->where('minutes', 0)
            ->orderByDesc('started_at')
            ->first();
    }

    /**
     * Resumen agregado de tiempo para un proyecto. Es
     * la pieza central del dashboard y de la vista de
     * resumen del portal. Se ejecuta en un numero
     * acotado de queries (una para los agregados y otra
     * para los breakdowns) y devuelve una estructura
     * lista para pintar.
     *
     * Estructura del resultado:
     * ```
     * [
     *     'total_minutes' => int,
     *     'total_entries' => int,
     *     'billable_minutes' => int,
     *     'not_billable_minutes' => int,
     *     'by_member' => [
     *         ['user_id' => int, 'name' => string, 'minutes' => int],
     *         ...
     *     ],
     *     'by_task' => [
     *         ['task_id' => int, 'title' => string, 'minutes' => int],
     *         ...
     *     ],
     * ]
     * ```
     *
     * Los filtros son opcionales y se aplican a la vez
     * (AND). Si ambos limites de fecha son null, no se
     * filtra por fecha.
     *
     * @param  Project  $project
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @param  bool|null  $billable  null = todas, true = solo facturables, false = solo no facturables
     * @return array<string, mixed>
     */
    public function getProjectSummary(
        Project $project,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?bool $billable = null,
    ): array {
        $base = TimeEntry::query()
            ->where('project_id', $project->id)
            ->inDateRange($from, $to);

        if ($billable === true) {
            $base->billable();
        } elseif ($billable === false) {
            $base->notBillable();
        }

        $totals = (clone $base)
            ->selectRaw('COALESCE(SUM(minutes), 0) as total_minutes, COUNT(*) as total_entries, COALESCE(SUM(CASE WHEN billed = 1 THEN minutes ELSE 0 END), 0) as billable_minutes')
            ->first();

        $byMember = (clone $base)
            ->selectRaw('user_id, COALESCE(SUM(minutes), 0) as minutes')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->get()
            ->map(fn (TimeEntry $row) => [
                'user_id' => (int) $row->user_id,
                'name' => $row->user?->name ?? 'Usuario eliminado',
                'minutes' => (int) $row->minutes,
            ])
            ->sortByDesc('minutes')
            ->values()
            ->all();

        $byTask = (clone $base)
            ->selectRaw('task_id, COALESCE(SUM(minutes), 0) as minutes')
            ->groupBy('task_id')
            ->with('task:id,title')
            ->get()
            ->map(fn (TimeEntry $row) => [
                'task_id' => (int) $row->task_id,
                'title' => $row->task?->title ?? 'Tarea eliminada',
                'minutes' => (int) $row->minutes,
            ])
            ->sortByDesc('minutes')
            ->values()
            ->all();

        $totalMinutes = (int) ($totals->total_minutes ?? 0);
        $billableMinutes = (int) ($totals->billable_minutes ?? 0);

        return [
            'total_minutes' => $totalMinutes,
            'total_entries' => (int) ($totals->total_entries ?? 0),
            'billable_minutes' => $billableMinutes,
            'not_billable_minutes' => $totalMinutes - $billableMinutes,
            'by_member' => $byMember,
            'by_task' => $byTask,
        ];
    }

    /**
     * Calcula los minutos efectivos de un timer en
     * curso, con el suelo de `MIN_TIMER_MINUTES` para
     * no tener entradas de 0 minutos.
     *
     * @param  TimeEntry  $entry  debe ser de tipo `timer` y con `started_at` no nulo
     * @return int
     */
    private function resolveTimerMinutes(TimeEntry $entry): int
    {
        $startedAt = $entry->started_at;
        if ($startedAt === null) {
            return self::MIN_TIMER_MINUTES;
        }

        $seconds = max(0, Carbon::now()->diffInSeconds($startedAt, true));
        $minutes = (int) round($seconds / 60);

        return max(self::MIN_TIMER_MINUTES, $minutes);
    }
}
