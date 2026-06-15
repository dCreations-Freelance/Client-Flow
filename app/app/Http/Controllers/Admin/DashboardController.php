<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard del panel de administracion.
 *
 * Muestra tarjetas con contadores agregados y una lista de las
 * organizaciones mas recientes. Los proyectos y tareas se anadiran
 * en fases siguientes, asi que en fase 1 los huecos quedan con valor
 * cero explicito.
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

        $recentOrganizations = Organization::query()
            ->withCount('members')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'organizations' => $organizationsCount,
                'activeOrganizations' => $activeOrganizationsCount,
                'pendingInvitations' => $pendingInvitationsCount,
                'projects' => 0,
                'tasks' => 0,
            ],
            'recentOrganizations' => $recentOrganizations,
        ]);
    }
}
