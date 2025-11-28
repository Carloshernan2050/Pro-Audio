<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\ChatbotController;
use App\Services\ChatbotTextProcessor;
use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotSuggestionGenerator;
use App\Services\ChatbotResponseBuilder;
use App\Services\ChatbotMessageProcessor;
use App\Services\ChatbotSubServicioService;
use App\Services\ChatbotSessionManager;

/**
 * Tests Unitarios para ChatbotController
 *
 * NOTA: Estos tests están obsoletos porque los métodos privados
 * fueron movidos a servicios separados. Este test se mantiene
 * solo para verificar que el controlador se puede instanciar.
 */
class ChatbotControllerUnitTest extends TestCase
{
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
        
        // Crear mocks de todos los servicios requeridos
        $this->textProcessor = $this->createMock(ChatbotTextProcessor::class);
        $this->intentionDetector = $this->createMock(ChatbotIntentionDetector::class);
        $this->suggestionGenerator = $this->createMock(ChatbotSuggestionGenerator::class);
        $this->responseBuilder = $this->createMock(ChatbotResponseBuilder::class);
        $this->messageProcessor = $this->createMock(ChatbotMessageProcessor::class);
        $this->subServicioService = $this->createMock(ChatbotSubServicioService::class);
        $this->sessionManager = $this->createMock(ChatbotSessionManager::class);
        
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

    /**
     * Test básico para verificar que el controlador se puede instanciar
     */
    public function test_controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ChatbotController::class, $this->controller);
    }

    // NOTA: Los tests anteriores que intentaban probar métodos privados
    // ya no son válidos porque esos métodos fueron movidos a servicios.
    // Se recomienda crear tests unitarios para los servicios directamente:
    // - ChatbotTextProcessorTest
    // - ChatbotIntentionDetectorTest
    // - ChatbotSessionManagerTest
    // etc.
}

