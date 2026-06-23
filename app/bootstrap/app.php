<?php

use App\Console\Commands\NotificationsDailyDigest;
use App\Console\Commands\NotificationsTaskDueSoon;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsClient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aliases de middleware por rol. Se usan como `->middleware('admin')`
        // y `->middleware('client')` en `routes/web.php` para proteger los
        // grupos de rutas administrativas y de portal respectivamente.
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'client' => EnsureUserIsClient::class,
        ]);
    })
    ->withCommands([
        // Listado explicito de comandos propios para que esten
        // disponibles aunque `bootstrap/app.php` no recorra
        // automaticamente `app/Console/Commands`. Laravel 12 ya
        // los descubre por convencion, pero listarlos aqui hace
        // que un `php artisan list` muestre las descripciones
        // sin necesidad de cachear el comando.
    ])
    ->withSchedule(function (Schedule $schedule): void {
        // Resumen diario: a las 09:00 hora local del servidor.
        // Por defecto se envia un unico email por usuario al dia
        // (salvaguarda de 18h en el propio comando). Para activar
        // el scheduler real, el admin tiene que anadir una entrada
        // cron: `* * * * * php artisan schedule:run >> /dev/null 2>&1`.
        $schedule->command(NotificationsDailyDigest::class)
            ->dailyAt('09:00')
            ->name('notifications-daily-digest')
            ->withoutOverlapping();

        // Recordatorios de deadline: a las 08:00 una vez al dia.
        // Las tareas que vencen "hoy" se notifican a primera hora;
        // las que vencen en 1-3 dias tambien. El sello
        // `last_due_notification_at` evita duplicados.
        $schedule->command(NotificationsTaskDueSoon::class)
            ->dailyAt('08:00')
            ->name('notifications-task-due-soon')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
