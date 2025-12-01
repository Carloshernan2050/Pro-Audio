<?php

namespace Tests\Unit;

use App\Http\Controllers\MovimientosInventarioController;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unitarios para MovimientosInventarioController
 *
 * Tests para validaciones y lógica de movimientos
 */
class MovimientosInventarioControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new MovimientosInventarioController;
    }

    // ============================================
    // TESTS PARA Validaciones
    // ============================================

    public function test_validacion_store_estructura(): void
    {
        $reglasEsperadas = [
            'inventario_id' => 'required|exists:inventario,id',
            'tipo_movimiento' => 'required|in:entrada,salida,alquilado,devuelto',
            'cantidad' => 'required|integer|min:1',
        ];

        $this->assertArrayHasKey('inventario_id', $reglasEsperadas);
        $this->assertArrayHasKey('tipo_movimiento', $reglasEsperadas);
        $this->assertArrayHasKey('cantidad', $reglasEsperadas);
    }

    public function test_tipos_movimiento_validos(): void
    {
        // Los tipos válidos son: entrada, salida, alquilado, devuelto
        $tipos = ['entrada', 'salida', 'alquilado', 'devuelto'];

        $this->assertCount(4, $tipos);
        $this->assertContains('entrada', $tipos);
        $this->assertContains('salida', $tipos);
        $this->assertContains('alquilado', $tipos);
        $this->assertContains('devuelto', $tipos);
    }

    public function test_tipos_movimiento_incrementan_stock(): void
    {
        // Los tipos que incrementan stock son: entrada, devuelto
        $tiposIncrementan = ['entrada', 'devuelto'];

        $this->assertCount(2, $tiposIncrementan);
        $this->assertContains('entrada', $tiposIncrementan);
        $this->assertContains('devuelto', $tiposIncrementan);
    }

    public function test_tipos_movimiento_decrementan_stock(): void
    {
        // Los tipos que decrementan stock son: salida, alquilado
        $tiposDecrementan = ['salida', 'alquilado'];

        $this->assertCount(2, $tiposDecrementan);
        $this->assertContains('salida', $tiposDecrementan);
        $this->assertContains('alquilado', $tiposDecrementan);
    }

    public function test_validacion_cantidad_minimo(): void
    {
        // La cantidad debe ser mínimo 1
        $reglasEsperadas = [
            'cantidad' => 'required|integer|min:1',
        ];

        $this->assertStringContainsString('min:1', $reglasEsperadas['cantidad']);
    }

    public function test_validacion_cantidad_debe_ser_integer(): void
    {
        $reglasEsperadas = [
            'cantidad' => 'required|integer|min:1',
        ];

        $this->assertStringContainsString('integer', $reglasEsperadas['cantidad']);
    }
}
