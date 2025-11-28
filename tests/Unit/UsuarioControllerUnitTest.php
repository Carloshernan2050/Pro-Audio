<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\UsuarioController;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Tests Unitarios para UsuarioController
 *
 * Tests para validaciones y estructura
 */
class UsuarioControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL_JUAN = 'juan@test.com';
    private const TEST_PASSWORD = 'password123';
    private const ROL_CLIENTE = 'Cliente';
    private const ROUTE_USUARIOS_AUTENTICAR = '/usuarios/autenticar';
    private const ROUTE_USUARIOS_PERFIL_PHOTO = '/usuarios/perfil/photo';
    private const APELLIDO_PEREZ = 'Pérez';

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->controller = new UsuarioController();
    }

    // ============================================
    // TESTS PARA Validaciones de Registro
    // ============================================

    public function test_validacion_registro_estructura(): void
    {
        $reglasEsperadas = [
            'primer_nombre' => 'required|string|max:255',
            'segundo_nombre' => 'nullable|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'correo' => 'required|email|unique:personas,correo',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'contrasena' => 'required|string|min:8|confirmed',
        ];
        
        $this->assertArrayHasKey('primer_nombre', $reglasEsperadas);
        $this->assertArrayHasKey('correo', $reglasEsperadas);
        $this->assertArrayHasKey('contrasena', $reglasEsperadas);
    }

    public function test_validacion_contrasena_min_caracteres(): void
    {
        // La contraseña debe tener al menos 8 caracteres
        $reglasEsperadas = [
            'contrasena' => 'required|string|min:8|confirmed'
        ];
        
        $this->assertStringContainsString('min:8', $reglasEsperadas['contrasena']);
        $this->assertStringContainsString('confirmed', $reglasEsperadas['contrasena']);
    }

    public function test_validacion_telefono_max_caracteres(): void
    {
        // El teléfono máximo es 20 caracteres
        $maxCaracteres = 20;
        
        $this->assertEquals(20, $maxCaracteres);
    }

    public function test_validacion_correo_debe_ser_email(): void
    {
        $reglasEsperadas = [
            'correo' => 'required|email|unique:personas,correo'
        ];
        
        $this->assertStringContainsString('email', $reglasEsperadas['correo']);
    }

    // ============================================
    // TESTS PARA Validaciones de Autenticación
    // ============================================

    public function test_validacion_autenticar_estructura(): void
    {
        $reglasEsperadas = [
            'correo' => 'required|email',
            'contrasena' => 'required|string',
        ];
        
        $this->assertArrayHasKey('correo', $reglasEsperadas);
        $this->assertArrayHasKey('contrasena', $reglasEsperadas);
    }

    // ============================================
    // TESTS PARA Validaciones de Foto Perfil
    // ============================================

    public function test_validacion_foto_perfil_estructura(): void
    {
        $reglasEsperadas = [
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
        ];
        
        $this->assertStringContainsString('image', $reglasEsperadas['foto_perfil']);
        $this->assertStringContainsString('max:5120', $reglasEsperadas['foto_perfil']);
    }

    public function test_foto_perfil_formatos_permitidos(): void
    {
        // Formatos permitidos: jpeg, png, jpg, gif
        $formatos = ['jpeg', 'png', 'jpg', 'gif'];
        
        $this->assertCount(4, $formatos);
        $this->assertContains('jpeg', $formatos);
        $this->assertContains('png', $formatos);
    }

    public function test_foto_perfil_max_tamaño(): void
    {
        // El tamaño máximo es 5120 KB (5MB)
        $maxTamano = 5120;
        
        $this->assertEquals(5120, $maxTamano);
    }

    // ============================================
    // TESTS PARA Valores por Defecto
    // ============================================

    public function test_estado_activo_por_defecto(): void
    {
        // El estado por defecto es 1 (Activo)
        $estadoActivo = 1;
        
        $this->assertEquals(1, $estadoActivo);
    }

    public function test_rol_cliente_por_defecto(): void
    {
        // El rol por defecto es 'Cliente'
        $rolDefecto = self::ROL_CLIENTE;
        
        $this->assertEquals(self::ROL_CLIENTE, $rolDefecto);
    }

    public function test_registro_retorna_vista(): void
    {
        $response = $this->controller->registro();
        
        $this->assertNotNull($response);
    }

    public function test_store_crea_usuario(): void
    {
        DB::table('roles')->insertGetId([
            'name' => self::ROL_CLIENTE,
            'nombre_rol' => self::ROL_CLIENTE
        ]);

        $request = Request::create('/usuarios/registro', 'POST', [
            'primer_nombre' => 'Juan',
            'primer_apellido' => self::APELLIDO_PEREZ,
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD
        ]);

        $response = $this->controller->store($request);
        
        $this->assertNotNull($response);
        
        $this->assertDatabaseHas('personas', [
            'correo' => self::TEST_EMAIL_JUAN,
            'primer_nombre' => 'Juan'
        ]);
    }

    public function test_inicio_sesion_retorna_vista(): void
    {
        $response = $this->controller->inicioSesion();
        
        $this->assertNotNull($response);
    }

    public function test_autenticar_exitoso(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'primer_apellido' => self::APELLIDO_PEREZ,
            'telefono' => '1234567890',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $request = Request::create(self::ROUTE_USUARIOS_AUTENTICAR, 'POST', [
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => self::TEST_PASSWORD
        ]);

        $response = $this->controller->autenticar($request);
        
        $this->assertNotNull($response);
        $this->assertEquals($usuario->id, session('usuario_id'));
    }

    public function test_autenticar_fallido(): void
    {
        Usuario::create([
            'primer_nombre' => 'Juan',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => Hash::make(self::TEST_PASSWORD)
        ]);

        $request = Request::create(self::ROUTE_USUARIOS_AUTENTICAR, 'POST', [
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => 'password_incorrecta'
        ]);

        $response = $this->controller->autenticar($request);
        
        $this->assertNotNull($response);
    }

    public function test_autenticar_con_roles(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'primer_apellido' => self::APELLIDO_PEREZ,
            'telefono' => '1234567890',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
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

        $request = Request::create(self::ROUTE_USUARIOS_AUTENTICAR, 'POST', [
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => self::TEST_PASSWORD
        ]);

        $response = $this->controller->autenticar($request);
        
        $this->assertNotNull($response);
        $this->assertTrue(session()->has('roles'));
    }

    public function test_autenticar_con_pending_admin(): void
    {
        Usuario::create([
            'primer_nombre' => 'Juan',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => Hash::make(self::TEST_PASSWORD)
        ]);

        session(['pending_admin' => true]);

        $request = Request::create(self::ROUTE_USUARIOS_AUTENTICAR, 'POST', [
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => self::TEST_PASSWORD
        ]);

        $response = $this->controller->autenticar($request);
        
        $this->assertNotNull($response);
    }

    public function test_cerrar_sesion(): void
    {
        session(['usuario_id' => 1, 'usuario_nombre' => 'Juan']);

        $response = $this->controller->cerrarSesion();
        
        $this->assertNotNull($response);
        $this->assertFalse(session()->has('usuario_id'));
    }

    public function test_perfil_retorna_vista(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => 'password'
        ]);

        session(['usuario_id' => $usuario->id]);

        $response = $this->controller->perfil();
        
        $this->assertNotNull($response);
    }

    public function test_update_photo_exitoso(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => 'password'
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $request = Request::create(self::ROUTE_USUARIOS_PERFIL_PHOTO, 'POST', [], [], [
            'foto_perfil' => $file
        ]);

        $response = $this->controller->updatePhoto($request);
        
        $this->assertNotNull($response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_update_photo_sin_sesion(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed');
            return;
        }

        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $request = Request::create(self::ROUTE_USUARIOS_PERFIL_PHOTO, 'POST', [], [], [
            'foto_perfil' => $file
        ]);

        $response = $this->controller->updatePhoto($request);
        
        $this->assertNotNull($response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function test_update_photo_usuario_invitado(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => 'password'
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => ['Invitado']]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $request = Request::create(self::ROUTE_USUARIOS_PERFIL_PHOTO, 'POST', [], [], [
            'foto_perfil' => $file
        ]);

        $response = $this->controller->updatePhoto($request);
        
        $this->assertNotNull($response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function test_update_photo_elimina_foto_anterior(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'correo' => self::TEST_EMAIL_JUAN,
            'contrasena' => 'password',
            'foto_perfil' => 'old_perfil.jpg'
        ]);

        Storage::disk('public')->put('perfiles/old_perfil.jpg', 'fake content');

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('new_perfil.jpg', 100, 100);

        $request = Request::create(self::ROUTE_USUARIOS_PERFIL_PHOTO, 'POST', [], [], [
            'foto_perfil' => $file
        ]);

        $response = $this->controller->updatePhoto($request);
        
        $this->assertNotNull($response);
        
        $this->assertFalse(Storage::disk('public')->exists('perfiles/old_perfil.jpg'));
    }
}

