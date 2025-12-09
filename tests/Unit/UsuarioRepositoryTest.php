<?php

namespace Tests\Unit;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UsuarioRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UsuarioRepository::class);
    }

    public function test_find_by_correo_encuentra_usuario(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test@example.com',
            'telefono' => '1234567890',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $encontrado = $this->repository->findByCorreo('test@example.com');

        $this->assertNotNull($encontrado);
        $this->assertEquals($usuario->id, $encontrado->id);
        $this->assertEquals('test@example.com', $encontrado->correo);
    }

    public function test_find_by_correo_retorna_null_si_no_existe(): void
    {
        $encontrado = $this->repository->findByCorreo('noexiste@example.com');

        $this->assertNull($encontrado);
    }

    public function test_find_encuentra_usuario(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test2@example.com',
            'telefono' => '1234567891',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $encontrado = $this->repository->find($usuario->id);

        $this->assertNotNull($encontrado);
        $this->assertEquals($usuario->id, $encontrado->id);
    }

    public function test_find_retorna_null_si_no_existe(): void
    {
        $encontrado = $this->repository->find(999);

        $this->assertNull($encontrado);
    }

    public function test_create_crea_usuario(): void
    {
        $data = [
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test3@example.com',
            'telefono' => '1234567892',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ];

        $usuario = $this->repository->create($data);

        $this->assertInstanceOf(Usuario::class, $usuario);
        $this->assertEquals('Test', $usuario->primer_nombre);
        $this->assertEquals('test3@example.com', $usuario->correo);
    }

    public function test_update_actualiza_usuario(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test4@example.com',
            'telefono' => '1234567893',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $data = [
            'primer_nombre' => 'Actualizado',
            'correo' => 'actualizado@example.com',
        ];

        $result = $this->repository->update($usuario->id, $data);

        $this->assertTrue($result);
        $usuario->refresh();
        $this->assertEquals('Actualizado', $usuario->primer_nombre);
        $this->assertEquals('actualizado@example.com', $usuario->correo);
    }

    public function test_update_retorna_false_si_usuario_no_existe(): void
    {
        $data = [
            'primer_nombre' => 'Actualizado',
        ];

        $result = $this->repository->update(999, $data);

        $this->assertFalse($result);
    }
}

