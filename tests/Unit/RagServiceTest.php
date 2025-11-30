<?php

namespace Tests\Unit;

use App\Services\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RagServiceTest extends TestCase
{
    use RefreshDatabase;

    private RagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RagService();
    }

    public function test_recuperar_contexto_encontra_resultados(): void
    {
        DB::table('servicios')->insert([
            [
                'nombre_servicio' => 'Alquiler de Equipos',
                'descripcion' => 'Equipos de sonido profesionales',
            ],
            [
                'nombre_servicio' => 'Animación',
                'descripcion' => 'Servicios de animación para eventos',
            ],
        ]);

        $contexto = $this->service->recuperarContexto('Equipos');

        $this->assertNotNull($contexto);
        $this->assertStringContainsString('Datos relacionados en la base de datos:', $contexto);
        $this->assertStringContainsString('Alquiler de Equipos', $contexto);
        $this->assertStringContainsString('Equipos de sonido profesionales', $contexto);
    }

    public function test_recuperar_contexto_busca_por_descripcion(): void
    {
        DB::table('servicios')->insert([
            [
                'nombre_servicio' => 'Servicio A',
                'descripcion' => 'Animación profesional',
            ],
        ]);

        $contexto = $this->service->recuperarContexto('profesional');

        $this->assertNotNull($contexto);
        $this->assertStringContainsString('Servicio A', $contexto);
        $this->assertStringContainsString('Animación profesional', $contexto);
    }

    public function test_recuperar_contexto_sin_resultados(): void
    {
        $contexto = $this->service->recuperarContexto('NoExiste');

        $this->assertNull($contexto);
    }

    public function test_recuperar_contexto_limita_resultados(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DB::table('servicios')->insert([
                'nombre_servicio' => "Servicio {$i}",
                'descripcion' => "Descripción {$i}",
            ]);
        }

        $contexto = $this->service->recuperarContexto('Servicio');

        $this->assertNotNull($contexto);
        $lineas = explode("\n", $contexto);
        $lineasConDatos = array_filter($lineas, fn($linea) => str_starts_with($linea, '-'));
        $this->assertLessThanOrEqual(6, count($lineasConDatos)); // 1 línea de encabezado + máximo 5 resultados
    }

    public function test_recuperar_contexto_formato_correcto(): void
    {
        DB::table('servicios')->insert([
            [
                'nombre_servicio' => 'Test Servicio',
                'descripcion' => 'Test Descripción',
            ],
        ]);

        $contexto = $this->service->recuperarContexto('Test');

        $this->assertNotNull($contexto);
        $this->assertStringContainsString('Datos relacionados en la base de datos:', $contexto);
        $this->assertStringContainsString('- Test Servicio: Test Descripción', $contexto);
    }
}

