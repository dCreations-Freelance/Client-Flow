<?php

namespace App\Console\Commands;

use App\Enums\NotificationEvent;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DailyDigest;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Envia el resumen diario por email a los usuarios que lo tengan
 * activado.
 *
 * El resumen incluye:
 * - Proyectos activos en los que el usuario es miembro o
 *   pertenece a la organizacion.
 * - Tareas pendientes asignadas al usuario, con su `due_date`.
 * - Proximos eventos del calendario (7 dias) en los que el
 *   usuario es asistente.
 * - Total de mensajes no leidos en chats de proyectos visibles.
 *
 * El opt-out se gestiona por la preferencia `DailyDigest.email`
 * del usuario (defaults: true). Ademas se aplica una salvaguarda
 * de 18h: si el usuario ya recibio el digest hoy (segun el sello
 * `last_digest_sent_at` en `users`), no se envia de nuevo. Asi
 * aunque el cron corra varias veces al dia, el usuario no recibe
 * duplicados.
 *
 * El comando NO se ejecuta solo: el admin tiene que programarlo
 * con cron externo o activando el scheduler en `bootstrap/app.php`.
 */
class NotificationsDailyDigest extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notifications:daily-digest
        {--dry-run : Muestra lo que se enviaria sin enviar ni actualizar last_digest_sent_at}';

    /**
     * @var string
     */
    protected $description = 'Envia el resumen diario por email a los usuarios que lo tengan activado';

    /**
     * Ventana anti-duplicados: 18 horas. Es menos estricta que un
     * dia completo para que un cron corrido tarde (por ejemplo a
     * las 14:00 en vez de a las 09:00) siga sin duplicar al dia
     * siguiente por la madrugada.
     */
    private const DUPLICATE_WINDOW_HOURS = 18;

    /**
     * @return int
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Cargamos todos los usuarios con preferencia email activa
        // para DailyDigest y sin sello reciente. La consulta se
        // hace en dos pasos para mantener la condicion del sello
        // clara: la preferencia es "esta activa" y el sello "no es
        // reciente". Mezclar ambos en un solo where complica el
        // SQL sin aportar rendimiento.
        $threshold = now()->subHours(self::DUPLICATE_WINDOW_HOURS);

        $users = User::query()
            ->whereHas('notificationPreferences', function ($q): void {
                $q->where('event', NotificationEvent::DailyDigest->value)
                    ->where('email', true);
            })
            ->where(function ($q) use ($threshold): void {
                $q->whereNull('last_digest_sent_at')
                    ->orWhere('last_digest_sent_at', '<', $threshold);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No hay usuarios a los que enviar el resumen hoy.');

            return self::SUCCESS;
        }

        $this->line(sprintf('Enviando resumen diario a %d usuario(s).', $users->count()));

        $sent = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $payload = $this->buildDigestPayload($user);

            $this->line(sprintf(
                '- %s: %d proyecto(s), %d tarea(s), %d evento(s), %d mensaje(s) sin leer',
                $user->email,
                $payload['projects']->count(),
                $payload['pendingTasks']->count(),
                $payload['upcomingEvents']->count(),
                $payload['unreadMessages'],
            ));

            if ($dryRun) {
                continue;
            }

            $ok = NotificationDispatcher::dispatchToAddress(
                $user->email,
                $user->name,
                new DailyDigest(
                    $user,
                    $payload['projects'],
                    $payload['pendingTasks'],
                    $payload['upcomingEvents'],
                    $payload['unreadMessages'],
                ),
            );

            // `dispatchToAddress` no respeta preferencias (es email
            // puro), asi que siempre devuelve true. Aun asi dejamos
            // la condicion para mantener simetria con el otro
            // dispatcher y poder cambiarlo en una fase futura.
            if ($ok) {
                $user->forceFill(['last_digest_sent_at' => now()])->save();
                $sent++;
            } else {
                $skipped++;
            }
        }

        $this->info(sprintf('Resúmenes enviados: %d. Omitidos: %d.', $sent, $skipped));

        return self::SUCCESS;
    }

    /**
     * Calcula el contenido del resumen para un usuario. Las cuatro
     * queries son pequenas (los filtros limitan drasticamente el
     * universo) y se hacen una sola vez por usuario; un usuario
     * con muchos proyectos no dispara N+1.
     *
     * @return array{projects: Collection, pendingTasks: Collection, upcomingEvents: Collection, unreadMessages: int}
     */
    private function buildDigestPayload(User $user): array
    {
        // Proyectos activos: el admin ve todo; el cliente ve los
        // proyectos de sus organizaciones y visibles.
        if ($user->isAdmin()) {
            $projects = Project::query()
                ->where('status', '!=', 'archived')
                ->orderBy('name')
                ->get();
        } else {
            $projectIds = $user->organizations()
                ->with(['projects' => function ($q): void {
                    $q->where('is_visible_to_client', true)
                        ->whereNull('archived_at');
                }])
                ->get()
                ->pluck('projects')
                ->flatten()
                ->pluck('id')
                ->unique();

            $projects = Project::query()
                ->whereIn('id', $projectIds)
                ->orderBy('name')
                ->get();
        }

        // Tareas pendientes asignadas al usuario. Limitamos a 20
        // para que el email no se haga eterno si alguien tiene
        // mucha carga.
        $pendingTasks = Task::query()
            ->whereNull('parent_id')
            ->where('assignee_id', $user->id)
            ->whereNull('completed_at')
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        // Proximos eventos del calendario (7 dias) donde el usuario
        // es asistente. Se hace una sola query con join al pivot.
        $upcomingEvents = CalendarEvent::query()
            ->whereHas('attendees', function ($q) use ($user): void {
                $q->where('users.id', $user->id);
            })
            ->whereBetween('starts_at', [now(), now()->addDays(7)])
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        // Mensajes no leidos en chats visibles al usuario. Replica
        // la logica del sidebar: para cada proyecto con marcador,
        // cuenta los mensajes con id mayor al last_read.
        $unreadMessages = $this->countUnreadMessages($user);

        return [
            'projects' => $projects,
            'pendingTasks' => $pendingTasks,
            'upcomingEvents' => $upcomingEvents,
            'unreadMessages' => $unreadMessages,
        ];
    }

    /**
     * Cuenta mensajes de chat no leidos en los proyectos visibles
     * al usuario. Misma logica que `NotificationsController::countUnreadMessages`
     * (version portal). Se duplica en lugar de extraer a un
     * servicio porque son menos de 30 lineas y mantener un
     * servicio compartido acoplaria el comando a un controller.
     */
    private function countUnreadMessages(User $user): int
    {
        $reads = \App\Models\ProjectChatRead::query()
            ->where('user_id', $user->id)
            ->pluck('last_read_message_id', 'project_id');

        if ($reads->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($reads as $projectId => $lastRead) {
            // Clientes: solo cuentan mensajes de proyectos visibles.
            if (! $user->isAdmin()) {
                $project = Project::find($projectId);
                if ($project === null || ! $project->isVisibleToClient()) {
                    continue;
                }
            }

            $total += \App\Models\ProjectMessage::query()
                ->where('project_id', $projectId)
                ->where('id', '>', (int) $lastRead)
                ->count();
        }

        return $total;
    }
}
