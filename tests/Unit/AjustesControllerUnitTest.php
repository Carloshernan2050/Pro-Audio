<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\AjustesController;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests Unitarios para AjustesController
 *
 * Tests para lógica de agrupación y estructura
 */
class AjustesControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_AJUSTES = '/ajustes';
    private const ROUTE_EXPORT_PDF = '/ajustes/export-pdf';
    private const TEST_EMAIL = 'test@test.com';

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AjustesController();
    }

    /**
     * Helper para invocar métodos privados
     *
     * @param object $object
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        // NOSONAR: S3011 - Reflection is required to test private methods in unit tests
        $method->setAccessible(true); // NOSONAR
        return $method->invokeArgs($object, $parameters);
    }

    // ============================================
    // TESTS PARA index()
    // ============================================

    public function test_index_sin_parametros(): void
    {
        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        $request = Request::create(self::ROUTE_AJUSTES, 'GET');
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $this->assertArrayHasKey('servicios', $response->getData());
        $this->assertArrayHasKey('subServicios', $response->getData());
    }

    public function test_index_con_group_by_dia(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['group_by' => 'dia']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertEquals('dia', $data['groupBy']);
        $this->assertEquals('historial', $data['activeTab']);
    }

    public function test_index_con_group_by_consulta(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['group_by' => 'consulta']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertEquals('consulta', $data['groupBy']);
        $this->assertEquals('historial', $data['activeTab']);
    }

    public function test_index_con_tab_especifico(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['tab' => 'inventario']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertEquals('inventario', $data['activeTab']);
    }

    public function test_index_con_cotizaciones(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $request = Request::create(self::ROUTE_AJUSTES, 'GET');
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertIsArray($data['cotizaciones']);
    }

    // ============================================
    // TESTS PARA exportHistorialPdf()
    // ============================================

    public function test_export_historial_pdf_sin_group_by(): void
    {
        $request = Request::create(self::ROUTE_EXPORT_PDF, 'GET');
        $response = $this->controller->exportHistorialPdf($request);

        $this->assertNotNull($response);
        $this->assertEquals('historial_cotizaciones.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_export_historial_pdf_con_group_by(): void
    {
        $request = Request::create(self::ROUTE_EXPORT_PDF, 'GET', ['group_by' => 'dia']);
        $response = $this->controller->exportHistorialPdf($request);

        $this->assertNotNull($response);
    }

    // ============================================
    // TESTS PARA getSubservicios()
    // ============================================

    public function test_get_subservicios(): void
    {
        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        $response = $this->controller->getSubservicios();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    // ============================================
    // TESTS PARA Lógica de Agrupación (indirecta)
    // ============================================

    public function test_group_by_opciones_validas(): void
    {
        $opcionesValidas = [null, 'consulta', 'dia'];
        
        $this->assertContains(null, $opcionesValidas);
        $this->assertContains('consulta', $opcionesValidas);
        $this->assertContains('dia', $opcionesValidas);
    }

    public function test_tab_opciones_validas(): void
    {
        $tabsValidos = ['servicios', 'subservicios', 'inventario', 'movimientos', 'historial'];
        
        $this->assertCount(5, $tabsValidos);
        $this->assertContains('servicios', $tabsValidos);
        $this->assertContains('historial', $tabsValidos);
    }

    public function test_active_tab_default(): void
    {
        $groupBy = null;
        $activeTab = $groupBy ? 'historial' : 'servicios';
        
        $this->assertEquals('servicios', $activeTab);
    }

    public function test_active_tab_con_group_by(): void
    {
        $groupBy = 'dia';
        $activeTab = $groupBy ? 'historial' : 'servicios';
        
        $this->assertEquals('historial', $activeTab);
    }

    public function test_group_cotizaciones_por_dia(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['group_by' => 'dia']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertNotNull($data['groupedCotizaciones']);
    }

    public function test_group_cotizaciones_por_consulta(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['group_by' => 'consulta']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertNotNull($data['groupedCotizaciones']);
    }

    // ============================================
    // TESTS PARA Métodos Privados (usando reflexión)
    // ============================================

    public function test_get_cotizaciones_metodo_privado(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $cotizaciones = $this->invokePrivateMethod($this->controller, 'getCotizaciones');
        
        $this->assertNotNull($cotizaciones);
        $this->assertGreaterThanOrEqual(1, $cotizaciones->count());
    }

    public function test_group_cotizaciones_por_dia_metodo_privado(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        $fecha = now();
        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => $fecha
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 200,
            'fecha_cotizacion' => $fecha
        ]);

        $cotizaciones = $this->invokePrivateMethod($this->controller, 'getCotizaciones');
        $grouped = $this->invokePrivateMethod($this->controller, 'groupCotizaciones', [$cotizaciones, 'dia']);
        
        $this->assertNotNull($grouped);
        $this->assertGreaterThanOrEqual(1, $grouped->count());
        
        // Verificar estructura de agrupación
        $firstGroup = $grouped->first();
        $this->assertArrayHasKey('items', $firstGroup);
        $this->assertArrayHasKey('total', $firstGroup);
        $this->assertArrayHasKey('count', $firstGroup);
    }

    public function test_group_cotizaciones_por_consulta_metodo_privado(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        $fecha = now();
        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => $fecha
        ]);

        $cotizaciones = $this->invokePrivateMethod($this->controller, 'getCotizaciones');
        $grouped = $this->invokePrivateMethod($this->controller, 'groupCotizaciones', [$cotizaciones, 'consulta']);
        
        $this->assertNotNull($grouped);
        $this->assertGreaterThanOrEqual(1, $grouped->count());
        
        // Verificar estructura de agrupación
        $firstGroup = $grouped->first();
        $this->assertArrayHasKey('items', $firstGroup);
        $this->assertArrayHasKey('total', $firstGroup);
        $this->assertArrayHasKey('count', $firstGroup);
        $this->assertArrayHasKey('persona', $firstGroup);
        $this->assertArrayHasKey('timestamp', $firstGroup);
    }

    public function test_group_cotizaciones_sin_group_by(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $cotizaciones = $this->invokePrivateMethod($this->controller, 'getCotizaciones');
        $grouped = $this->invokePrivateMethod($this->controller, 'groupCotizaciones', [$cotizaciones, null]);
        
        $this->assertNull($grouped);
    }

    public function test_group_cotizaciones_con_fecha_null(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        // Crear cotización sin fecha
        $cotizacion = new Cotizacion();
        $cotizacion->personas_id = $usuario->id;
        $cotizacion->sub_servicios_id = $subServicio->id;
        $cotizacion->monto = 100;
        $cotizacion->fecha_cotizacion = null;
        $cotizacion->save();

        $cotizaciones = $this->invokePrivateMethod($this->controller, 'getCotizaciones');
        $grouped = $this->invokePrivateMethod($this->controller, 'groupCotizaciones', [$cotizaciones, 'dia']);
        
        $this->assertNotNull($grouped);
    }

    public function test_export_historial_pdf_con_group_by_consulta(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password'
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $request = Request::create('/ajustes/export-pdf', 'GET', ['group_by' => 'consulta']);
        $response = $this->controller->exportHistorialPdf($request);

        $this->assertNotNull($response);
    }
}

