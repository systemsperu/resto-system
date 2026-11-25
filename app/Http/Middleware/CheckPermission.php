<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Verifica una capacidad del usuario autenticado.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        abort_unless($user->hasPermission($permission), 403, 'No tienes permiso para realizar esta acción.');

        return $next($request);
    }
}
