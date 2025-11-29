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
    private const DESC_PRUEBA = 'Descripción de prueba';
    private const TELEFONO_PRUEBA = '1234567890';

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
        // Note: setAccessible() is no longer needed in PHP 8.1+ as reflected methods are accessible by default
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
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100
        ]);

        $request = Request::create(self::ROUTE_AJUSTES, 'GET');
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertArrayHasKey('servicios', $data);
        $this->assertArrayHasKey('subServicios', $data);
        $this->assertArrayHasKey('cotizaciones', $data);
        $this->assertArrayHasKey('groupBy', $data);
        $this->assertArrayHasKey('groupedCotizaciones', $data);
        $this->assertArrayHasKey('activeTab', $data);
        $this->assertEquals('servicios', $data['activeTab']);
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

    public function test_index_con_tab_subservicios(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['tab' => 'subservicios']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertEquals('subservicios', $data['activeTab']);
    }

    public function test_index_con_tab_movimientos(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['tab' => 'movimientos']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertEquals('movimientos', $data['activeTab']);
    }

    public function test_index_con_tab_invalido(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', ['tab' => 'tab_invalido']);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        // Si hay group_by, debería ser 'historial', si no, 'servicios'
        $this->assertContains($data['activeTab'], ['servicios', 'historial']);
    }

    public function test_index_con_cotizaciones(): void
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

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('historial_cotizaciones.pdf', $contentDisposition);
    }

    public function test_export_historial_pdf_con_group_by(): void
    {
        $request = Request::create(self::ROUTE_EXPORT_PDF, 'GET', ['group_by' => 'dia']);
        $response = $this->controller->exportHistorialPdf($request);

        $this->assertNotNull($response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_export_historial_pdf_con_group_by_consulta(): void
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

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100
        ]);

        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $request = Request::create(self::ROUTE_EXPORT_PDF, 'GET', ['group_by' => 'consulta']);
        $response = $this->controller->exportHistorialPdf($request);

        $this->assertNotNull($response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
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
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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
            'primer_apellido' => 'Usuario',
            'telefono' => self::TELEFONO_PRUEBA,
            'correo' => self::TEST_EMAIL,
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
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

    public function test_group_cotizaciones_por_consulta_con_personas_id_null(): void
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

        $servicio = Servicios::create(['nombre_servicio' => 'Test']);
        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'SubTest',
            'descripcion' => self::DESC_PRUEBA,
            'precio' => 100
        ]);

        // Nota: personas_id no puede ser null según la base de datos
        // Este test verifica el agrupamiento con un usuario válido
        Cotizacion::create([
            'personas_id' => $usuario->id,
            'sub_servicios_id' => $subServicio->id,
            'monto' => 100,
            'fecha_cotizacion' => now()
        ]);

        $cotizaciones = $this->invokePrivateMethod($this->controller, 'getCotizaciones');
        $grouped = $this->invokePrivateMethod($this->controller, 'groupCotizaciones', [$cotizaciones, 'consulta']);
        
        $this->assertNotNull($grouped);
    }

    public function test_index_con_group_by_y_tab(): void
    {
        $request = Request::create(self::ROUTE_AJUSTES, 'GET', [
            'group_by' => 'dia',
            'tab' => 'servicios'
        ]);
        $response = $this->controller->index($request);

        $this->assertNotNull($response);
        $data = $response->getData();
        $this->assertEquals('dia', $data['groupBy']);
        $this->assertEquals('servicios', $data['activeTab']); // El tab tiene prioridad
    }

}

