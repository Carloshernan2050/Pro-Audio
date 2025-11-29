<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tests Feature para UsuarioController
 *
 * Prueban los flujos completos usando rutas HTTP reales
 */
class UsuarioControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Juan';
    private const TEST_APELLIDO = 'Pérez';
    private const ROL_CLIENTE = 'Cliente';
    private const ROUTE_PERFIL_PHOTO = '/perfil/photo';
    private const ROUTE_USUARIOS_AUTENTICAR = '/usuarios/autenticar';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Crear rol Cliente si no existe
        if (!DB::table('roles')->where('name', 'Cliente')->exists() &&
            !DB::table('roles')->where('nombre_rol', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Cliente',
                'nombre_rol' => 'Cliente'
            ]);
        }
    }

    // ============================================
    // TESTS PARA PERFIL
    // ============================================

    public function test_perfil_retorna_vista_con_usuario_autenticado(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $response = $this->get('/perfil');

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.perfil');
        $response->assertViewHas('usuario');
    }

    public function test_perfil_sin_autenticacion(): void
    {
        $response = $this->get('/perfil');

        // Debería funcionar pero sin usuario en la vista
        $response->assertStatus(200);
    }

    // ============================================
    // TESTS PARA UPDATE PHOTO
    // ============================================

    public function test_update_photo_exitoso(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, [
            'foto_perfil' => $file
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Foto de perfil actualizada correctamente'
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'foto_url'
        ]);

        $usuario->refresh();
        $this->assertNotNull($usuario->foto_perfil);
        $this->assertStringStartsWith('perfil_', $usuario->foto_perfil);
    }

    public function test_update_photo_sin_sesion(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, [
            'foto_perfil' => $file
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Debes iniciar sesión'
        ]);
    }

    public function test_update_photo_usuario_invitado(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => ['Invitado']]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, [
            'foto_perfil' => $file
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Los usuarios invitados no pueden subir foto de perfil'
        ]);
    }

    public function test_update_photo_usuario_no_existe(): void
    {
        session(['usuario_id' => 99999, 'roles' => [self::ROL_CLIENTE]]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('perfil.jpg', 100, 100);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, [
            'foto_perfil' => $file
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
    }

    public function test_update_photo_elimina_foto_anterior(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'foto_perfil' => 'old_perfil.jpg',
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        Storage::disk('public')->put('perfiles/old_perfil.jpg', 'fake content');

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('new_perfil.jpg', 100, 100);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, [
            'foto_perfil' => $file
        ]);

        $response->assertStatus(200);
        $this->assertFalse(Storage::disk('public')->exists('perfiles/old_perfil.jpg'));
    }

    public function test_update_photo_valida_archivo_requerido(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('foto_perfil');
    }

    public function test_update_photo_valida_archivo_es_imagen(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        session(['usuario_id' => $usuario->id, 'roles' => [self::ROL_CLIENTE]]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('documento.pdf', 100);

        $response = $this->post(self::ROUTE_PERFIL_PHOTO, [
            'foto_perfil' => $file
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('foto_perfil');
    }

    // ============================================
    // TESTS PARA STORE - CASOS ESPECÍFICOS
    // ============================================

    public function test_store_funciona_sin_rol_existente(): void
    {
        // Eliminar todos los roles
        DB::table('roles')->truncate();

        $response = $this->post('/usuarios', [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('usuarios.inicioSesion'));
        $this->assertDatabaseHas('personas', [
            'correo' => self::TEST_EMAIL
        ]);
    }

    public function test_store_usa_nombre_rol_si_name_no_existe(): void
    {
        // Crear rol solo con nombre_rol
        DB::table('roles')->truncate();
        $rolId = DB::table('roles')->insertGetId([
            'nombre_rol' => self::ROL_CLIENTE
        ]);

        $response = $this->post('/usuarios', [
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('usuarios.inicioSesion'));
        
        $usuario = Usuario::where('correo', self::TEST_EMAIL)->first();
        $this->assertDatabaseHas('personas_roles', [
            'personas_id' => $usuario->id,
            'roles_id' => $rolId
        ]);
    }

    // ============================================
    // TESTS PARA AUTENTICAR - CASOS ESPECÍFICOS
    // ============================================

    public function test_autenticar_con_pending_admin(): void
    {
        Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        session(['pending_admin' => true]);

        $response = $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('admin.key.form'));
    }

    public function test_autenticar_con_roles_vacios_asigna_cliente(): void
    {
        Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        // No asignar roles

        $response = $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals(['Cliente'], session('roles'));
        $this->assertEquals('Cliente', session('role'));
    }

    public function test_autenticar_capitaliza_nombre(): void
    {
        Usuario::create([
            'primer_nombre' => 'jUAN', // Nombre con mayúsculas mixtas
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $this->assertEquals('Juan', session('usuario_nombre'));
    }

    public function test_autenticar_con_rol_usando_nombre_rol(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'nombre_rol' => 'Admin'
        ]);

        DB::table('personas_roles')->insert([
            'personas_id' => $usuario->id,
            'roles_id' => $rolId
        ]);

        $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $roles = session('roles');
        $this->assertContains('Admin', $roles);
    }

    public function test_autenticar_con_roles_duplicados(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'correo' => self::TEST_EMAIL,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'name' => self::ROL_CLIENTE,
            'nombre_rol' => self::ROL_CLIENTE
        ]);

        // Insertar el mismo rol dos veces
        DB::table('personas_roles')->insert([
            ['personas_id' => $usuario->id, 'roles_id' => $rolId],
            ['personas_id' => $usuario->id, 'roles_id' => $rolId]
        ]);

        $this->post(self::ROUTE_USUARIOS_AUTENTICAR, [
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
        ]);

        $roles = session('roles');
        // Debe eliminar duplicados
        $this->assertCount(1, $roles);
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

