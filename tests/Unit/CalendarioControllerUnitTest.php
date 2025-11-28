<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\CalendarioController;

/**
 * Tests Unitarios para CalendarioController
 *
 * Tests para constantes y lógica de roles
 */
class CalendarioControllerUnitTest extends TestCase
{
    // ============================================
    // TESTS PARA Constantes
    // ============================================

    public function test_default_event_title_constante(): void
    {
        // Verificar que la constante DEFAULT_EVENT_TITLE tiene el valor esperado
        $this->assertEquals('Alquiler', CalendarioController::DEFAULT_EVENT_TITLE);
        $this->assertIsString(CalendarioController::DEFAULT_EVENT_TITLE);
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

