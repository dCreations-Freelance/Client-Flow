<?php

namespace App\Services\Calendar;

use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de queries para el calendario por proyecto.
 *
 * Centraliza todo lo relativo a obtener los datos que pinta la
 * vista del calendario:
 * - Eventos persistidos en el rango solicitado.
 * - Deadlines virtuales derivados de `tasks.due_date`.
 * - Grilla de dias del mes (con overflow al mes anterior y
 *   siguiente) o de la semana.
 *
 * Mantener la logica aqui permite que el componente Livewire no
 * tenga que conocer detalles del modelo y que cualquier cambio en
 * la fuente de datos (por ejemplo, anadir recurrencias) afecte a
 * un solo sitio.
 */
class CalendarQueryService
{
    /**
     * Eventos persistidos del proyecto en el rango indicado.
     * Eager-loads `creator` y `attendees` para que la vista pueda
     * pintar las tarjetas sin disparar N+1.
     *
     * @param  Project  $project
     * @param  Carbon  $from  inicio del rango (inclusivo)
     * @param  Carbon  $to  fin del rango (inclusivo)
     * @return Collection<int, CalendarEvent>
     */
    public function getEventsForPeriod(Project $project, Carbon $from, Carbon $to): Collection
    {
        return $project->calendarEvents()
            ->with(['creator', 'attendees'])
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('starts_at', [$from, $to])
                    ->orWhereBetween('ends_at', [$from, $to])
                    ->orWhere(function ($sub) use ($from, $to): void {
                        $sub->where('starts_at', '<=', $from)
                            ->where('ends_at', '>=', $to);
                    });
            })
            ->ordered()
            ->get();
    }

    /**
     * Deadlines virtuales derivados de las tareas raiz del
     * proyecto con `due_date` en el rango. Cada entrada se adapta
     * a una estructura compatible con la vista: un objeto ligero
     * con los campos que necesita el calendario.
     *
     * @param  Project  $project
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return Collection<int, object>
     */
    public function getVirtualDeadlines(Project $project, Carbon $from, Carbon $to): Collection
    {
        $tasks = $project->tasks()
            ->whereNull('parent_id')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with(['column', 'assignee'])
            ->orderBy('due_date')
            ->get();

        return $tasks->map(function (Task $task): object {
            return (object) [
                'id' => 'task-'.$task->id,
                'type' => CalendarEventType::Deadline,
                'title' => $task->title,
                'starts_at' => $task->due_date,
                'ends_at' => null,
                'is_all_day' => true,
                'is_virtual' => true,
                'source_task' => $task,
            ];
        });
    }

    /**
     * Payload completo para el componente Livewire del
     * calendario: grilla de dias segun la vista solicitada,
     * eventos por dia y deadlines virtuales por dia.
     *
     * @param  Project  $project
     * @param  Carbon  $currentDate  fecha de referencia (cualquier dia del mes/semana a mostrar)
     * @param  string  $view  'month' o 'week'
     * @return array<string, mixed>
     */
    public function getCalendarData(Project $project, Carbon $currentDate, string $view): array
    {
        if ($view === 'week') {
            $range = $this->getWeekRange($currentDate);
        } else {
            $range = $this->getMonthRange($currentDate);
        }

        $events = $this->getEventsForPeriod($project, $range['from'], $range['to']);
        $deadlines = $this->getVirtualDeadlines($project, $range['from'], $range['to']);

        $days = $view === 'week'
            ? $this->buildWeekDays($currentDate)
            : $this->buildMonthDays($currentDate);

        $eventsByDay = $this->groupByDay($events);
        $deadlinesByDay = $this->groupByDay($deadlines);

        return [
            'view' => $view,
            'current_date' => $currentDate,
            'from' => $range['from'],
            'to' => $range['to'],
            'days' => $days,
            'events_by_day' => $eventsByDay,
            'deadlines_by_day' => $deadlinesByDay,
            'events' => $events,
            'deadlines' => $deadlines,
        ];
    }

    /**
     * Rango de fechas que cubre el mes de la fecha de referencia.
     * Si la fecha cae en mitad de mes, el rango se extiende a los
     * dias visibles del mes anterior y siguiente para mantener
     * la grilla completa de 6 filas x 7 columnas.
     *
     * @param  Carbon  $currentDate
     * @return array{from: Carbon, to: Carbon}
     */
    public function getMonthRange(Carbon $currentDate): array
    {
        $firstOfMonth = $currentDate->copy()->startOfMonth();
        $lastOfMonth = $currentDate->copy()->endOfMonth();

        // El calendario empieza en lunes (convenio es). Si el
        // primer dia del mes no es lunes, retrocedemos al lunes
        // anterior para incluir la semana parcial.
        $from = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);

        // Si el primer dia del mes cae en lunes no hay overflow
        // previo; si cae en martes ya hay 6 dias del mes anterior
        // en la primera semana. Asi garantizamos que la grilla
        // siempre tiene semanas completas empezando en lunes.
        $to = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Rango de fechas que cubre la semana de la fecha de
     * referencia (lunes a domingo).
     *
     * @param  Carbon  $currentDate
     * @return array{from: Carbon, to: Carbon}
     */
    public function getWeekRange(Carbon $currentDate): array
    {
        return [
            'from' => $currentDate->copy()->startOfWeek(Carbon::MONDAY),
            'to' => $currentDate->copy()->endOfWeek(Carbon::SUNDAY),
        ];
    }

    /**
     * Grilla de dias del mes como Carbon. Empieza en lunes y
     * rellena hasta completar 6 semanas (42 celdas) para que la
     * UI mantenga siempre la misma altura. Esto evita "saltos"
     * al pasar de un mes de 4 a uno de 5 semanas.
     *
     * @param  Carbon  $currentDate
     * @return array<int, Carbon>
     */
    public function buildMonthDays(Carbon $currentDate): array
    {
        $range = $this->getMonthRange($currentDate);
        $days = [];
        $cursor = $range['from']->copy();

        while ($cursor->lessThanOrEqualTo($range['to'])) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Array de 7 Carbon con los dias de la semana (lunes a
     * domingo) que contiene la fecha de referencia.
     *
     * @param  Carbon  $currentDate
     * @return array<int, Carbon>
     */
    public function buildWeekDays(Carbon $currentDate): array
    {
        $range = $this->getWeekRange($currentDate);
        $days = [];
        $cursor = $range['from']->copy();

        for ($i = 0; $i < 7; $i++) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Agrupa eventos (o deadlines) por la clave de dia
     * (Y-m-d) para lookup O(1) en la vista. La agrupacion se
     * hace en PHP para mantener la flexibilidad: mezcla
     * eventos persistidos y virtuales en la misma estructura.
     *
     * @param  Collection<int, CalendarEvent|object>  $items
     * @return array<string, array<int, CalendarEvent|object>>
     */
    public function groupByDay(Collection $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $date = $item->starts_at;
            if ($date === null) {
                continue;
            }
            $key = $date->format('Y-m-d');
            $grouped[$key] ??= [];
            $grouped[$key][] = $item;
        }

        return $grouped;
    }

    /**
     * Lista de usuarios disponibles para invitar a un evento
     * del proyecto: union de miembros directos del proyecto y
     * miembros de la organizacion, deduplicados y ordenados por
     * nombre. Excluye al emisor para evitar invitarse a si
     * mismo.
     *
     * @param  Project  $project
     * @param  int|null  $excludeUserId  id a excluir (el admin actual)
     * @return Collection<int, \App\Models\User>
     */
    public function availableAttendees(Project $project, ?int $excludeUserId = null): Collection
    {
        $projectMembers = $project->members;
        $orgMembers = $project->organization?->members ?? collect();

        $all = $projectMembers->merge($orgMembers)
            ->unique('id')
            ->sortBy('name')
            ->values();

        if ($excludeUserId !== null) {
            $all = $all->reject(fn ($user) => $user->id === $excludeUserId)->values();
        }

        return $all;
    }
}
