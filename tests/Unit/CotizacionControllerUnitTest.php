<?php

namespace Tests\Unit;

use App\Http\Controllers\CotizacionController;
use App\Models\Cotizacion;
use App\Repositories\Interfaces\CotizacionRepositoryInterface;
use App\Services\ChatbotSessionManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests Unitarios para CotizacionController
 */
class CotizacionControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_COTIZACION = '/cotizacion';

    private const ROUTE_HISTORIAL = '/cotizaciones/historial';

    protected $controller;

    protected $sessionManager;

    protected $cotizacionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionManager = \Mockery::mock(ChatbotSessionManager::class);
        $this->cotizacionRepository = \Mockery::mock(CotizacionRepositoryInterface::class);
        $this->controller = new CotizacionController($this->sessionManager, $this->cotizacionRepository);
    }

    public function test_controller_instancia_correctamente(): void
    {
        $this->assertInstanceOf(CotizacionController::class, $this->controller);
    }

    public function test_store_guarda_cotizacion_exitosamente(): void
    {
        session(['usuario_id' => 1, 'chat.selecciones' => [1, 2], 'chat.days' => 3]);

        $this->sessionManager
            ->shouldReceive('guardarCotizacion')
            ->once()
            ->with(1, [1, 2], 3)
            ->andReturn(true);

        $request = Request::create(self::ROUTE_COTIZACION, 'POST', [
            'selecciones' => [1, 2],
            'dias' => 3,
            'personas_id' => 1,
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Cotización guardada correctamente.', $responseData['message']);
    }

    public function test_store_valida_selecciones_vacias(): void
    {
        session(['usuario_id' => 1]);

        $request = Request::create(self::ROUTE_COTIZACION, 'POST', [
            'selecciones' => [],
            'dias' => 1,
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('sin selecciones', $responseData['error']);
    }

    public function test_store_valida_personas_id_vacio(): void
    {
        $request = Request::create(self::ROUTE_COTIZACION, 'POST', [
            'selecciones' => [1, 2],
            'dias' => 1,
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('sin usuario identificado', $responseData['error']);
    }

    public function test_store_usa_valores_de_session(): void
    {
        session(['usuario_id' => 5, 'chat.selecciones' => [3, 4], 'chat.days' => 2]);

        $this->sessionManager
            ->shouldReceive('guardarCotizacion')
            ->once()
            ->with(5, [3, 4], 2)
            ->andReturn(true);

        $request = Request::create(self::ROUTE_COTIZACION, 'POST');

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
    }

    public function test_store_catch_exception(): void
    {
        session(['usuario_id' => 1, 'chat.selecciones' => [1, 2], 'chat.days' => 1]);

        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Error al guardar cotización/'));

        $this->sessionManager
            ->shouldReceive('guardarCotizacion')
            ->once()
            ->with(1, [1, 2], 1)
            ->andThrow(new \Exception('Database connection error'));

        $request = Request::create(self::ROUTE_COTIZACION, 'POST', [
            'selecciones' => [1, 2],
            'dias' => 1,
            'personas_id' => 1,
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Error al guardar la cotización.', $responseData['error']);
    }

    public function test_historial_redirige_sin_autenticacion(): void
    {
        session()->forget('usuario_id');

        $request = Request::create(self::ROUTE_HISTORIAL, 'GET');

        $response = $this->controller->historial($request);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(route('usuarios.inicioSesion'), $response->getTargetUrl());
        $this->assertTrue(session()->has('error'));
    }

    public function test_historial_muestra_cotizaciones_sin_agrupacion(): void
    {
        session(['usuario_id' => 1]);

        $cotizacion = new Cotizacion();
        $cotizacion->id = 1;
        $cotizacion->personas_id = 1;
        $cotizacion->sub_servicios_id = 1;
        $cotizacion->monto = 100000;
        $cotizacion->fecha_cotizacion = now();

        $cotizaciones = new EloquentCollection([$cotizacion]);

        $this->cotizacionRepository
            ->shouldReceive('getByPersonasId')
            ->once()
            ->with(1)
            ->andReturn($cotizaciones);

        $request = Request::create(self::ROUTE_HISTORIAL, 'GET');

        $response = $this->controller->historial($request);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('usuarios.historial_cotizaciones', $response->getName());
        $this->assertArrayHasKey('cotizaciones', $response->getData());
        $this->assertArrayHasKey('groupBy', $response->getData());
        $this->assertNull($response->getData()['groupBy']);
    }

    public function test_historial_agrupa_por_dia(): void
    {
        session(['usuario_id' => 1]);

        $fecha = now()->setDate(2025, 12, 13)->setTime(10, 0, 0);

        $cotizacion1 = new Cotizacion();
        $cotizacion1->id = 1;
        $cotizacion1->personas_id = 1;
        $cotizacion1->sub_servicios_id = 1;
        $cotizacion1->monto = 100000;
        $cotizacion1->fecha_cotizacion = $fecha;

        $cotizacion2 = new Cotizacion();
        $cotizacion2->id = 2;
        $cotizacion2->personas_id = 1;
        $cotizacion2->sub_servicios_id = 2;
        $cotizacion2->monto = 200000;
        $cotizacion2->fecha_cotizacion = $fecha;

        $cotizaciones = new EloquentCollection([$cotizacion1, $cotizacion2]);

        $this->cotizacionRepository
            ->shouldReceive('getByPersonasId')
            ->once()
            ->with(1)
            ->andReturn($cotizaciones);

        $request = Request::create(self::ROUTE_HISTORIAL, 'GET', ['group_by' => 'dia']);

        $response = $this->controller->historial($request);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('usuarios.historial_cotizaciones', $response->getName());
        $data = $response->getData();
        $this->assertEquals('dia', $data['groupBy']);
        $this->assertNotNull($data['groupedCotizaciones']);
    }

    public function test_historial_agrupa_por_consulta(): void
    {
        session(['usuario_id' => 1]);

        $fecha = now()->setTime(23, 6, 7);

        $cotizacion1 = new Cotizacion();
        $cotizacion1->id = 1;
        $cotizacion1->personas_id = 1;
        $cotizacion1->sub_servicios_id = 1;
        $cotizacion1->monto = 100000;
        $cotizacion1->fecha_cotizacion = $fecha;

        $cotizaciones = new EloquentCollection([$cotizacion1]);

        $this->cotizacionRepository
            ->shouldReceive('getByPersonasId')
            ->once()
            ->with(1)
            ->andReturn($cotizaciones);

        $request = Request::create(self::ROUTE_HISTORIAL, 'GET', ['group_by' => 'consulta']);

        $response = $this->controller->historial($request);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('usuarios.historial_cotizaciones', $response->getName());
        $data = $response->getData();
        $this->assertEquals('consulta', $data['groupBy']);
        $this->assertNotNull($data['groupedCotizaciones']);
    }

    public function test_historial_catch_exception(): void
    {
        session(['usuario_id' => 1]);

        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Error al obtener historial de cotizaciones/'));

        $this->cotizacionRepository
            ->shouldReceive('getByPersonasId')
            ->once()
            ->with(1)
            ->andThrow(new \Exception('Database error'));

        $request = Request::create(self::ROUTE_HISTORIAL, 'GET');

        $response = $this->controller->historial($request);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('usuarios.historial_cotizaciones', $response->getName());
        $data = $response->getData();
        $this->assertInstanceOf(Collection::class, $data['cotizaciones']);
        $this->assertTrue($data['cotizaciones']->isEmpty());
        $this->assertNull($data['groupBy']);
        $this->assertNull($data['groupedCotizaciones']);
    }
}

