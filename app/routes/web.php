<?php

use App\Http\Controllers\Admin\AiChatController as AdminAiChatController;
use App\Http\Controllers\Admin\AiConfigController as AdminAiConfigController;
use App\Http\Controllers\Admin\BoardColumnController as AdminBoardColumnController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KanbanController as AdminKanbanController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationMemberController as AdminOrganizationMemberController;
use App\Http\Controllers\Admin\ProjectArchiveController as AdminProjectArchiveController;
use App\Http\Controllers\Admin\ProjectCalendarController as AdminProjectCalendarController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ProjectDocumentController as AdminProjectDocumentController;
use App\Http\Controllers\Admin\ProjectMemberController as AdminProjectMemberController;
use App\Http\Controllers\Admin\ProjectMessageController as AdminProjectMessageController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\TaskMoveController as AdminTaskMoveController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InvitationAcceptanceController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Portal\AiChatController as PortalAiChatController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\KanbanController as PortalKanbanController;
use App\Http\Controllers\Portal\ProjectCalendarController as PortalProjectCalendarController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\ProjectDocumentController as PortalProjectDocumentController;
use App\Http\Controllers\Portal\ProjectMessageController as PortalProjectMessageController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Todas las rutas viven en `web.php` para conservar sesiones y CSRF. Se
| organizan en bloques: publicas, auth, panel admin, portal cliente.
| Los nombres siguen el patron `<zona>.<recurso>.<accion>`.
|
*/

// ---------------------------------------------------------------------
// Rutas publicas
// ---------------------------------------------------------------------

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('portal.dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('invitacion/{token}', [InvitationAcceptanceController::class, 'show'])->name('invitation.accept');
Route::post('invitacion/{token}', [InvitationAcceptanceController::class, 'store']);

// ---------------------------------------------------------------------
// PWA: manifest y service worker
// ---------------------------------------------------------------------
// Las dos rutas son publicas (sin middleware auth) porque el
// navegador necesita poder pedir el manifest y registrar el SW
// incluso antes de que el usuario haya iniciado sesion. Asi se
// puede "anadir a pantalla de inicio" desde la landing.

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])
    ->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])
    ->name('pwa.sw');

// ---------------------------------------------------------------------
// API interna: contadores para el polling de la PWA
// ---------------------------------------------------------------------
// Vive en `web.php` (no en `api.php`) para poder usar el
// middleware `auth` de sesion, que es el que aplica al resto de
// la app. El cliente lo invoca con `credentials: 'same-origin'`
// para que la cookie de sesion viaje automaticamente.

Route::middleware('auth')
    ->get('/api/notifications/unread-count', [NotificationsController::class, 'unreadCount'])
    ->name('api.notifications.unread-count');

// ---------------------------------------------------------------------
// Autenticacion: solo accesibles por visitantes (`guest`)
// ---------------------------------------------------------------------

Route::middleware('guest')->group(function (): void {
    Route::get('iniciar-sesion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('iniciar-sesion', [AuthenticatedSessionController::class, 'store']);

    Route::get('registro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('registro', [RegisteredUserController::class, 'store']);

    Route::get('recuperar-contrasena', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('recuperar-contrasena', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('recuperar-contrasena/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('recuperar-contrasena', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('cerrar-sesion', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------------------
// Panel de administracion (`/admin/*`)
// ---------------------------------------------------------------------

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Organizaciones
        Route::resource('organizations', AdminOrganizationController::class);
        Route::get('organizations/{organization}/members', [AdminOrganizationMemberController::class, 'index'])
            ->name('organizations.members');
        Route::post('organizations/{organization}/members', [AdminOrganizationMemberController::class, 'store'])
            ->name('organizations.members.store');
        Route::delete('organizations/{organization}/members/{user}', [AdminOrganizationMemberController::class, 'destroy'])
            ->name('organizations.members.destroy');

        // Proyectos
        Route::resource('projects', AdminProjectController::class);
        Route::post('projects/{project}/members', [AdminProjectMemberController::class, 'store'])
            ->name('projects.members.store');
        Route::delete('projects/{project}/members/{user}', [AdminProjectMemberController::class, 'destroy'])
            ->name('projects.members.destroy');
        Route::post('projects/{project}/archive', [AdminProjectArchiveController::class, 'archive'])
            ->name('projects.archive');
        Route::post('projects/{project}/unarchive', [AdminProjectArchiveController::class, 'unarchive'])
            ->name('projects.unarchive');

        // Kanban: tablero, columnas y tareas.
        Route::get('projects/{project}/board', [AdminKanbanController::class, 'index'])
            ->name('projects.board');

        Route::post('projects/{project}/columns', [AdminBoardColumnController::class, 'store'])
            ->name('projects.columns.store');
        Route::put('projects/{project}/columns/{column}', [AdminBoardColumnController::class, 'update'])
            ->name('projects.columns.update');
        Route::delete('projects/{project}/columns/{column}', [AdminBoardColumnController::class, 'destroy'])
            ->name('projects.columns.destroy');
        Route::post('projects/{project}/columns/reorder', [AdminBoardColumnController::class, 'reorder'])
            ->name('projects.columns.reorder');

        Route::post('projects/{project}/tasks', [AdminTaskController::class, 'store'])
            ->name('projects.tasks.store');
        Route::put('projects/{project}/tasks/{task}', [AdminTaskController::class, 'update'])
            ->name('projects.tasks.update');
        Route::delete('projects/{project}/tasks/{task}', [AdminTaskController::class, 'destroy'])
            ->name('projects.tasks.destroy');
        Route::patch('projects/{project}/tasks/{task}/move', [AdminTaskMoveController::class, 'update'])
            ->name('projects.tasks.move');
        Route::post('projects/{project}/tasks/{task}/complete', [AdminTaskController::class, 'complete'])
            ->name('projects.tasks.complete');
        Route::post('projects/{project}/tasks/{task}/reopen', [AdminTaskController::class, 'reopen'])
            ->name('projects.tasks.reopen');

        // Documentacion: CRUD de documentos del proyecto.
        // Las acciones del editor se hacen via componente Livewire
        // (POST PUT a los mismos endpoints).
        Route::get('projects/{project}/documents', [AdminProjectDocumentController::class, 'index'])
            ->name('projects.documents.index');
        Route::get('projects/{project}/documents/create', [AdminProjectDocumentController::class, 'create'])
            ->name('projects.documents.create');
        Route::post('projects/{project}/documents', [AdminProjectDocumentController::class, 'store'])
            ->name('projects.documents.store');
        Route::get('projects/{project}/documents/{document}', [AdminProjectDocumentController::class, 'show'])
            ->name('projects.documents.show');
        Route::get('projects/{project}/documents/{document}/edit', [AdminProjectDocumentController::class, 'edit'])
            ->name('projects.documents.edit');
        Route::put('projects/{project}/documents/{document}', [AdminProjectDocumentController::class, 'update'])
            ->name('projects.documents.update');
        Route::delete('projects/{project}/documents/{document}', [AdminProjectDocumentController::class, 'destroy'])
            ->name('projects.documents.destroy');

        // Chat del proyecto. El grueso de la interaccion se hace
        // via componente Livewire; el POST es para tests y como
        // fallback si Livewire falla.
        Route::get('projects/{project}/chat', [AdminProjectMessageController::class, 'index'])
            ->name('projects.chat');
        Route::post('projects/{project}/messages', [AdminProjectMessageController::class, 'store'])
            ->name('projects.chat.store');

        // Asistente IA por proyecto (admin usa su propio chat
        // de prueba en cada proyecto).
        Route::get('projects/{project}/ai', [AdminAiChatController::class, 'index'])
            ->name('projects.ai');
        Route::get('projects/{project}/ai/sessions/{session}', [AdminAiChatController::class, 'show'])
            ->name('projects.ai.show');
        Route::delete('projects/{project}/ai/sessions/{session}', [AdminAiChatController::class, 'destroy'])
            ->name('projects.ai.destroy');

        // Configuracion global de IA.
        Route::get('settings/ai', [AdminAiConfigController::class, 'edit'])
            ->name('ai.config.edit');
        Route::put('settings/ai', [AdminAiConfigController::class, 'update'])
            ->name('ai.config.update');
        Route::post('settings/ai/test', [AdminAiConfigController::class, 'test'])
            ->name('ai.config.test');

        // Calendario del proyecto. Toda la interaccion (navegacion,
        // modal, crear/editar/eliminar) se hace via componente
        // Livewire; este endpoint solo renderiza la vista.
        Route::get('projects/{project}/calendar', [AdminProjectCalendarController::class, 'index'])
            ->name('projects.calendar');
    });

// ---------------------------------------------------------------------
// Portal de cliente (`/portal/*`)
// ---------------------------------------------------------------------

Route::middleware(['auth', 'client'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('portal.dashboard'));
        Route::get('dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('organizations/{organization}', [PortalProjectController::class, 'showOrganization'])
            ->name('organizations.show');

        Route::get('projects', [PortalProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/board', [PortalKanbanController::class, 'index'])
            ->name('projects.board');
        Route::get('projects/{project}/tasks/{task}', [PortalKanbanController::class, 'showTask'])
            ->name('projects.tasks.show');

        // Documentacion publica del proyecto: solo documentos con
        // `visibility = public` y proyectos visibles al cliente.
        Route::get('projects/{project}/documents', [PortalProjectDocumentController::class, 'index'])
            ->name('projects.documents.index');
        Route::get('projects/{project}/documents/{document}', [PortalProjectDocumentController::class, 'show'])
            ->name('projects.documents.show');

        // Chat del proyecto (cliente).
        Route::get('projects/{project}/chat', [PortalProjectMessageController::class, 'index'])
            ->name('projects.chat');
        Route::post('projects/{project}/messages', [PortalProjectMessageController::class, 'store'])
            ->name('projects.chat.store');

        // Asistente IA por proyecto (portal cliente).
        Route::get('projects/{project}/ai', [PortalAiChatController::class, 'index'])
            ->name('projects.ai');
        Route::post('projects/{project}/ai/sessions', [PortalAiChatController::class, 'store'])
            ->name('projects.ai.sessions.store');
        Route::get('projects/{project}/ai/sessions/{session}', [PortalAiChatController::class, 'show'])
            ->name('projects.ai.show');
        Route::delete('projects/{project}/ai/sessions/{session}', [PortalAiChatController::class, 'destroy'])
            ->name('projects.ai.destroy');

        // Calendario del proyecto (cliente, solo lectura).
        // El mismo componente Livewire se monta con readOnly=true.
        Route::get('projects/{project}/calendar', [PortalProjectCalendarController::class, 'index'])
            ->name('projects.calendar');
    });
