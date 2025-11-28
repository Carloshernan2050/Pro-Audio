<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests Unitarios para el Modelo Usuario
 */
class UsuarioModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Instanciación y Configuración
    // ============================================

    public function test_usuario_instancia_correctamente(): void
    {
        $usuario = new Usuario();
        
        $this->assertInstanceOf(Usuario::class, $usuario);
    }

    public function test_usuario_tiene_guard_name(): void
    {
        $usuario = new Usuario();
        
        $this->assertEquals('web', $usuario->guardName());
    }

    public function test_usuario_tabla_correcta(): void
    {
        $usuario = new Usuario();
        
        $this->assertEquals('personas', $usuario->getTable());
    }

    public function test_usuario_fillable_attributes(): void
    {
        $usuario = new Usuario();
        $fillable = $usuario->getFillable();
        
        $this->assertContains('primer_nombre', $fillable);
        $this->assertContains('correo', $fillable);
        $this->assertContains('contrasena', $fillable);
        $this->assertContains('foto_perfil', $fillable);
    }

    public function test_usuario_usa_has_roles_trait(): void
    {
        $usuario = new Usuario();
        
        $traits = class_uses_recursive(get_class($usuario));
        $this->assertContains(\Spatie\Permission\Traits\HasRoles::class, $traits);
    }
}

