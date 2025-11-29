<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Tests de Integración para CRUD de SubServicios
 *
 * Prueban los flujos completos de creación, lectura, actualización y eliminación de subservicios
 */
class SubServiciosCrudTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'admin@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Admin';
    private const TEST_APELLIDO = 'Usuario';
    private const TEST_TELEFONO = '1234567890';
    private const ROUTE_SUBSERVICIOS = '/subservicios';
    private const DESC_SERVICIO_ALQUILER = 'Servicio de alquiler';
    private const NOMBRE_SUBSERVICIO_CON_IMAGEN = 'Subservicio con Imagen';
    private const DESC_PRUEBA = 'Descripción';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Crear roles
        if (!DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Administrador',
                'nombre_rol' => 'Administrador'
            ]);
        }
    }

    private function crearUsuarioAdmin(): Usuario
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

        $rolId = DB::table('roles')->where('nombre_rol', 'Administrador')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId
            ]);
        }

        // Simular sesión iniciada como Admin
        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA LISTAR SUBSERVICIOS
    // ============================================

    public function test_index_lista_subservicios(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de sonido',
            'descripcion' => 'Equipo completo',
            'precio' => 100
        ]);

        $response = $this->get(self::ROUTE_SUBSERVICIOS);

        $response->assertStatus(200);
    }

    // ============================================
    // TESTS PARA CREAR SUBSERVICIO
    // ============================================

    public function test_store_crea_subservicio_exitoso(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler'
        ]);

        $response = $this->post('/subservicios', [
            'servicios_id' => $servicio->id,
            'nombre' => 'Nuevo Subservicio',
            'descripcion' => 'Descripción del subservicio',
            'precio' => 150
        ]);

        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sub_servicios', [
            'nombre' => 'Nuevo Subservicio',
            'descripcion' => 'Descripción del subservicio',
            'precio' => 150
        ]);
    }

    public function test_store_crea_subservicio_con_imagen(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed');
        }

        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->post(self::ROUTE_SUBSERVICIOS, [
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_SUBSERVICIO_CON_IMAGEN,
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 200
        ], [
            'imagen' => $file
        ]);

        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('success');

        $subServicio = SubServicios::where('nombre', self::NOMBRE_SUBSERVICIO_CON_IMAGEN)->first();
        $this->assertNotNull($subServicio);
        $this->assertNotNull($subServicio->imagen);
    }

    public function test_store_validacion_servicios_id_requerido(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->post(self::ROUTE_SUBSERVICIOS, [
            'nombre' => 'Subservicio',
            'precio' => 100
        ]);

        $response->assertSessionHasErrors('servicios_id');
    }

    public function test_store_validacion_servicios_id_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->post(self::ROUTE_SUBSERVICIOS, [
            'servicios_id' => 99999,
            'nombre' => 'Subservicio',
            'precio' => 100
        ]);

        $response->assertSessionHasErrors('servicios_id');
    }

    public function test_store_validacion_nombre_requerido(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $response = $this->post(self::ROUTE_SUBSERVICIOS, [
            'servicios_id' => $servicio->id,
            'precio' => 100
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_store_validacion_precio_requerido(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $response = $this->post(self::ROUTE_SUBSERVICIOS, [
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio'
        ]);

        $response->assertSessionHasErrors('precio');
    }

    public function test_store_validacion_precio_minimo(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $response = $this->post(self::ROUTE_SUBSERVICIOS, [
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio',
            'precio' => -10
        ]);

        $response->assertSessionHasErrors('precio');
    }

    // ============================================
    // TESTS PARA VER SUBSERVICIO
    // ============================================

    public function test_show_retorna_vista_subservicio(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de sonido',
            'descripcion' => 'Equipo completo',
            'precio' => 100
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_SUBSERVICIOS . "/{$subServicio->id}");

        $response->assertStatus(200);
    }

    public function test_show_subservicio_no_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->get(self::ROUTE_SUBSERVICIOS . '/99999');

        $response->assertStatus(404);
    }

    // ============================================
    // TESTS PARA ACTUALIZAR SUBSERVICIO
    // ============================================

    public function test_update_actualiza_subservicio(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio Original',
            'descripcion' => 'Descripción original',
            'precio' => 100
        ]);

        $response = $this->put(self::ROUTE_SUBSERVICIOS . "/{$subServicio->id}", [
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio Actualizado',
            'descripcion' => 'Descripción actualizada',
            'precio' => 200
        ]);

        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sub_servicios', [
            'id' => $subServicio->id,
            'nombre' => 'Subservicio Actualizado',
            'descripcion' => 'Descripción actualizada',
            'precio' => 200
        ]);
    }

    public function test_update_actualiza_imagen(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed');
        }

        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio',
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100,
            'imagen' => 'old_image.jpg'
        ]);

        Storage::disk('public')->put('subservicios/old_image.jpg', 'fake content');

        $file = \Illuminate\Http\UploadedFile::fake()->image('new_test.jpg', 100, 100);

        $response = $this->put(self::ROUTE_SUBSERVICIOS . "/{$subServicio->id}", [
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio',
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100
        ], [
            'imagen' => $file
        ]);

        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('success');
    }

    // ============================================
    // TESTS PARA ELIMINAR SUBSERVICIO
    // ============================================

    public function test_destroy_elimina_subservicio(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Subservicio a Eliminar',
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100
        ]);

        $response = $this->delete(self::ROUTE_SUBSERVICIOS . "/{$subServicio->id}");

        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sub_servicios', [
            'id' => $subServicio->id
        ]);
    }

    public function test_destroy_elimina_imagen_al_eliminar_subservicio(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_SUBSERVICIO_CON_IMAGEN,
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100,
            'imagen' => 'test_image.jpg'
        ]);

        Storage::disk('public')->put('subservicios/test_image.jpg', 'fake content');

        $response = $this->delete(self::ROUTE_SUBSERVICIOS . "/{$subServicio->id}");

        $response->assertRedirect(route('subservicios.index'));
        $this->assertFalse(Storage::disk('public')->exists('subservicios/test_image.jpg'));
    }

    public function test_destroy_subservicio_no_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->delete(self::ROUTE_SUBSERVICIOS . '/99999');

        $response->assertStatus(404);
    }

    // ============================================
    // TESTS PARA PERMISOS
    // ============================================

    public function test_index_requiere_rol_admin(): void
    {
        // Sin autenticación
        $response = $this->get(self::ROUTE_SUBSERVICIOS);

        // Debería redirigir o denegar acceso
        $this->assertTrue($response->isRedirect() || $response->status() === 403);
    }
}

