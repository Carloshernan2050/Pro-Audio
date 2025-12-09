<?php

namespace Tests\Unit;

use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotTextProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test aislado para cubrir líneas 229-230 de ChatbotIntentionDetector
 * obtenerCacheTfidf() catch block cuando hay excepción
 * 
 * Esta clase se ejecuta en un proceso separado para asegurar que el cache estático esté vacío.
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
class ChatbotIntentionDetectorCatchExceptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test para cubrir líneas 229-230 de ChatbotIntentionDetector
     * obtenerCacheTfidf() catch block cuando hay excepción
     */
    public function test_obtener_cache_tfidf_catch_exception_cubre_lineas_229_230(): void
    {
        // Crear una nueva instancia del detector en un proceso aislado
        // para asegurar que el cache estático esté vacío
        $textProcessor = app(\App\Services\ChatbotTextProcessor::class);
        $detector = app(\App\Services\ChatbotIntentionDetector::class);

        // Usar reflection para acceder al método obtenerCacheTfidf
        $reflection = new \ReflectionClass($detector);
        $method = $reflection->getMethod('obtenerCacheTfidf');
        $method->setAccessible(true);

        // Guardar información de la conexión original
        $connection = \Illuminate\Support\Facades\DB::connection();
        $connectionName = $connection->getName();
        $originalConfig = config("database.connections.{$connectionName}");

        try {
            // Cambiar temporalmente la configuración para que la conexión falle
            config(["database.connections.{$connectionName}.database" => ':nonexistent:']);

            // Limpiar la instancia de conexión para forzar que use la nueva configuración
            \Illuminate\Support\Facades\DB::purge($connectionName);

            // Ejecutar el método - esto debería lanzar una excepción
            // al intentar consultar SubServicios porque la BD está desconectada
            // y el catch block (líneas 229-230) debería manejarla
            // Como este test se ejecuta en un proceso separado, el cache estático debería ser null
            $result = $method->invoke($detector);

            // Debería retornar un array vacío debido al catch (línea 230)
            // cuando el cache es null y se lanza la excepción
            $this->assertIsArray($result);
            $this->assertEquals([], $result);
        } finally {
            // Restaurar la configuración original
            config(["database.connections.{$connectionName}" => $originalConfig]);

            // Limpiar y reconectar
            \Illuminate\Support\Facades\DB::purge($connectionName);
            \Illuminate\Support\Facades\DB::reconnect($connectionName);
        }
    }
}

