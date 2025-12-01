<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests de Integración para Autenticación
 *
 * Prueban los flujos completos de registro, login y logout
 */
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@example.com';

    private const TEST_PASSWORD = 'password123';

    private const TEST_NOMBRE = 'Juan';

    private const TEST_APELLIDO = 'Pérez';

    private const TEST_TELEFONO = '1234567890';

    private const ROUTE_USUARIOS = '/usuarios';

    private const ROUTE_USUARIOS_AUTENTICAR = '/usuarios/autenticar';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol Cliente si no existe
        if (! DB::table('roles')->where('name', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Cliente',
                'nombre_rol' => 'Cliente',
            ]);
        }
    }

    // ============================================
    // TESTS PARA REGISTRO DE USUARIO
    // ============================================

    public function test_registro_usuario_exitoso(): void
    {
        $response = $this->post(self::ROUTE_USUARIOS, [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('usuarios.inicioSesion'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personas', [
            'correo' => self::TEST_EMAIL,
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
        ]);
    }

    public function test_registro_usuario_validacion_correo_duplicado(): void
    {
        Usuario::create([
            'primer_nombre' => 'Otro',
            'primer_apellido' => 'Usuario',
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $response = $this->post(self::ROUTE_USUARIOS, [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD,
        ]);

        $response->assertSessionHasErrors('correo');
    }

    public function test_registro_usuario_validacion_contraseña_corta(): void
    {
        $response = $this->post(self::ROUTE_USUARIOS, [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => '123',
            'contrasena_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('contrasena');
    }

    public function test_registro_usuario_validacion_contraseña_no_coincide(): void
    {
        $response = $this->post(self::ROUTE_USUARIOS, [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => 'diferente',
        ]);

        $response->assertSessionHasErrors('contrasena');
    }

    public function test_registro_usuario_asigna_rol_cliente(): void
    {
        $this->post(self::ROUTE_USUARIOS, [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD,
        ]);

        $usuario = Usuario::where('correo', self::TEST_EMAIL)->first();
        $this->assertNotNull($usuario);

        $rolAsignado = DB::table('personas_roles')
            ->join('roles', 'roles.id', '=', 'personas_roles.roles_id')
            ->where('personas_roles.personas_id', $usuario->id)
            ->where(function ($query) {
                $query->where('roles.name', 'Cliente')
                    ->orWhere('roles.nombre_rol', 'Cliente');
            })
            ->exists();

        $this->assertTrue($rolAsignado);
    }

    // ============================================
    // TESTS PARA INICIO DE SESIÓN
    // ============================================

    public function test_login_exitoso(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $rolId = DB::table('roles')->where('name', 'Cliente')->orWhere('nombre_rol', 'Cliente')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId,
            ]);
        }

        $response = $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('usuario_id', $usuario->id);
        $response->assertSessionHas('usuario_nombre');
        $response->assertSessionHas('success');
    }

    public function test_login_credenciales_incorrectas(): void
    {
        Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $response = $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'contraseña_incorrecta',
        ]);

        $response->assertSessionHasErrors('correo');
        $this->assertNull(session('usuario_id'));
    }

    public function test_login_usuario_no_existe(): void
    {
        $response = $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => 'noexiste@example.com',
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $response->assertSessionHasErrors('correo');
        $this->assertNull(session('usuario_id'));
    }

    public function test_login_carga_roles_en_sesion(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $rolId = DB::table('roles')->where('name', 'Cliente')->orWhere('nombre_rol', 'Cliente')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId,
            ]);
        }

        $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $this->assertNotNull(session('roles'));
        $this->assertNotNull(session('role'));
    }

    // ============================================
    // TESTS PARA CERRAR SESIÓN
    // ============================================

    public function test_logout_limpia_sesion(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        // Simular sesión iniciada
        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);

        $response = $this->post('/usuarios/cerrarSesion');

        $response->assertRedirect(route('usuarios.inicioSesion'));
        $response->assertSessionHas('success');
        $this->assertNull(session('usuario_id'));
    }

    // ============================================
    // TESTS PARA VISTAS
    // ============================================

    public function test_vista_registro_retorna_formulario(): void
    {
        $response = $this->withoutVite()->get('/usuarios/crear');

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.registroUsuario');
    }

    public function test_vista_login_retorna_formulario(): void
    {
        $response = $this->withoutVite()->get('/usuarios/inicioSesion');

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.inicioSesion');
    }
}
