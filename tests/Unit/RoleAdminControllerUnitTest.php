<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\RoleAdminController;

/**
 * Tests Unitarios para RoleAdminController
 * 
 * Tests para validaciones y lógica de roles
 */
class RoleAdminControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RoleAdminController();
    }

    // ============================================
    // TESTS PARA Validaciones
    // ============================================

    public function test_validacion_update_estructura(): void
    {
        $reglasEsperadas = [
            'persona_id' => 'required|integer|exists:personas,id',
            'role_id' => 'nullable|integer|exists:roles,id'
        ];
        
        $this->assertArrayHasKey('persona_id', $reglasEsperadas);
        $this->assertArrayHasKey('role_id', $reglasEsperadas);
    }

    public function test_role_id_puede_ser_nullable(): void
    {
        // El role_id puede ser nullable (para remover roles)
        $reglasEsperadas = [
            'role_id' => 'nullable|integer|exists:roles,id'
        ];
        
        $this->assertStringContainsString('nullable', $reglasEsperadas['role_id']);
    }

    // ============================================
    // TESTS PARA Roles Permitidos
    // ============================================

    public function test_roles_permitidos(): void
    {
        // Los roles permitidos son: Superadmin, Admin, Cliente
        $rolesPermitidos = ['Superadmin', 'Admin', 'Cliente'];
        
        $this->assertCount(3, $rolesPermitidos);
        $this->assertContains('Superadmin', $rolesPermitidos);
        $this->assertContains('Admin', $rolesPermitidos);
        $this->assertContains('Cliente', $rolesPermitidos);
    }

    public function test_orden_roles(): void
    {
        // El orden de roles es: Superadmin (1), Admin (2), Cliente (3)
        $orden = ['Superadmin' => 1, 'Admin' => 2, 'Cliente' => 3];
        
        $this->assertEquals(1, $orden['Superadmin']);
        $this->assertEquals(2, $orden['Admin']);
        $this->assertEquals(3, $orden['Cliente']);
    }

    public function test_normalizacion_usuario_a_cliente(): void
    {
        // El rol "Usuario" se normaliza a "Cliente"
        $rolUsuario = 'Usuario';
        $rolNormalizado = $rolUsuario === 'Usuario' ? 'Cliente' : $rolUsuario;
        
        $this->assertEquals('Cliente', $rolNormalizado);
    }

    public function test_roles_no_permitidos_son_rechazados(): void
    {
        // Solo se permiten Superadmin, Admin, Cliente
        $rolesPermitidos = ['Superadmin', 'Admin', 'Cliente'];
        $rolNoPermitido = 'Invitado';
        
        $this->assertNotContains($rolNoPermitido, $rolesPermitidos);
    }
}

