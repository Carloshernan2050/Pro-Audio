<?php

namespace Tests\Unit;

use App\Models\Servicios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para el Modelo Servicios
 *
 * Tests que ejecutan el código del modelo con base de datos
 */
class ServiciosModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Método crearUnico()
    // ============================================

    public function test_servicios_crear_unico_crea_nuevo(): void
    {
        $nombre = 'Servicio Nuevo';

        $servicio = Servicios::crearUnico($nombre);

        $this->assertInstanceOf(Servicios::class, $servicio);
        $this->assertEquals($nombre, $servicio->nombre_servicio);
        $this->assertDatabaseHas('servicios', [
            'nombre_servicio' => $nombre,
        ]);
    }

    public function test_servicios_crear_unico_devuelve_existente(): void
    {
        $nombre = 'Servicio Existente';

        // Crear servicio primero
        $servicio1 = Servicios::create([
            'nombre_servicio' => $nombre,
            'descripcion' => 'Descripción original',
            'icono' => 'icono-original',
        ]);

        // Intentar crear el mismo servicio
        $servicio2 = Servicios::crearUnico($nombre);

        // Debe devolver el existente, no crear uno nuevo
        $this->assertEquals($servicio1->id, $servicio2->id);
        $this->assertDatabaseCount('servicios', 1);
    }

    public function test_servicios_crear_unico_mismo_nombre_diferente_caso(): void
    {
        $nombre1 = 'Servicio Test';
        $nombre2 = 'servicio test'; // Diferente case

        $servicio1 = Servicios::crearUnico($nombre1);
        $servicio2 = Servicios::crearUnico($nombre2);

        // Deberían ser diferentes porque el nombre es case-sensitive
        $this->assertNotEquals($servicio1->id, $servicio2->id);
        $this->assertDatabaseCount('servicios', 2);
    }

    // ============================================
    // TESTS PARA Relación subServicios()
    // ============================================

    public function test_servicios_tiene_relacion_subservicios(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        // Verificar que la relación funciona
        $subServicios = $servicio->subServicios;

        $this->assertCount(0, $subServicios);
    }
}

