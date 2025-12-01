<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests de Integración para AjustesController
 *
 * Prueban los flujos completos de ajustes y exportación de historial
 */
class AjustesTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'admin@example.com';

    private const TEST_PASSWORD = 'password123';

    private const TEST_NOMBRE = 'Admin';

    private const TEST_APELLIDO = 'Usuario';

    private const TEST_TELEFONO = '1234567890';

    private const ROUTE_AJUSTES = '/usuarios/ajustes';

    private const ROUTE_AJUSTES_HISTORIAL_PDF = '/usuarios/ajustes/historial/pdf';

    private const ROUTE_AJUSTES_SUBSERVICIOS = '/usuarios/ajustes/subservicios';

    private const DESC_SERVICIO_ALQUILER = 'Servicio de alquiler';

    private const NOMBRE_EQUIPO_SONIDO = 'Equipo de sonido';

    private const DESC_EQUIPO_COMPLETO = 'Equipo completo';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol Administrador
        if (! DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Administrador',
                'nombre_rol' => 'Administrador',
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
                'roles_id' => $rolId,
            ]);
        }

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA INDEX
    // ============================================

    public function test_index_muestra_ajustes(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES);

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.ajustes');
        $response->assertViewHas('servicios');
        $response->assertViewHas('subServicios');
    }

    public function test_index_con_tab_servicios(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?tab=servicios');

        $response->assertStatus(200);
        $response->assertViewHas('activeTab', 'servicios');
    }

    public function test_index_con_tab_subservicios(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?tab=subservicios');

        $response->assertStatus(200);
        $response->assertViewHas('activeTab', 'subservicios');
    }

    public function test_index_con_group_by_consulta(): void
    {
        $this->crearUsuarioAdmin();

        $usuario = Usuario::where('correo', self::TEST_EMAIL)->first();
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now(),
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?group_by=consulta');

        $response->assertStatus(200);
        $response->assertViewHas('groupBy', 'consulta');
        $response->assertViewHas('groupedCotizaciones');
    }

    public function test_index_con_group_by_dia(): void
    {
        $this->crearUsuarioAdmin();

        $usuario = Usuario::where('correo', self::TEST_EMAIL)->first();
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now(),
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?group_by=dia');

        $response->assertStatus(200);
        $response->assertViewHas('groupBy', 'dia');
        $response->assertViewHas('groupedCotizaciones');
    }

    // ============================================
    // TESTS PARA EXPORTAR PDF
    // ============================================

    public function test_export_historial_pdf_sin_group_by(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->get(self::ROUTE_AJUSTES_HISTORIAL_PDF);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_export_historial_pdf_con_group_by_consulta(): void
    {
        $this->crearUsuarioAdmin();

        $usuario = Usuario::where('correo', self::TEST_EMAIL)->first();
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now(),
        ]);

        $response = $this->get(self::ROUTE_AJUSTES_HISTORIAL_PDF.'?group_by=consulta');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    // ============================================
    // TESTS PARA GET SUBSERVICIOS
    // ============================================

    public function test_get_subservicios_retorna_json(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $response = $this->get(self::ROUTE_AJUSTES_SUBSERVICIOS);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'nombre',
                'precio',
                'descripcion',
                'servicio',
            ],
        ]);
    }

    // ============================================
    // TESTS PARA PERMISOS
    // ============================================

    public function test_index_requiere_rol_admin(): void
    {
        $response = $this->get(self::ROUTE_AJUSTES);

        $this->assertTrue($response->isRedirect() || $response->status() === 403);
    }
}
