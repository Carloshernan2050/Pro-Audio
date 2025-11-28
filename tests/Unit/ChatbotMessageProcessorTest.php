<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatbotMessageProcessor;
use App\Services\ChatbotTextProcessor;
use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotSuggestionGenerator;
use App\Services\ChatbotResponseBuilder;
use App\Services\ChatbotSubServicioService;
use Illuminate\Http\Request;
use Mockery;

/**
 * Tests Unitarios para ChatbotMessageProcessor
 */
class ChatbotMessageProcessorTest extends TestCase
{
    private const ROUTE_TEST = '/test';
    private const MENSAJE_ALQUILER = 'necesito alquiler';
    private const MENSAJE_5_DIAS = 'por 5 dias';

    protected ChatbotMessageProcessor $processor;
    protected $textProcessorMock;
    protected $intentionDetectorMock;
    protected $suggestionGeneratorMock;
    protected $responseBuilderMock;
    protected $subServicioServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->textProcessorMock = Mockery::mock(ChatbotTextProcessor::class);
        $this->intentionDetectorMock = Mockery::mock(ChatbotIntentionDetector::class);
        $this->suggestionGeneratorMock = Mockery::mock(ChatbotSuggestionGenerator::class);
        $this->responseBuilderMock = Mockery::mock(ChatbotResponseBuilder::class);
        $this->subServicioServiceMock = Mockery::mock(ChatbotSubServicioService::class);
        
        $this->processor = new ChatbotMessageProcessor(
            $this->textProcessorMock,
            $this->intentionDetectorMock,
            $this->suggestionGeneratorMock,
            $this->responseBuilderMock,
            $this->subServicioServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // TESTS PARA procesarMensajeTexto()
    // ============================================

    public function test_procesar_mensaje_texto_vacio(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->responseBuilderMock
            ->shouldReceive('responderConOpciones')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            '',
            '',
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_catalogo(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'catalogo']);
        
        $this->textProcessorMock
            ->shouldReceive('corregirOrtografia')
            ->andReturn('catalogo');
        
        $this->responseBuilderMock
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);
        
        session(['chat.selecciones' => [1, 2]]);
        session(['chat.days' => 3]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'catalogo',
            'catalogo',
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_fuera_de_tema(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(false);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerencias')
            ->andReturn(['alquiler', 'animacion']);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('extraerMejorSugerencia')
            ->andReturn(['token' => 'test', 'sugerencia' => 'alquiler']);
        
        session()->forget('chat.selecciones');
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'hola como estas',
            'hola como estas',
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
    }

    public function test_procesar_mensaje_texto_con_intenciones(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn(['Alquiler']);
        
        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn(['Alquiler']);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn(['alquiler']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->responseBuilderMock
            ->shouldReceive('solicitarConfirmacionIntencion')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_ALQUILER,
            self::MENSAJE_ALQUILER,
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_actualizacion_dias(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'cotizacion']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(true);
        
        session(['chat.selecciones' => [1, 2]]);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerItemsSeleccionados')
            ->andReturn($collectionMock);
        
        $this->responseBuilderMock
            ->shouldReceive('responderCotizacion')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_5_DIAS,
            self::MENSAJE_5_DIAS,
            5,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_buscar_subservicios(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones relacionadas']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn(['equipos']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);
        
        $this->responseBuilderMock
            ->shouldReceive('responderOpciones')
            ->once()
            ->andReturn($responseMock);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'equipos de sonido',
            'equipos de sonido',
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_continuacion(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(true);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerencias')
            ->andReturn(['alquiler']);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('extraerMejorSugerencia')
            ->andReturn(['token' => 'test', 'sugerencia' => 'alquiler']);
        
        session()->forget('chat.selecciones');
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'tambien',
            'tambien',
            0,
            0,
            ['Alquiler'],
            true
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_dias_y_selecciones(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'cotizacion actualizada']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(true);
        
        session(['chat.selecciones' => [1, 2], 'chat.days' => 3]);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerItemsSeleccionados')
            ->with([1, 2])
            ->andReturn($collectionMock);
        
        $this->responseBuilderMock
            ->shouldReceive('responderCotizacion')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_5_DIAS,
            self::MENSAJE_5_DIAS,
            5,
            3,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_error_en_actualizacion_dias(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'error']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(true);
        
        session(['chat.selecciones' => [1, 2]]);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerItemsSeleccionados')
            ->andThrow(new \Exception('Error de BD'));
        
        $this->responseBuilderMock
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_5_DIAS,
            self::MENSAJE_5_DIAS,
            5,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_intenciones_sesion(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->responseBuilderMock
            ->shouldReceive('mostrarOpcionesConIntenciones')
            ->once()
            ->andReturn($responseMock);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'continuar',
            'continuar',
            0,
            0,
            ['Alquiler'],
            true
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_ml_intent(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn(['Alquiler']);
        
        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn(['Alquiler']);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn(['alquiler']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->responseBuilderMock
            ->shouldReceive('solicitarConfirmacionIntencion')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_ALQUILER,
            self::MENSAJE_ALQUILER,
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_intenciones_sesion_y_continuacion(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->responseBuilderMock
            ->shouldReceive('mostrarOpcionesConIntenciones')
            ->once()
            ->andReturn($responseMock);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'continuar',
            'continuar',
            0,
            0,
            ['Alquiler'],
            true
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_agregado(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn(['Animación']);
        
        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn(['Animación']);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn(['animacion']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->responseBuilderMock
            ->shouldReceive('solicitarConfirmacionIntencion')
            ->once()
            ->andReturn($responseMock);
        
        session(['chat.selecciones' => [1], 'chat.intenciones' => ['Alquiler']]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'tambien animacion',
            'tambien animacion',
            0,
            0,
            ['Alquiler'],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_nueva_consulta(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn(['Alquiler']);
        
        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn(['Alquiler']);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn(['alquiler']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->responseBuilderMock
            ->shouldReceive('solicitarConfirmacionIntencion')
            ->once()
            ->andReturn($responseMock);
        
        session()->forget('chat.selecciones');
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_ALQUILER,
            self::MENSAJE_ALQUILER,
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_dias_y_continuacion(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(true);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerencias')
            ->andReturn(['alquiler']);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('extraerMejorSugerencia')
            ->andReturn(['token' => 'test', 'sugerencia' => 'alquiler']);
        
        session()->forget('chat.selecciones');
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'continuar',
            'continuar',
            0,
            3,
            [],
            true
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_fuera_de_tema_con_selecciones(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(false);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerencias')
            ->andReturn(['alquiler', 'animacion']);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => []]]);
        
        $this->suggestionGeneratorMock
            ->shouldReceive('extraerMejorSugerencia')
            ->andReturn(['token' => 'test', 'sugerencia' => 'alquiler']);
        
        session(['chat.selecciones' => [1, 2], 'chat.days' => 3]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_5_DIAS,
            self::MENSAJE_5_DIAS,
            5,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_actualizacion_dias_items_vacios(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'catalogo']);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(true);
        
        session(['chat.selecciones' => [1, 2]]);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerItemsSeleccionados')
            ->andReturn($collectionMock);
        
        $this->responseBuilderMock
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_5_DIAS,
            self::MENSAJE_5_DIAS,
            5,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_buscar_subservicios_con_mensaje_vacio(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones relacionadas']);
        
        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);
        
        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);
        
        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);
        
        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);
        
        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);
        
        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);
        
        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);
        
        $this->responseBuilderMock
            ->shouldReceive('responderOpciones')
            ->once()
            ->andReturn($responseMock);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            '',
            '',
            0,
            0,
            [],
            false
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }
}

