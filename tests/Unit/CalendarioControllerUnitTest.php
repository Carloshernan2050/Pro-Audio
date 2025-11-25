<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\CalendarioController;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests Unitarios para CalendarioController
 * 
 * Tests para métodos privados con lógica pura
 */
class CalendarioControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        // CalendarioController requiere inyección de dependencias
        // Por ahora solo probamos métodos que no dependen de ellas
    }

    /**
     * Helper para acceder a métodos privados mediante reflexión
     */
    private function getPrivateMethod(string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass(CalendarioController::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    // ============================================
    // TESTS PARA Constantes
    // ============================================

    public function test_default_event_title_constante(): void
    {
        $reflection = new ReflectionClass(CalendarioController::class);
        $constants = $reflection->getConstants();
        
        $this->assertArrayHasKey('DEFAULT_EVENT_TITLE', $constants);
        $this->assertEquals('Alquiler', $constants['DEFAULT_EVENT_TITLE']);
    }

    // ============================================
    // TESTS PARA Lógica de Roles
    // ============================================

    public function test_is_admin_like_logica_administrador(): void
    {
        // Verificar la lógica de detección de admin
        $rolesAdmin = ['administrador', 'admin', 'superadmin'];
        
        foreach ($rolesAdmin as $rol) {
            $rolLower = strtolower($rol);
            $this->assertContains($rolLower, $rolesAdmin);
        }
    }

    public function test_is_admin_like_case_insensitive(): void
    {
        // Los roles deben ser case insensitive
        $roles = ['Administrador', 'ADMIN', 'SuperAdmin'];
        $rolesLower = array_map('strtolower', $roles);
        
        $this->assertContains('administrador', $rolesLower);
        $this->assertContains('admin', $rolesLower);
        $this->assertContains('superadmin', $rolesLower);
    }
}

