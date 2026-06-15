<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard del portal de cliente.
 *
 * El cliente ve unicamente las organizaciones donde es miembro. En fase 1
 * la vista muestra el total de organizaciones y la lista de tarjetas
 * para cada una. Los proyectos y el chat se anadiran en fases 2 y 5.
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
            ->wherePivot('role', '!=', null)
            ->orderBy('name')
            ->get();

        return view('portal.dashboard', [
            'user' => $user,
            'organizations' => $organizations,
            'stats' => [
                'organizations' => $organizations->count(),
                'projects' => 0,
                'tasks' => 0,
                'unread' => 0,
            ],
        ]);
    }
}
