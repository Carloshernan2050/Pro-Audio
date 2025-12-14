<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use App\Repositories\CotizacionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para CotizacionRepository
 */
class CotizacionRepositoryUnitTest extends TestCase
{
    use RefreshDatabase;

    private CotizacionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CotizacionRepository();
    }

    public function test_repository_instancia_correctamente(): void
    {
        $this->assertInstanceOf(CotizacionRepository::class, $this->repository);
    }

    public function test_getByPersonasId_retorna_cotizaciones_del_cliente(): void
    {
        // Crear datos de prueba
        $usuario1 = Usuario::create([
            'primer_nombre' => 'Carlos',
            'primer_apellido' => 'Molina',
            'correo' => 'carlos@test.com',
            'telefono' => '3001234567',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $usuario2 = Usuario::create([
            'primer_nombre' => 'Ana',
            'primer_apellido' => 'López',
            'correo' => 'ana@test.com',
            'telefono' => '3019876543',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $servicio = Servicios::create([
            'nombre_servicio' => 'Animación',
            'descripcion' => 'Servicio de animación',
        ]);

        $subServicio1 = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'DJ Profesional',
            'descripcion' => 'DJ profesional',
            'precio' => 600000,
        ]);

        $subServicio2 = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Animador',
            'descripcion' => 'Animador de eventos',
            'precio' => 350000,
        ]);

        // Crear cotizaciones para usuario1
        $cotizacion1 = Cotizacion::create([
            'personas_id' => $usuario1->id,
            'sub_servicios_id' => $subServicio1->id,
            'monto' => 600000,
            'fecha_cotizacion' => now()->subDays(2),
        ]);

        $cotizacion2 = Cotizacion::create([
            'personas_id' => $usuario1->id,
            'sub_servicios_id' => $subServicio2->id,
            'monto' => 350000,
            'fecha_cotizacion' => now()->subDays(1),
        ]);

        // Crear cotización para usuario2 (no debe aparecer)
        $cotizacion3 = Cotizacion::create([
            'personas_id' => $usuario2->id,
            'sub_servicios_id' => $subServicio1->id,
            'monto' => 600000,
            'fecha_cotizacion' => now(),
        ]);

        // Ejecutar el método
        $resultado = $this->repository->getByPersonasId($usuario1->id);

        // Verificaciones
        $this->assertCount(2, $resultado);
        $this->assertTrue($resultado->contains($cotizacion1));
        $this->assertTrue($resultado->contains($cotizacion2));
        $this->assertFalse($resultado->contains($cotizacion3));

        // Verificar que las relaciones están cargadas
        $primeraCotizacion = $resultado->first();
        $this->assertTrue($primeraCotizacion->relationLoaded('persona'));
        $this->assertTrue($primeraCotizacion->relationLoaded('subServicio'));
        $this->assertTrue($primeraCotizacion->subServicio->relationLoaded('servicio'));

        // Verificar orden descendente por fecha
        $fechas = $resultado->pluck('fecha_cotizacion')->toArray();
        $this->assertGreaterThanOrEqual($fechas[1]->timestamp, $fechas[0]->timestamp);
    }

    public function test_getByPersonasId_retorna_vacio_si_no_hay_cotizaciones(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'User',
            'correo' => 'test@test.com',
            'telefono' => '3000000000',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => 1,
        ]);

        $resultado = $this->repository->getByPersonasId($usuario->id);

        $this->assertCount(0, $resultado);
        $this->assertTrue($resultado->isEmpty());
    }
}

