<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard del portal de cliente.
 *
 * El cliente ve sus organizaciones y los proyectos recientes de
 * esas organizaciones que esten marcados como visibles. Las
 * tarjetas de tareas y mensajes se mantienen en cero hasta las
 * fases 3 y 5.
 */
class DashboardController extends Controller
{
    /**
     * Renderiza el dashboard del cliente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $organizations = $user->organizations()
            ->withCount(['members', 'pendingInvitations'])
            ->orderBy('name')
            ->get();

        $recentProjects = Project::query()
            ->visibleToClient()
            ->forUser($user)
            ->with('organization')
            ->latest()
            ->take(5)
            ->get();

        $activeProjectsCount = Project::query()
            ->visibleToClient()
            ->forUser($user)
            ->active()
            ->count();

        return view('portal.dashboard', [
            'user' => $user,
            'organizations' => $organizations,
            'recentProjects' => $recentProjects,
            'stats' => [
                'organizations' => $organizations->count(),
                'projects' => $activeProjectsCount,
                'tasks' => 0,
                'unread' => 0,
            ],
        ]);
    }
}
