<?php

namespace App\Console\Commands;

use App\Enums\NotificationEvent;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueSoon;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Envia recordatorios de "deadline cercano" a los usuarios con
 * tareas que vencen en los proximos dias.
 *
 * Comportamiento:
 * - Detecta tareas con `due_date` en el rango [hoy, hoy+3 dias],
 *   no completadas y con `assignee_id` no nulo.
 * - Filtra las que ya recibieron un recordatorio en las ultimas
 *   24h usando el sello `last_due_notification_at`.
 * - Despacha la notificacion `TaskDueSoon` al assignee, pasando
 *   por el `NotificationDispatcher` para respetar las
 *   preferencias por canal.
 * - Sella `last_due_notification_at` solo si el envio tuvo exito,
 *   para no perder avisos si el opt-out esta activo.
 *
 * El comando NO se ejecuta solo: el admin tiene que programarlo
 * con cron externo (o usar `php artisan schedule:run` si activa
 * el scheduler en `bootstrap/app.php`).
 */
class NotificationsTaskDueSoon extends Command
{
    /**
     * Nombre y argumentos del comando.
     *
     * @var string
     */
    protected $signature = 'notifications:task-due-soon
                            {--days=3 : Ventana de dias hacia adelante para considerar una tarea como "deadline cercano"}
                            {--dry-run : Muestra lo que se enviaria sin enviar nada ni sellar last_due_notification_at}';

    /**
     * Descripcion visible en `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Envia recordatorios de deadline a los assignees de tareas que vencen pronto';

    /**
     * Logica principal. Devuelve el codigo de salida estandar de
     * Symfony (0 ok, 1 error) para integrarse bien con cron.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $today = Carbon::today();
        $until = $today->copy()->addDays($days);

        // Tareas candidatas: raiz (no subtareas) y con fecha limite
        // en la ventana. Excluimos las que ya recibieron un
        // recordatorio en las ultimas 24h para no spamear.
        $tasks = Task::query()
            ->whereNull('parent_id')
            ->whereNotNull('assignee_id')
            ->whereNotNull('due_date')
            ->whereNull('completed_at')
            ->whereBetween('due_date', [$today->toDateString(), $until->toDateString()])
            ->where(function ($q) use ($today): void {
                $q->whereNull('last_due_notification_at')
                    ->orWhere('last_due_notification_at', '<', $today);
            })
            ->with(['project', 'assignee'])
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No hay tareas con deadline proximo para avisar.');

            return self::SUCCESS;
        }

        $this->line(sprintf('Encontradas %d tarea(s) con deadline proximo.', $tasks->count()));

        $sent = 0;
        $skipped = 0;

        foreach ($tasks as $task) {
            /** @var Task $task */
            $assignee = $task->assignee;

            // Defensa en profundidad: si por algun motivo el
            // assignee no existe (borrado con cascade raro), no
            // intentamos enviar.
            if (! $assignee instanceof User) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '- Tarea #%d "%s" (vence %s) -> %s',
                $task->id,
                $task->title,
                $task->due_date->format('d/m/Y'),
                $assignee->email,
            ));

            if ($dryRun) {
                continue;
            }

            $ok = NotificationDispatcher::dispatch(
                $assignee,
                new TaskDueSoon($task, $task->project),
                NotificationEvent::TaskDueSoon,
            );

            if ($ok) {
                $task->forceFill(['last_due_notification_at' => now()])->save();
                $sent++;
            } else {
                $skipped++;
            }
        }

        $this->info(sprintf('Recordatorios enviados: %d. Omitidos (opt-out o sin assignee): %d.', $sent, $skipped));

        return self::SUCCESS;
    }
}
