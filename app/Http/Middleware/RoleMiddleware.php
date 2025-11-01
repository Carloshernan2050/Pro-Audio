<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $sessionRoles = session('roles');
        $currentRole = session('role');

        // Si no hay roles en sesión, entrar como Invitado por defecto
        if (empty($sessionRoles)) {
            $sessionRoles = ['Invitado'];
            session(['roles' => $sessionRoles, 'role' => 'Invitado']);
        }

        // Si no se especificaron roles permitidos, dejar pasar
        if (empty($allowedRoles)) {
            return $next($request);
        }

        // Normalizar a array
        $userRoles = is_array($sessionRoles) ? $sessionRoles : array_filter([$currentRole]);

        // Permitir si hay intersección
        $hasAccess = count(array_intersect($userRoles, $allowedRoles)) > 0;
        if (!$hasAccess) {
            return redirect()->route('inicio')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}


