<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationMemberController as AdminOrganizationMemberController;
use App\Http\Controllers\Admin\ProjectArchiveController as AdminProjectArchiveController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ProjectMemberController as AdminProjectMemberController;
use App\Http\Controllers\Admin\ProjectProgressController as AdminProjectProgressController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InvitationAcceptanceController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
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

// Aceptacion de invitacion: vive fuera de `guest` porque si el usuario
// ya esta logueado, aceptamos la invitacion directamente.
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

// Logout: solo accesible para usuarios autenticados.
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

        // Miembros de proyecto (rutas anidadas).
        Route::post('projects/{project}/members', [AdminProjectMemberController::class, 'store'])
            ->name('projects.members.store');
        Route::delete('projects/{project}/members/{user}', [AdminProjectMemberController::class, 'destroy'])
            ->name('projects.members.destroy');

        // Archivado y progreso: endpoints dedicados para mantener
        // los formularios pequenos y la logica aislada.
        Route::post('projects/{project}/archive', [AdminProjectArchiveController::class, 'archive'])
            ->name('projects.archive');
        Route::post('projects/{project}/unarchive', [AdminProjectArchiveController::class, 'unarchive'])
            ->name('projects.unarchive');
        Route::patch('projects/{project}/progress', [AdminProjectProgressController::class, 'update'])
            ->name('projects.progress.update');
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

        // Organizaciones: el cliente ve la lista desde el dashboard y
        // puede entrar al detalle para explorar los proyectos.
        Route::get('organizations/{organization}', [PortalProjectController::class, 'showOrganization'])
            ->name('organizations.show');

        // Proyectos
        Route::get('projects', [PortalProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
    });
