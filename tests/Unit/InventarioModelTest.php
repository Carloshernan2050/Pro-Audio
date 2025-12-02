<?php

namespace Tests\Unit;

use App\Models\Inventario;
use App\Models\MovimientosInventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para el Modelo Inventario
 *
 * Tests que ejecutan el código del modelo con base de datos
 */
class InventarioModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Instanciación y Configuración
    // ============================================

    public function test_inventario_instancia_correctamente(): void
    {
        $inventario = new Inventario;

        $this->assertInstanceOf(Inventario::class, $inventario);
    }

    public function test_inventario_tabla_correcta(): void
    {
        $inventario = new Inventario;

        $this->assertEquals('inventario', $inventario->getTable());
    }

    public function test_inventario_no_tiene_timestamps(): void
    {
        $inventario = new Inventario;

        $this->assertFalse($inventario->timestamps);
    }

    public function test_inventario_fillable_attributes(): void
    {
        $inventario = new Inventario;
        $fillable = $inventario->getFillable();

        $this->assertContains('descripcion', $fillable);
        $this->assertContains('stock', $fillable);
        $this->assertCount(2, $fillable);
    }

    public function test_inventario_tiene_relacion_movimientos(): void
    {
        $inventario = new Inventario;

        // Verificar que el método movimientos existe
        $this->assertTrue(method_exists($inventario, 'movimientos'));
    }

    public function test_inventario_usa_has_factory_trait(): void
    {
        $inventario = new Inventario;

        $traits = class_uses_recursive(get_class($inventario));
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }

    // ============================================
    // TESTS PARA Creación y Persistencia
    // ============================================

    public function test_inventario_se_puede_crear(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);

        $this->assertDatabaseHas('inventario', [
            'id' => $inventario->id,
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);
    }

    public function test_inventario_se_puede_actualizar(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto original',
            'stock' => 10,
        ]);

        $inventario->update([
            'descripcion' => 'Producto actualizado',
            'stock' => 20,
        ]);

        $this->assertDatabaseHas('inventario', [
            'id' => $inventario->id,
            'descripcion' => 'Producto actualizado',
            'stock' => 20,
        ]);
    }

    public function test_inventario_se_puede_eliminar(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto a eliminar',
            'stock' => 5,
        ]);

        $id = $inventario->id;
        $inventario->delete();

        $this->assertDatabaseMissing('inventario', [
            'id' => $id,
        ]);
    }

    // ============================================
    // TESTS PARA Relación con Movimientos
    // ============================================

    public function test_inventario_tiene_relacion_movimientos_funcional(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto con movimientos',
            'stock' => 10,
        ]);

        // Crear movimiento de inventario
        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento de prueba',
        ]);

        // Verificar que la relación funciona
        $movimientos = $inventario->movimientos;
        
        $this->assertCount(1, $movimientos);
        $this->assertEquals($movimiento->id, $movimientos->first()->id);
    }

    public function test_inventario_relacion_movimientos_vacia(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto sin movimientos',
            'stock' => 10,
        ]);

        $movimientos = $inventario->movimientos;

        $this->assertCount(0, $movimientos);
    }

    public function test_inventario_relacion_movimientos_multiple(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto con múltiples movimientos',
            'stock' => 10,
        ]);

        MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento 1',
        ]);

        MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'salida',
            'cantidad' => 2,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento 2',
        ]);

        $movimientos = $inventario->movimientos;

        $this->assertCount(2, $movimientos);
    }

    // ============================================
    // TESTS PARA Atributos Masivos
    // ============================================

    public function test_inventario_fillable_descripcion(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Nueva descripción',
            'stock' => 15,
        ]);

        $this->assertEquals('Nueva descripción', $inventario->descripcion);
    }

    public function test_inventario_fillable_stock(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto',
            'stock' => 25,
        ]);

        $this->assertEquals(25, $inventario->stock);
    }
}
