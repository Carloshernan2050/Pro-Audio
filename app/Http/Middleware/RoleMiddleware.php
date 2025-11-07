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

        $normalize = static function (string $role): string {
            $map = [
                'Administrador' => 'Admin',
                'administrador' => 'Admin',
                'Administrador ' => 'Admin',
                'Cliente' => 'Usuario',
                'cliente' => 'Usuario',
            ];
            $trimmed = trim($role);
            return $map[$trimmed] ?? $map[strtolower($trimmed)] ?? $trimmed;
        };

        $normalizedUserRoles = array_unique(array_merge(
            $userRoles,
            array_map($normalize, $userRoles)
        ));

        $normalizedAllowedRoles = array_unique(array_merge(
            $allowedRoles,
            array_map($normalize, $allowedRoles)
        ));

        // Permitir si hay intersección
        $hasAccess = count(array_intersect($normalizedUserRoles, $normalizedAllowedRoles)) > 0;
        if (!$hasAccess) {
            return redirect()->route('inicio')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}


