<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ClientInvitationController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectUpdateController;
use App\Http\Controllers\Admin\VisualEntryController as AdminVisualEntryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\InvitationAcceptanceController;
use App\Http\Controllers\Portal\ProjectTimelineController;
use App\Http\Controllers\Portal\VisualEntryController as PortalVisualEntryController;
use App\Http\Controllers\VisualEntryMediaController;
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

Route::get('/media/visual-entries/{visualEntry}', [VisualEntryMediaController::class, 'show'])
    ->middleware('auth')
    ->name('visual-entries.media');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', function () {
        $projects = Project::with(['client', 'updates' => fn ($query) => $query->latest('published_at')->latest()->take(1)])->latest()->take(5)->get();

        return view('admin.dashboard', compact('projects'));
    })->name('dashboard');

    Route::get('/clients/invite', [ClientInvitationController::class, 'create'])->name('clients.invite');
    Route::post('/clients/invite', [ClientInvitationController::class, 'store'])->name('clients.invite.store');
    Route::resource('clients', ClientController::class);

    Route::get('/projects/{project}/timeline', [ProjectUpdateController::class, 'index'])->name('projects.timeline');
    Route::get('/projects/{project}/updates/create', [ProjectUpdateController::class, 'create'])->name('projects.updates.create');
    Route::post('/projects/{project}/updates', [ProjectUpdateController::class, 'store'])->name('projects.updates.store');
    Route::get('/visual-diary', [AdminVisualEntryController::class, 'index'])->name('visual-entries.index');
    Route::get('/visual-diary/{visualEntry}', [AdminVisualEntryController::class, 'show'])->name('visual-entries.show');
    Route::get('/projects/{project}/visual-diary/create', [AdminVisualEntryController::class, 'create'])->name('projects.visual-entries.create');
    Route::post('/projects/{project}/visual-diary', [AdminVisualEntryController::class, 'store'])->name('projects.visual-entries.store');
    Route::resource('projects', ProjectController::class);
});

Route::middleware(['auth', 'client'])->prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/dashboard', function () {
        $projects = auth()->user()->client?->projects()
            ->with(['updates' => fn ($query) => $query->public()->latest('published_at')->latest()->take(1)])
            ->where('is_visible_to_client', true)
            ->latest()
            ->get() ?? collect();

        return view('portal.dashboard', compact('projects'));
    })->name('dashboard');

    Route::get('/projects/{project}/timeline', [ProjectTimelineController::class, 'show'])->name('projects.timeline');
    Route::get('/projects/{project}/visual-diary', [PortalVisualEntryController::class, 'index'])->name('projects.visual-entries.index');
    Route::get('/projects/{project}/visual-diary/{visualEntry}', [PortalVisualEntryController::class, 'show'])->name('projects.visual-entries.show');
});
