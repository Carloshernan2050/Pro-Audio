<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatbotSessionManager;
use App\Services\ChatbotTextProcessor;

/**
 * Tests Unitarios para ChatbotSessionManager
 */
class ChatbotSessionManagerTest extends TestCase
{
    protected ChatbotSessionManager $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionManager = new ChatbotSessionManager();
    }

    // ============================================
    // TESTS PARA limpiarSesionChat()
    // ============================================

    public function test_limpiar_sesion_chat_limpia_selecciones(): void
    {
        session(['chat.selecciones' => [1, 2, 3]]);
        $this->sessionManager->limpiarSesionChat();
        $this->assertNull(session('chat.selecciones'));
    }

    public function test_limpiar_sesion_chat_limpia_intenciones(): void
    {
        session(['chat.intenciones' => ['Alquiler']]);
        $this->sessionManager->limpiarSesionChat();
        $this->assertNull(session('chat.intenciones'));
    }

    public function test_limpiar_sesion_chat_limpia_days(): void
    {
        session(['chat.days' => 5]);
        $this->sessionManager->limpiarSesionChat();
        $this->assertNull(session('chat.days'));
    }

    // ============================================
    // TESTS PARA obtenerDiasParaRespuesta()
    // ============================================

    public function test_obtener_dias_para_respuesta_con_dias(): void
    {
        $resultado = $this->sessionManager->obtenerDiasParaRespuesta(5);
        $this->assertEquals(5, $resultado);
    }

    public function test_obtener_dias_para_respuesta_con_session(): void
    {
        session(['chat.days' => 3]);
        $resultado = $this->sessionManager->obtenerDiasParaRespuesta(0);
        $this->assertEquals(3, $resultado);
    }

    public function test_obtener_dias_para_respuesta_sin_dias(): void
    {
        session()->forget('chat.days');
        $resultado = $this->sessionManager->obtenerDiasParaRespuesta(0);
        $this->assertNull($resultado);
    }

    // ============================================
    // TESTS PARA extraerDiasDelRequest()
    // ============================================

    public function test_extraer_dias_del_request_con_numero(): void
    {
        $textProcessor = new ChatbotTextProcessor();
        $resultado = $this->sessionManager->extraerDiasDelRequest(
            'necesito por 3 dias',
            false,
            0,
            $textProcessor
        );
        $this->assertEquals(3, $resultado);
    }

    public function test_extraer_dias_del_request_con_palabras(): void
    {
        $textProcessor = new ChatbotTextProcessor();
        $resultado = $this->sessionManager->extraerDiasDelRequest(
            'necesito por tres dias',
            false,
            0,
            $textProcessor
        );
        $this->assertEquals(3, $resultado);
    }

    public function test_extraer_dias_del_request_con_continuacion(): void
    {
        $textProcessor = new ChatbotTextProcessor();
        session(['chat.days' => 5]);
        $resultado = $this->sessionManager->extraerDiasDelRequest(
            'tambien',
            true,
            5,
            $textProcessor
        );
        $this->assertEquals(5, $resultado);
    }

    public function test_extraer_dias_del_request_guarda_en_session(): void
    {
        $textProcessor = new ChatbotTextProcessor();
        session()->forget('chat.days');
        $this->sessionManager->extraerDiasDelRequest(
            'por 4 dias',
            false,
            0,
            $textProcessor
        );
        $this->assertEquals(4, session('chat.days'));
    }
}

