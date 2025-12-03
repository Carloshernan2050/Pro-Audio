<?php

namespace Tests\Unit;

use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotMessageProcessor;
use App\Services\ChatbotResponseBuilder;
use App\Services\ChatbotSubServicioService;
use App\Services\ChatbotSuggestionGenerator;
use App\Services\ChatbotTextProcessor;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * Tests Unitarios para ChatbotMessageProcessor
 */
class ChatbotMessageProcessorTest extends TestCase
{
    private const ROUTE_TEST = '/test';

    private const MENSAJE_ALQUILER = 'necesito alquiler';

    private const MENSAJE_5_DIAS = 'por 5 dias';

    private const RESPUESTA_OPCIONES_RELACIONADAS = 'opciones relacionadas';

    private const MENSAJE_EQUIPOS_SONIDO = 'equipos de sonido';

    private const INTENCION_ANIMACION = 'Animación';

    private const MENSAJE_TAMBIEN_ANIMACION = 'tambien animacion';

    private const MENSAJE_CATALOGO_ACENTO = 'catálogo';

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

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);

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
        $responseMock = response()->json(['respuesta' => self::RESPUESTA_OPCIONES_RELACIONADAS]);

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
            self::MENSAJE_EQUIPOS_SONIDO,
            self::MENSAJE_EQUIPOS_SONIDO,
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
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

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
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);

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

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);

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

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);

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
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

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

    public function test_procesar_mensaje_texto_con_agregado(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([self::INTENCION_ANIMACION]);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([self::INTENCION_ANIMACION]);

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
            ->shouldReceive('mostrarOpcionesConIntenciones')
            ->once()
            ->andReturn($responseMock);

        session(['chat.selecciones' => [1], 'chat.intenciones' => ['Alquiler']]);

        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_TAMBIEN_ANIMACION,
            self::MENSAJE_TAMBIEN_ANIMACION,
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
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

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
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);

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

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

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
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);

        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);

        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

        $this->responseBuilderMock
            ->shouldReceive('mostrarCatalogoJson')
            ->zeroOrMoreTimes()
            ->andReturn(response()->json(['respuesta' => 'catalogo']));

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

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);

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
        $responseMock = response()->json(['respuesta' => self::RESPUESTA_OPCIONES_RELACIONADAS]);

        // Cuando el mensaje está vacío, se llama directamente a responderConOpciones
        $this->responseBuilderMock
            ->shouldReceive('responderConOpciones')
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

    public function test_procesar_mensaje_texto_con_catalogo_corregido(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'catalogo']);

        $this->responseBuilderMock
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);

        session(['chat.selecciones' => [1, 2], 'chat.days' => 3]);

        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_CATALOGO_ACENTO,
            'catalogo',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_actualizacion_dias_dias_cero(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');

        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);

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

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(true);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);

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
            'test',
            'test',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_intenciones_vacias_y_no_relacionado(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);

        // "hola" ahora es detectado como saludo, así que debe mockear responderConOpciones
        $this->responseBuilderMock
            ->shouldReceive('responderConOpciones')
            ->once()
            ->andReturn($responseMock);

        session()->forget('chat.selecciones');

        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'hola',
            'hola',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_sugerencia_aplicada(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST', ['sugerencia_aplicada' => true]);
        $responseMock = response()->json(['respuesta' => 'confirmacion']);

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
            ->andReturn([['token' => 'alqiler', 'sugerencias' => ['alquiler']]]);

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

    public function test_procesar_mensaje_texto_con_error_en_sugerencias(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');

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
            ->andThrow(new \Exception('Error'));

        $this->responseBuilderMock
            ->shouldReceive('solicitarConfirmacionIntencion')
            ->once()
            ->andReturn(response()->json(['respuesta' => 'confirmacion']));

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

    public function test_procesar_mensaje_texto_fuera_de_tema_con_selecciones_y_dias(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(false);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(true);

        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);

        session(['chat.selecciones' => [1, 2], 'chat.days' => 3]);

        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);

        $this->subServicioServiceMock
            ->shouldReceive('obtenerItemsSeleccionados')
            ->andReturn($collectionMock);

        $this->responseBuilderMock
            ->shouldReceive('responderCotizacion')
            ->once()
            ->andReturn(response()->json(['respuesta' => 'cotizacion']));

        // Cuando hay selecciones y solo días, no debería responder fuera de tema
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_5_DIAS,
            self::MENSAJE_5_DIAS,
            5,
            0,
            [],
            false
        );

        // Debería procesar como actualización de días, no como fuera de tema
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_con_detectar_intenciones_sesion_y_dias(): void
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

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

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

        // Caso: continuación con días > 0 y intenciones de sesión
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'continuar',
            'continuar',
            0,
            3,
            ['Alquiler'],
            true
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_buscar_subservicios_con_mensaje_corregido_vacio(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'He encontrado opciones relacionadas. Selecciona los sub-servicios que deseas cotizar:']);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

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

        // Caso: mensaje corregido vacío pero hay tokens
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'equipos',
            '',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('He encontrado opciones relacionadas', $data['respuesta']);
    }

    public function test_procesar_mensaje_texto_responder_con_intenciones_sin_sugerencia_aplicada(): void
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
            ->andReturn(true);

        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(false);

        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isEmpty')->andReturn(false);

        $this->subServicioServiceMock
            ->shouldReceive('obtenerSubServiciosPorIntenciones')
            ->andReturn($collectionMock);

        $this->responseBuilderMock
            ->shouldReceive('mostrarOpcionesConIntenciones')
            ->once()
            ->andReturn($responseMock);

        session(['chat.selecciones' => [1], 'chat.intenciones' => ['Alquiler']]);

        // Caso: agregado (no es nueva petición) y no proviene de sugerencia
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'tambien alquiler',
            'tambien alquiler',
            0,
            0,
            ['Alquiler'],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_obtener_sugerencias_con_error(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);

        // "hola" ahora es detectado como saludo, así que debe mockear responderConOpciones
        $this->responseBuilderMock
            ->shouldReceive('responderConOpciones')
            ->once()
            ->andReturn($responseMock);

        session()->forget('chat.selecciones');

        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'hola',
            'hola',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    /**
     * Test para cubrir líneas 326-328: obtenerSugerenciasConHints con excepción
     */
    public function test_obtener_sugerencias_con_hints_catch_excepcion_cubre_lineas_326_328(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(false);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

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
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);

        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);

        // Forzar excepción en generarSugerenciasPorToken para cubrir el catch (líneas 326-328)
        // Primero generarSugerencias debe ejecutarse exitosamente (línea 324)
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerencias')
            ->once()
            ->with('xyz123')
            ->andReturn(['alquiler', 'animacion']);

        // Luego generarSugerenciasPorToken debe lanzar excepción (línea 325)
        // Esto hace que el catch se ejecute (línea 326) y cubra las líneas 327-328
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerenciasPorToken')
            ->once()
            ->with('xyz123')
            ->andThrow(new \Exception('Error en generarSugerenciasPorToken'));

        // Cuando la excepción ocurre, el catch ejecuta:
        // - Línea 327: $sugerencias = [];
        // - Línea 328: $tokenHints = $this->suggestionGenerator->fallbackTokenHints($mensajeOriginal);
        $this->suggestionGeneratorMock
            ->shouldReceive('fallbackTokenHints')
            ->once()
            ->with('xyz123')
            ->andReturn([['token' => 'test', 'sugerencias' => ['alquiler']]]);

        $this->suggestionGeneratorMock
            ->shouldReceive('extraerMejorSugerencia')
            ->andReturn(['token' => 'test', 'sugerencia' => 'alquiler']);

        session()->forget('chat.selecciones');

        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'xyz123',
            'xyz123',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('sugerencias', $data);
        $this->assertArrayHasKey('tokenHints', $data);
    }

    /**
     * Test adicional para cubrir líneas 326-328 cuando la excepción ocurre en generarSugerencias
     */
    public function test_obtener_sugerencias_con_hints_catch_excepcion_generar_sugerencias_cubre_lineas_326_328(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(false);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

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
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(false);

        $this->subServicioServiceMock
            ->shouldReceive('buscarSubServiciosRelacionados')
            ->andReturn($collectionMock);

        // Forzar excepción en generarSugerencias (línea 324) para cubrir el catch (líneas 326-328)
        $this->suggestionGeneratorMock
            ->shouldReceive('generarSugerencias')
            ->once()
            ->with('test2')
            ->andThrow(new \Exception('Error en generarSugerencias'));

        // Cuando generarSugerencias lanza excepción, el catch ejecuta:
        // - Línea 327: $sugerencias = [];
        // - Línea 328: $tokenHints = $this->suggestionGenerator->fallbackTokenHints($mensajeOriginal);
        $this->suggestionGeneratorMock
            ->shouldReceive('fallbackTokenHints')
            ->once()
            ->with('test2')
            ->andReturn([['token' => 'test', 'sugerencias' => ['alquiler']]]);

        $this->suggestionGeneratorMock
            ->shouldReceive('extraerMejorSugerencia')
            ->once()
            ->andReturn(['token' => 'test', 'sugerencia' => 'alquiler']);

        session()->forget('chat.selecciones');

        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            'test2',
            'test2',
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('sugerencias', $data);
        $this->assertArrayHasKey('tokenHints', $data);
    }

    // ============================================
    // TESTS ADICIONALES PARA casos límite
    // ============================================

    public function test_procesar_mensaje_texto_calcular_dias_para_respuesta_con_mensaje_vacio(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);

        // Cuando el mensaje está vacío, se llama directamente a responderConOpciones
        $this->responseBuilderMock
            ->shouldReceive('responderConOpciones')
            ->once()
            ->andReturn($responseMock);

        session()->forget('chat.selecciones');

        // Mensaje vacío debería retornar null para daysForResponse
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

    public function test_procesar_mensaje_texto_calcular_dias_para_respuesta_con_dias_mayor_cero(): void
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

        // Con días > 0, debería usar esos días
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_ALQUILER,
            self::MENSAJE_ALQUILER,
            5,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
    }

    public function test_procesar_mensaje_texto_combinar_intenciones_con_agregado_y_sesion(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'opciones']);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([self::INTENCION_ANIMACION]);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([self::INTENCION_ANIMACION]);

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

        $this->responseBuilderMock
            ->shouldReceive('mostrarOpcionesConIntenciones')
            ->once()
            ->andReturn($responseMock);

        session(['chat.selecciones' => [1], 'chat.intenciones' => ['Alquiler']]);

        // Con agregado y sesión, debería combinar intenciones
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_TAMBIEN_ANIMACION,
            self::MENSAJE_TAMBIEN_ANIMACION,
            0,
            0,
            ['Alquiler'],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        // Verificar que las intenciones se combinaron
        $intenciones = session('chat.intenciones', []);
        $this->assertContains('Alquiler', $intenciones);
        $this->assertContains(self::INTENCION_ANIMACION, $intenciones);
    }

    public function test_procesar_mensaje_texto_combinar_intenciones_nueva_consulta_limpia_selecciones(): void
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

        session(['chat.selecciones' => [1, 2, 3]]);

        // Nueva consulta debería limpiar selecciones
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
        $this->assertEmpty(session('chat.selecciones', []));
    }

    public function test_procesar_mensaje_texto_verificar_mensaje_fuera_de_tema_con_solo_dias_y_selecciones(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json(['respuesta' => 'cotizacion']);

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(false);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSoloDias')
            ->andReturn(true);

        $this->textProcessorMock
            ->shouldReceive('extraerTokens')
            ->andReturn([]);

        $this->textProcessorMock
            ->shouldReceive('verificarSiEsAgregado')
            ->andReturn(false);

        session(['chat.selecciones' => [1, 2], 'chat.days' => 3]);

        $collectionMock = Mockery::mock(\Illuminate\Support\Collection::class);
        $collectionMock->shouldReceive('isNotEmpty')->andReturn(true);

        $this->subServicioServiceMock
            ->shouldReceive('obtenerItemsSeleccionados')
            ->andReturn($collectionMock);

        $this->responseBuilderMock
            ->shouldReceive('responderCotizacion')
            ->once()
            ->andReturn($responseMock);

        // Cuando hay solo días y selecciones, debería procesar como actualización de días
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

    public function test_procesar_mensaje_texto_es_solicitud_catalogo_con_acento_en_original_y_corregido(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json([
            'respuesta' => 'catalogo',
            'optionGroups' => [],
        ]);

        $this->responseBuilderMock
            ->shouldReceive('mostrarCatalogoJson')
            ->once()
            ->andReturn($responseMock);

        session(['chat.selecciones' => [1, 2], 'chat.days' => 3]);

        // Debería reconocer 'catálogo' con acento tanto en el mensaje original como corregido
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_CATALOGO_ACENTO,
            self::MENSAJE_CATALOGO_ACENTO,
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
        $this->assertArrayHasKey('optionGroups', $data);
    }

    public function test_procesar_mensaje_texto_buscar_subservicios_con_mensaje_corregido_no_vacio(): void
    {
        $request = Request::create(self::ROUTE_TEST, 'POST');
        $responseMock = response()->json([
            'respuesta' => 'Con base en tu consulta, estas opciones están relacionadas. Selecciona los sub-servicios que deseas cotizar:',
            'optionGroups' => [],
        ]);

        $this->intentionDetectorMock
            ->shouldReceive('detectarIntenciones')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('clasificarPorTfidf')
            ->andReturn([]);

        $this->intentionDetectorMock
            ->shouldReceive('esRelacionado')
            ->andReturn(true);

        $this->intentionDetectorMock
            ->shouldReceive('validarIntencionesContraMensaje')
            ->andReturn([]);

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
        $collectionMock->shouldReceive('isEmpty')->andReturn(true);
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

        // Con mensaje corregido no vacío, debería usar el prefijo diferente
        $resultado = $this->processor->procesarMensajeTexto(
            $request,
            self::MENSAJE_EQUIPOS_SONIDO,
            self::MENSAJE_EQUIPOS_SONIDO,
            0,
            0,
            [],
            false
        );

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('Con base en tu consulta', $data['respuesta']);
    }
}
