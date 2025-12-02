<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tests Feature para RoleMiddleware
 */
class RoleMiddlewareTest extends TestCase
{
    // ============================================
    // TESTS PARA Middleware con roles vacíos
    // ============================================

    public function test_middleware_pasa_cuando_no_hay_roles_permitidos(): void
    {
        // Crear una ruta temporal que use el middleware sin roles
        Route::middleware(RoleMiddleware::class)->get('/test-middleware', function () {
            return response()->json(['success' => true]);
        });

        session(['roles' => ['Admin'], 'role' => 'Admin']);

        $response = $this->get('/test-middleware');

        // Debería pasar porque no hay roles permitidos especificados (línea 24)
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_middleware_pasa_cuando_allowed_roles_vacio(): void
    {
        // El middleware debería pasar cuando $allowedRoles está vacío
        $middleware = new RoleMiddleware;
        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return response()->json(['success' => true]);
        };

        session(['roles' => ['Admin'], 'role' => 'Admin']);

        $response = $middleware->handle($request, $next);

        // Debería pasar (línea 24)
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_funciona_con_sesion_vacia(): void
    {
        // Cuando no hay roles en sesión, debería asignar Invitado
        $middleware = new RoleMiddleware;
        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return response()->json(['success' => true]);
        };

        // No establecer roles en sesión

        $response = $middleware->handle($request, $next);

        // Debería asignar Invitado por defecto y pasar si no hay roles permitidos
        $this->assertTrue(session()->has('roles'));
        $this->assertEquals(['Invitado'], session('roles'));
    }
}

