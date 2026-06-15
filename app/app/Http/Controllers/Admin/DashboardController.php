<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard del panel de administracion.
 *
 * Muestra tarjetas con contadores agregados, una lista de las
 * organizaciones mas recientes y otra de los proyectos mas
 * recientes. Los huecos para tareas y mensajes se mantienen en
 * cero hasta que lleguen las fases 3 y 5.
 */
class DashboardController extends Controller
{
    /**
     * Renderiza el dashboard del administrador.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $organizationsCount = Organization::count();
        $activeOrganizationsCount = Organization::active()->count();
        $pendingInvitationsCount = Organization::query()
            ->withCount(['pendingInvitations'])
            ->get()
            ->sum('pending_invitations_count');

        $projectsCount = Project::count();
        $activeProjectsCount = Project::active()->count();

        $recentOrganizations = Organization::query()
            ->withCount('members')
            ->latest()
            ->take(5)
            ->get();

        $recentProjects = Project::query()
            ->with('organization')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'organizations' => $organizationsCount,
                'activeOrganizations' => $activeOrganizationsCount,
                'pendingInvitations' => $pendingInvitationsCount,
                'projects' => $projectsCount,
                'activeProjects' => $activeProjectsCount,
                'tasks' => 0,
            ],
            'recentOrganizations' => $recentOrganizations,
            'recentProjects' => $recentProjects,
        ]);
    }
}
