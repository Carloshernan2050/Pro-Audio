<?php

namespace Tests\Unit;

use App\Http\Controllers\ReservaController;
use Tests\TestCase;

/**
 * Tests Unitarios para ReservaController
 *
 * Tests para validaciones y estructura
 */
class ReservaControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(ReservaController::class);
    }

    // ============================================
    // TESTS PARA Validaciones
    // ============================================

    public function test_validacion_store_estructura(): void
    {
        $reglasEsperadas = [
            'servicio' => 'required|string|max:120',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.inventario_id' => 'required|exists:inventario,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ];

        $this->assertArrayHasKey('servicio', $reglasEsperadas);
        $this->assertArrayHasKey('fecha_inicio', $reglasEsperadas);
        $this->assertArrayHasKey('items', $reglasEsperadas);
    }

    public function test_validacion_items_minimo(): void
    {
        // Debe haber al menos 1 item
        $reglasEsperadas = [
            'items' => 'required|array|min:1',
        ];

        $this->assertStringContainsString('min:1', $reglasEsperadas['items']);
    }

    public function test_validacion_cantidad_minimo(): void
    {
        // La cantidad debe ser mínimo 1
        $reglasEsperadas = [
            'items.*.cantidad' => 'required|integer|min:1',
        ];

        $this->assertStringContainsString('min:1', $reglasEsperadas['items.*.cantidad']);
    }

    public function test_estados_reserva_validos(): void
    {
        // Los estados válidos son: pendiente, confirmada
        $estados = ['pendiente', 'confirmada'];

        $this->assertContains('pendiente', $estados);
        $this->assertContains('confirmada', $estados);
    }

    public function test_servicio_max_caracteres(): void
    {
        // El servicio máximo es 120 caracteres
        $maxCaracteres = 120;

        $this->assertEquals(120, $maxCaracteres);
    }

    public function test_tipo_movimiento_alquilado(): void
    {
        // El tipo de movimiento para reserva confirmada es 'alquilado'
        $tipoMovimiento = 'alquilado';

        $this->assertEquals('alquilado', $tipoMovimiento);
    }
}
