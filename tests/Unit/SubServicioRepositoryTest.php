<?php

namespace Tests\Unit;

use App\Models\Servicios;
use App\Models\SubServicios;
use App\Repositories\SubServicioRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubServicioRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SubServicioRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(SubServicioRepository::class);
    }

    public function test_buscar_por_tokens_normalizados_retorna_vacio_si_array_vacio(): void
    {
        // Esta línea cubre la línea 77 de SubServicioRepository
        $resultado = $this->repository->buscarPorTokensNormalizados([], 'nombre_normalizado', 12);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertTrue($resultado->isEmpty());
    }
}

