<?php

namespace Tests\Unit;

use App\Http\Controllers\CotizacionController;
use App\Services\ChatbotSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests Unitarios para CotizacionController
 */
class CotizacionControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_COTIZACION = '/cotizacion';

    protected $controller;

    protected $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionManager = \Mockery::mock(ChatbotSessionManager::class);
        $this->controller = new CotizacionController($this->sessionManager);
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
}

