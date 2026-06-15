<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que el usuario autenticado tenga rol de administrador.
 *
 * Si el usuario no esta autenticado se delega en el middleware `auth`
 * (que redirige al login). Si esta autenticado pero no es admin, se le
 * envia a su dashboard correspondiente en lugar de mostrar un 403, porque
 * es una situacion esperada al equivocarse de zona y queremos guiarle
 * a donde tiene acceso.
 */
class EnsureUserIsAdmin
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

        if (! $user->isAdmin()) {
            // El usuario ya esta autenticado, pero intenta entrar al panel
            // de administracion. Lo mas amable es redirigirlo a su portal.
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
