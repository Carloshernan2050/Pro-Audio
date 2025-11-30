<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\ChatbotController;
use App\Services\ChatbotTextProcessor;
use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotSuggestionGenerator;
use App\Services\ChatbotResponseBuilder;
use App\Services\ChatbotMessageProcessor;
use App\Services\ChatbotSubServicioService;
use App\Services\ChatbotSessionManager;
use Illuminate\Http\Request;
use Mockery;

/**
 * Tests Unitarios para ChatbotController
 *
 * NOTA: Estos tests están obsoletos porque los métodos privados
 * fueron movidos a servicios separados. Este test se mantiene
 * solo para verificar que el controlador se puede instanciar.
 */
class ChatbotControllerUnitTest extends TestCase
{
    private const ROUTE_CHAT_ENVIAR = '/chat/enviar';

    protected $controller;
    protected $textProcessor;
    protected $intentionDetector;
    protected $suggestionGenerator;
    protected $responseBuilder;
    protected $messageProcessor;
    protected $subServicioService;
    protected $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear mocks de todos los servicios requeridos usando Mockery
        $this->textProcessor = Mockery::mock(ChatbotTextProcessor::class);
        $this->intentionDetector = Mockery::mock(ChatbotIntentionDetector::class);
        $this->suggestionGenerator = Mockery::mock(ChatbotSuggestionGenerator::class);
        $this->responseBuilder = Mockery::mock(ChatbotResponseBuilder::class);
        $this->messageProcessor = Mockery::mock(ChatbotMessageProcessor::class);
        $this->subServicioService = Mockery::mock(ChatbotSubServicioService::class);
        $this->sessionManager = Mockery::mock(ChatbotSessionManager::class);
        
        // Instanciar el controlador con los mocks
        $this->controller = new ChatbotController(
            $this->textProcessor,
            $this->intentionDetector,
            $this->suggestionGenerator,
            $this->responseBuilder,
            $this->messageProcessor,
            $this->subServicioService,
            $this->sessionManager
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test básico para verificar que el controlador se puede instanciar
     */
    public function test_controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ChatbotController::class, $this->controller);
    }

    public function test_index_retorna_vista(): void
    {
        $response = $this->controller->index();
        
        $this->assertNotNull($response);
    }

    public function test_enviar_procesa_mensaje(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'mensaje' => 'hola'
        ]);

        $this->textProcessor
            ->shouldReceive('corregirOrtografia')
            ->once()
            ->andReturn('hola');

        $this->textProcessor
            ->shouldReceive('esContinuacion')
            ->once()
            ->andReturn(false);

        $this->sessionManager
            ->shouldReceive('extraerDiasDelRequest')
            ->once()
            ->andReturn(0);

        $responseMock = response()->json(['respuesta' => 'test']);
        
        $this->messageProcessor
            ->shouldReceive('procesarMensajeTexto')
            ->once()
            ->andReturn($responseMock);

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
    }

    public function test_enviar_procesa_seleccion(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'seleccion' => [1, 2, 3],
            'dias' => 5
        ]);

        // Cuando hay selección y días > 0, no se llama a esContinuacion ni extraerDiasDelRequest
        // porque los días vienen directamente del request

        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);

        $this->sessionManager
            ->shouldReceive('obtenerDiasParaRespuesta')
            ->zeroOrMoreTimes()
            ->andReturn(5);

        $this->textProcessor
            ->shouldReceive('corregirOrtografia')
            ->zeroOrMoreTimes()
            ->andReturn('');

        $this->textProcessor
            ->shouldReceive('esContinuacion')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $this->subServicioService
            ->shouldReceive('obtenerItemsSeleccionados')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($collectionMock);

        $responseMock = response()->json(['respuesta' => 'cotizacion']);
        
        $this->responseBuilder
            ->shouldReceive('responderCotizacion')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::type('array'))
            ->andReturn($responseMock);
        
        // En caso de error, se llama a mostrarCatalogoJson
        $this->responseBuilder
            ->shouldReceive('mostrarCatalogoJson')
            ->zeroOrMoreTimes()
            ->andReturn(response()->json(['respuesta' => 'catalogo']));

        session(['chat.days' => 0]);

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
    }

    public function test_enviar_maneja_error(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'mensaje' => 'test'
        ]);

        $this->textProcessor
            ->shouldReceive('corregirOrtografia')
            ->once()
            ->andThrow(new \Exception('Error'));

        $this->intentionDetector
            ->shouldReceive('detectarIntenciones')
            ->once()
            ->andReturn([]);

        $responseMock = response()->json(['respuesta' => 'catalogo']);
        
        $this->responseBuilder
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);

        session(['chat.days' => 1, 'chat.selecciones' => []]);

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
    }

    public function test_enviar_procesa_seleccion_vacio(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'seleccion' => [],
            'dias' => 5
        ]);

        $this->textProcessor
            ->shouldReceive('corregirOrtografia')
            ->zeroOrMoreTimes()
            ->andReturn('');

        $this->textProcessor
            ->shouldReceive('esContinuacion')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $this->sessionManager
            ->shouldReceive('obtenerDiasParaRespuesta')
            ->once()
            ->andReturn(5);

        $responseMock = response()->json(['respuesta' => 'catalogo']);
        
        $this->responseBuilder
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);

        session(['chat.days' => 0]);

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
    }

    public function test_enviar_confirm_intencion(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'confirm_intencion' => true,
            'intenciones' => ['Alquiler'],
            'dias' => 3
        ]);

        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);

        $this->subServicioService
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->once()
            ->andReturn($collectionMock);

        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->responseBuilder
            ->shouldReceive('responderOpciones')
            ->once()
            ->andReturn($responseMock);

        session(['chat.selecciones' => []]);

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
    }

    public function test_enviar_limpiar_cotizacion(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'limpiar_cotizacion' => true
        ]);

        $this->sessionManager
            ->shouldReceive('limpiarSesionChat')
            ->once();

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
    }

    public function test_enviar_terminar_cotizacion(): void
    {
        $request = Request::create(self::ROUTE_CHAT_ENVIAR, 'POST', [
            'terminar_cotizacion' => true
        ]);

        $this->sessionManager
            ->shouldReceive('guardarCotizacion')
            ->once();

        $this->sessionManager
            ->shouldReceive('limpiarSesionChat')
            ->once();

        session(['chat.selecciones' => [1, 2], 'chat.days' => 3, 'usuario_id' => 1]);

        $response = $this->controller->enviar($request);
        
        $this->assertNotNull($response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('limpiar_chat', $data);
    }

    // NOTA: Los tests anteriores que intentaban probar métodos privados
    // ya no son válidos porque esos métodos fueron movidos a servicios.
    // Se recomienda crear tests unitarios para los servicios directamente:
    // - ChatbotTextProcessorTest
    // - ChatbotIntentionDetectorTest
    // - ChatbotSessionManagerTest
    // etc.
}

