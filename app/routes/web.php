<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ClientInvitationController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\InvitationAcceptanceController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->role === UserRole::Admin ? 'admin.dashboard' : 'portal.dashboard');
    }

    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/invitation/{token}', [InvitationAcceptanceController::class, 'create'])->name('invitation.accept');
    Route::post('/invitation/{token}', [InvitationAcceptanceController::class, 'store'])->name('invitation.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', function () {
        $projects = Project::with('client')->latest()->take(5)->get();

        return view('admin.dashboard', compact('projects'));
    })->name('dashboard');

    Route::get('/clients/invite', [ClientInvitationController::class, 'create'])->name('clients.invite');
    Route::post('/clients/invite', [ClientInvitationController::class, 'store'])->name('clients.invite.store');
    Route::resource('clients', ClientController::class);

    Route::resource('projects', ProjectController::class);
});

Route::middleware(['auth', 'client'])->prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/dashboard', function () {
        $projects = auth()->user()->client?->projects()
            ->where('is_visible_to_client', true)
            ->latest()
            ->get() ?? collect();

        return view('portal.dashboard', compact('projects'));
    })->name('dashboard');
});
