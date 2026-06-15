<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que el usuario autenticado tenga rol de cliente.
 *
 * Mantiene la simetria con `EnsureUserIsAdmin`. Si un admin intenta entrar
 * al portal de un cliente (por enlace antiguo, marcador o error) se le
 * redirige a su propio panel en lugar de devolver un 403, manteniendo
 * el principio de "guiar al usuario a donde tiene sentido".
 */
class EnsureUserIsClient
{
    /**
     * Maneja la peticion entrante.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->isClient()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
