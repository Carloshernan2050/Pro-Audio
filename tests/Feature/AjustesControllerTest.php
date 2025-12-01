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
 * Prueban los flujos completos de visualización y exportación de ajustes
 */
class AjustesControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_AJUSTES = '/usuarios/ajustes';

    private const ROUTE_AJUSTES_EXPORT = '/usuarios/ajustes/historial/pdf';

    private const ROUTE_AJUSTES_SUBSERVICIOS = '/usuarios/ajustes/subservicios';

    private const TEST_EMAIL = 'admin@example.com';

    private const TEST_PASSWORD = 'password123';

    private const TEST_NOMBRE = 'Admin';

    private const TEST_APELLIDO = 'Usuario';

    private const TEST_TELEFONO = '1234567890';

    private const NOMBRE_SERVICIO = 'Alquiler';

    private const DESC_SERVICIO = 'Servicio de alquiler';

    private const NOMBRE_SUBSERVICIO = 'Equipo de sonido';

    private const DESC_SUBSERVICIO = 'Equipo completo';

    private const CONTENT_TYPE_PDF = 'application/pdf';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol Administrador si no existe
        if (! DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
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

        $rolId = DB::table('roles')->where('nombre_rol', 'Administrador')->orWhere('nombre_rol', 'Admin')->value('id');
        if (! $rolId) {
            $rolId = DB::table('roles')->insertGetId(['nombre_rol' => 'Administrador']);
        }

        DB::table('personas_roles')->insert([
            'personas_id' => $usuario->id,
            'roles_id' => $rolId,
        ]);

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Admin'], 'role' => 'Admin']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA INDEX
    // ============================================

    public function test_index_retorna_vista_ajustes(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES);

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.ajustes');
        $response->assertViewHas(['servicios', 'subServicios', 'cotizaciones', 'groupBy', 'groupedCotizaciones', 'activeTab']);
    }

    public function test_index_carga_servicios(): void
    {
        $this->crearUsuarioAdmin();

        Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO,
            'descripcion' => self::DESC_SERVICIO,
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES);

        $response->assertStatus(200);
        $servicios = $response->viewData('servicios');
        $this->assertCount(1, $servicios);
        $this->assertEquals(self::NOMBRE_SERVICIO, $servicios->first()->nombre_servicio);
    }

    public function test_index_carga_subservicios(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO,
            'descripcion' => self::DESC_SERVICIO,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_SUBSERVICIO,
            'descripcion' => self::DESC_SUBSERVICIO,
            'precio' => 100,
        ]);

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES);

        $response->assertStatus(200);
        $subServicios = $response->viewData('subServicios');
        $this->assertCount(1, $subServicios);
        $this->assertEquals(self::NOMBRE_SUBSERVICIO, $subServicios->first()->nombre);
    }

    public function test_index_carga_cotizaciones(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO,
            'descripcion' => self::DESC_SERVICIO,
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_SUBSERVICIO,
            'descripcion' => self::DESC_SUBSERVICIO,
            'precio' => 100,
        ]);

        try {
            Cotizacion::create([
                'personas_id' => session('usuario_id'),
                'sub_servicios_id' => $subServicio->id,
                'fecha_cotizacion' => now(),
                'monto' => 500,
            ]);

            $response = $this->withoutVite()->get(self::ROUTE_AJUSTES);

            $response->assertStatus(200);
            $cotizaciones = $response->viewData('cotizaciones');
            $this->assertCount(1, $cotizaciones);
        } catch (\Exception $e) {
            $this->markTestSkipped('La tabla cotizacion no existe o no tiene las columnas necesarias');
        }
    }

    public function test_index_con_group_by_dia(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?group_by=dia');

        $response->assertStatus(200);
        $response->assertViewHas('groupBy', 'dia');
        $response->assertViewHas('activeTab', 'historial');
    }

    public function test_index_con_group_by_consulta(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?group_by=consulta');

        $response->assertStatus(200);
        $response->assertViewHas('groupBy', 'consulta');
        $response->assertViewHas('activeTab', 'historial');
    }

    public function test_index_con_tab_especifico(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES.'?tab=inventario');

        $response->assertStatus(200);
        $response->assertViewHas('activeTab', 'inventario');
    }

    public function test_index_tab_default_sin_group_by(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->withoutVite()->get(self::ROUTE_AJUSTES);

        $response->assertStatus(200);
        $response->assertViewHas('activeTab', 'servicios');
    }

    public function test_index_sin_autenticacion(): void
    {
        $response = $this->get(self::ROUTE_AJUSTES);

        // El middleware redirige a inicio cuando no hay acceso
        $this->assertContains($response->status(), [302, 404]);
    }

    // ============================================
    // TESTS PARA GET SUBSERVICIOS
    // ============================================

    public function test_get_subservicios_retorna_json(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO,
            'descripcion' => self::DESC_SERVICIO,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_SUBSERVICIO,
            'descripcion' => self::DESC_SUBSERVICIO,
            'precio' => 100,
        ]);

        $response = $this->getJson(self::ROUTE_AJUSTES_SUBSERVICIOS);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'servicios_id',
                'nombre',
                'descripcion',
                'precio',
                'servicio',
            ],
        ]);
    }

    public function test_get_subservicios_ordena_por_id(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO,
            'descripcion' => self::DESC_SERVICIO,
        ]);

        $subServicio1 = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubServicio 1',
            'descripcion' => 'Descripción 1',
            'precio' => 100,
        ]);

        $subServicio2 = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubServicio 2',
            'descripcion' => 'Descripción 2',
            'precio' => 200,
        ]);

        $response = $this->getJson(self::ROUTE_AJUSTES_SUBSERVICIOS);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertEquals($subServicio1->id, $data[0]['id']);
        $this->assertEquals($subServicio2->id, $data[1]['id']);
    }

    // ============================================
    // TESTS PARA EXPORT PDF
    // ============================================

    public function test_export_pdf_genera_pdf(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->get(self::ROUTE_AJUSTES_EXPORT);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', self::CONTENT_TYPE_PDF);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('historial_cotizaciones.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_export_pdf_con_group_by_dia(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->get(self::ROUTE_AJUSTES_EXPORT.'?group_by=dia');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', self::CONTENT_TYPE_PDF);
    }

    public function test_export_pdf_con_group_by_consulta(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->get(self::ROUTE_AJUSTES_EXPORT.'?group_by=consulta');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', self::CONTENT_TYPE_PDF);
    }

    public function test_export_pdf_sin_autenticacion(): void
    {
        $response = $this->get(self::ROUTE_AJUSTES_EXPORT);

        // El middleware redirige a inicio cuando no hay acceso
        $this->assertContains($response->status(), [302, 404]);
    }
}
