<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use App\Services\CalendarioDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Tests para cubrir líneas faltantes en CalendarioDataService
 */
class CalendarioDataServiceCoverageTest extends TestCase
{
    use RefreshDatabase;

    private CalendarioDataService $service;
    private Usuario $persona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CalendarioDataService::class);

        $this->persona = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test@example.com',
            'telefono' => '1234567890',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);
    }

    /**
     * Test para cubrir línea 91 - continue cuando mov no existe o inventario no existe
     */
    public function test_obtener_productos_desde_items_item_sin_movimiento_cubre_linea_91(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto Test',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-05',
            'evento' => 'Alquiler',
            'descripcion_evento' => 'Evento test',
        ]);

        // Crear item con un movimiento válido (para evitar constraint)
        $item = CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $calendario->load('items');

        // Crear colecciones donde el movimiento del item NO existe
        // (usando un ID diferente al del item)
        $movimientos = collect(); // Colección vacía - el movimiento no está
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        // Usar Reflection para invocar el método privado
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('obtenerProductosDesdeItems');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $calendario, $movimientos, $inventarios);

        // Debe retornar array vacío porque el item se saltó (línea 91 continue)
        // ya que $mov es null (no existe en la colección)
        $this->assertIsArray($result);
        $this->assertEmpty($result[0] ?? []); // productosLista
        $this->assertEquals(0, $result[1] ?? 0); // cantidadTotal
    }

    /**
     * Test para cubrir línea 91 - continue cuando inventario no existe en colección
     */
    public function test_obtener_productos_desde_items_item_sin_inventario_en_coleccion_cubre_linea_91(): void
    {
        $inventario = Inventario::create([
            'descripcion' => 'Producto Test',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-05',
            'evento' => 'Alquiler',
            'descripcion_evento' => 'Evento test',
        ]);

        $item = CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $calendario->load('items');

        // Crear colecciones donde el movimiento existe pero el inventario NO está en la colección
        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect(); // Colección vacía - el inventario no está

        // Usar Reflection para invocar el método privado
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('obtenerProductosDesdeItems');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $calendario, $movimientos, $inventarios);

        // Debe retornar array vacío porque el item se saltó (línea 91 continue)
        // ya que el inventario no está en la colección
        $this->assertIsArray($result);
        $this->assertEmpty($result[0] ?? []); // productosLista
        $this->assertEquals(0, $result[1] ?? 0); // cantidadTotal
    }
}

