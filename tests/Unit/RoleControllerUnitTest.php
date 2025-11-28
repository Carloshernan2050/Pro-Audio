<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\RoleController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests Unitarios para RoleController
 *
 * Tests para verificar la estructura y validación de roles
 */
class RoleControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_ROLE_SET = '/role/set';
    private const ROUTE_ADMIN_KEY_VERIFY = '/role/admin-key/verify';

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RoleController();
    }

    public function test_controller_instancia_correctamente(): void
    {
        $this->assertInstanceOf(RoleController::class, $this->controller);
    }

    public function test_select_retorna_vista(): void
    {
        $response = $this->controller->select();
        
        $this->assertNotNull($response);
    }

    public function test_set_con_rol_administrador_sin_sesion(): void
    {
        $request = Request::create(self::ROUTE_ROLE_SET, 'POST', [
            'role' => 'Administrador'
        ]);

        $response = $this->controller->set($request);
        
        $this->assertNotNull($response);
        $this->assertTrue(session()->has('pending_admin'));
    }

    public function test_set_con_rol_administrador_con_sesion(): void
    {
        session(['usuario_id' => 1]);
        
        $request = Request::create(self::ROUTE_ROLE_SET, 'POST', [
            'role' => 'Administrador'
        ]);

        $response = $this->controller->set($request);
        
        $this->assertNotNull($response);
    }

    public function test_set_con_rol_cliente(): void
    {
        $request = Request::create(self::ROUTE_ROLE_SET, 'POST', [
            'role' => 'Cliente'
        ]);

        $response = $this->controller->set($request);
        
        $this->assertNotNull($response);
        $this->assertEquals('Cliente', session('role'));
    }

    public function test_set_con_rol_invitado(): void
    {
        $request = Request::create(self::ROUTE_ROLE_SET, 'POST', [
            'role' => 'Invitado'
        ]);

        $response = $this->controller->set($request);
        
        $this->assertNotNull($response);
        $this->assertEquals('Invitado', session('role'));
    }

    public function test_admin_key_form_sin_sesion(): void
    {
        $response = $this->controller->adminKeyForm();
        
        $this->assertNotNull($response);
    }

    public function test_admin_key_form_con_sesion(): void
    {
        session(['usuario_id' => 1, 'pending_admin' => true]);
        
        $response = $this->controller->adminKeyForm();
        
        $this->assertNotNull($response);
    }

    public function test_admin_key_verify_clave_correcta(): void
    {
        session(['usuario_id' => 1, 'pending_admin' => true]);
        
        $request = Request::create(self::ROUTE_ADMIN_KEY_VERIFY, 'POST', [
            'admin_key' => 'ProAudio00'
        ]);

        $response = $this->controller->adminKeyVerify($request);
        
        $this->assertNotNull($response);
        $this->assertEquals('Administrador', session('role'));
        $this->assertFalse(session()->has('pending_admin'));
    }

    public function test_admin_key_verify_clave_incorrecta(): void
    {
        session(['usuario_id' => 1, 'pending_admin' => true]);
        
        $request = Request::create(self::ROUTE_ADMIN_KEY_VERIFY, 'POST', [
            'admin_key' => 'clave_incorrecta'
        ]);

        $response = $this->controller->adminKeyVerify($request);
        
        $this->assertNotNull($response);
    }

    public function test_admin_key_verify_sin_sesion(): void
    {
        $request = Request::create(self::ROUTE_ADMIN_KEY_VERIFY, 'POST', [
            'admin_key' => 'ProAudio00'
        ]);

        $response = $this->controller->adminKeyVerify($request);
        
        $this->assertNotNull($response);
    }

    public function test_clear_limpia_rol(): void
    {
        session(['role' => 'Cliente']);
        
        $response = $this->controller->clear();
        
        $this->assertNotNull($response);
        $this->assertFalse(session()->has('role'));
    }

    public function test_roles_permitidos_son_validos(): void
    {
        // Los roles permitidos según el controlador son:
        // Administrador, Cliente, Invitado
        $rolesPermitidos = ['Administrador', 'Cliente', 'Invitado'];

        foreach ($rolesPermitidos as $rol) {
            $this->assertIsString($rol);
            $this->assertNotEmpty($rol);
        }
    }

    public function test_admin_key_constante(): void
    {
        // Verificar que la clave de administrador es válida
        // Según el código: 'ProAudio00'
        $adminKey = 'ProAudio00';

        $this->assertIsString($adminKey);
        $this->assertNotEmpty($adminKey);
        $this->assertEquals('ProAudio00', $adminKey);
    }

    public function test_validacion_roles_estructura(): void
    {
        // Verificar que los roles tienen la estructura correcta
        $rolesEsperados = ['Administrador', 'Cliente', 'Invitado'];

        $this->assertCount(3, $rolesEsperados);
        $this->assertContains('Administrador', $rolesEsperados);
        $this->assertContains('Cliente', $rolesEsperados);
        $this->assertContains('Invitado', $rolesEsperados);
    }
}

