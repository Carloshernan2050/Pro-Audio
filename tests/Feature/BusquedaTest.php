<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * Tests de Integración para BusquedaController
 *
 * Prueban los flujos completos de búsqueda de servicios
 */
class BusquedaTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Juan';
    private const TEST_APELLIDO = 'Pérez';
    private const TEST_TELEFONO = '1234567890';
    private const ROUTE_BUSCAR = '/buscar';
    private const DESC_SERVICIO_ALQUILER = 'Servicio de alquiler';
    private const NOMBRE_EQUIPO_SONIDO = 'Equipo de sonido';
    private const DESC_EQUIPO_COMPLETO = 'Equipo completo';

    protected function setUp(): void
    {
        parent::setUp();

        if (!DB::table('roles')->where('nombre_rol', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Cliente',
                'nombre_rol' => 'Cliente'
            ]);
        }
    }

    private function crearUsuarioAutenticado(): Usuario
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

        $rolId = DB::table('roles')->where('nombre_rol', 'Cliente')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId
            ]);
        }

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Cliente'], 'role' => 'Cliente']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA BÚSQUEDA
    // ============================================

    public function test_buscar_sin_termino_retorna_vista_vacia(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR);

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.busqueda');
        $response->assertViewHas('termino', '');
        $response->assertViewHas('resultados');
    }

    public function test_buscar_por_nombre_subservicio(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR . '?buscar=sonido');

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.busqueda');
        $resultados = $response->viewData('resultados');
        $this->assertGreaterThan(0, $resultados->count());
    }

    public function test_buscar_por_descripcion(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler'
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de sonido',
            'descripcion' => 'Equipo completo de audio',
            'precio' => 100
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR . '?buscar=audio');

        $response->assertStatus(200);
        $resultados = $response->viewData('resultados');
        $this->assertGreaterThan(0, $resultados->count());
    }

    public function test_buscar_por_nombre_servicio(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR . '?buscar=Alquiler');

        $response->assertStatus(200);
        $resultados = $response->viewData('resultados');
        $this->assertGreaterThan(0, $resultados->count());
    }

    public function test_buscar_por_palabras_multiples(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR . '?buscar=equipo sonido');

        $response->assertStatus(200);
        $resultados = $response->viewData('resultados');
        $this->assertGreaterThan(0, $resultados->count());
    }

    public function test_buscar_sin_resultados(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR . '?buscar=xyz123noexiste');

        $response->assertStatus(200);
        $resultados = $response->viewData('resultados');
        $this->assertEquals(0, $resultados->count());
    }

    public function test_buscar_normaliza_texto(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Animación',
            'descripcion' => 'Servicio de animación'
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Animador',
            'descripcion' => 'Servicio de animación',
            'precio' => 200
        ]);

        // Buscar con error ortográfico que debería normalizarse
        $response = $this->withoutVite()->get(self::ROUTE_BUSCAR . '?buscar=animacion');

        $response->assertStatus(200);
        $resultados = $response->viewData('resultados');
        $this->assertGreaterThan(0, $resultados->count());
    }
}

