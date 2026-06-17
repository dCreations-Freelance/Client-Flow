<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso al MCP server a usuarios administradores.
 *
 * Aunque la autenticacion via Sanctum ya identifica al usuario,
 * este middleware garantiza que solo los admins puedan usar las
 * tools de lectura del MCP, incluyendo documentos privados.
 */
class EnsureMcpAccess
{
    /**
     * Maneja la peticion entrante y rechaza a no administradores.
     *
     * @param  Request  $request
     * @param  \Closure(Request): Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== UserRole::Admin) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Acceso denegado al MCP server.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
