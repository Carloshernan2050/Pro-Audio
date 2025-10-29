<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $currentRole = session('role');

        if ($currentRole === null) {
            return redirect()->route('role.select')->with('error', 'Selecciona un rol para continuar.');
        }

        if (!empty($allowedRoles) && !in_array($currentRole, $allowedRoles, true)) {
            return redirect()->route('role.select')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}


