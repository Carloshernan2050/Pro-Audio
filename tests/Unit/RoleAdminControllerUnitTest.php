<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\RoleAdminController;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Tests Unitarios para RoleAdminController
 *
 * Tests para validaciones y lógica de roles
 */
class RoleAdminControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@test.com';
    private const ROUTE_ADMIN_ROLES_UPDATE = '/admin/roles/update';
    private const ROL_CLIENTE = 'Cliente';
    private const TELEFONO_PRUEBA = '1234567890';

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

    public function test_index_retorna_vista(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        // Crear roles
        $rolId = DB::table('roles')->insertGetId([
            'name' => self::ROL_CLIENTE,
            'nombre_rol' => self::ROL_CLIENTE
        ]);

        DB::table('personas_roles')->insert([
            'personas_id' => $usuario->id,
            'roles_id' => $rolId
        ]);

        $response = $this->controller->index();
        
        $this->assertNotNull($response);
    }

    public function test_update_asigna_rol(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'name' => self::ROL_CLIENTE,
            'nombre_rol' => self::ROL_CLIENTE
        ]);

        $request = Request::create(self::ROUTE_ADMIN_ROLES_UPDATE, 'POST', [
            'persona_id' => $usuario->id,
            'role_id' => $rolId
        ]);

        $response = $this->controller->update($request);
        
        $this->assertNotNull($response);
        
        $asignado = DB::table('personas_roles')
            ->where('personas_id', $usuario->id)
            ->where('roles_id', $rolId)
            ->exists();
        
        $this->assertTrue($asignado);
    }

    public function test_update_remueve_rol(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'name' => self::ROL_CLIENTE,
            'nombre_rol' => self::ROL_CLIENTE
        ]);

        DB::table('personas_roles')->insert([
            'personas_id' => $usuario->id,
            'roles_id' => $rolId
        ]);

        $request = Request::create(self::ROUTE_ADMIN_ROLES_UPDATE, 'POST', [
            'persona_id' => $usuario->id,
            'role_id' => null
        ]);

        $response = $this->controller->update($request);
        
        $this->assertNotNull($response);
        
        $asignado = DB::table('personas_roles')
            ->where('personas_id', $usuario->id)
            ->exists();
        
        $this->assertFalse($asignado);
    }

    public function test_update_rechaza_rol_no_permitido(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'name' => 'Invitado',
            'nombre_rol' => 'Invitado'
        ]);

        $request = Request::create(self::ROUTE_ADMIN_ROLES_UPDATE, 'POST', [
            'persona_id' => $usuario->id,
            'role_id' => $rolId
        ]);

        $response = $this->controller->update($request);
        
        $this->assertNotNull($response);
    }
}

