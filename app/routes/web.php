<?php

use App\Http\Controllers\Admin\BoardColumnController as AdminBoardColumnController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KanbanController as AdminKanbanController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationMemberController as AdminOrganizationMemberController;
use App\Http\Controllers\Admin\ProjectArchiveController as AdminProjectArchiveController;
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
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\KanbanController as PortalKanbanController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\ProjectDocumentController as PortalProjectDocumentController;
use App\Http\Controllers\Portal\ProjectMessageController as PortalProjectMessageController;
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

Route::get('invitation/{token}', [InvitationAcceptanceController::class, 'show'])->name('invitation.accept');
Route::post('invitation/{token}', [InvitationAcceptanceController::class, 'store']);

// ---------------------------------------------------------------------
// Autenticacion: solo accesibles por visitantes (`guest`)
// ---------------------------------------------------------------------

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('password/reset', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('password/email', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('password/reset/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('password/reset', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
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
    });
