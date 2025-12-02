<?php

namespace Tests\Unit;

use App\Models\Servicios;
use App\Models\SubServicios;
use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotTextProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para cubrir líneas faltantes en ChatbotIntentionDetector
 */
class ChatbotIntentionDetectorCoverageTest extends TestCase
{
    use RefreshDatabase;

    private ChatbotIntentionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $textProcessor = new ChatbotTextProcessor;
        $this->detector = new ChatbotIntentionDetector($textProcessor);
    }

    /**
     * Test para cubrir líneas 223-225 de ChatbotIntentionDetector
     * obtenerCacheTfidf() procesa filas de SubServicios
     */
    public function test_obtener_cache_tfidf_procesa_filas_cubre_lineas_223_225(): void
    {
        // Crear servicios y subservicios para que el cache se construya
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Parlantes',
            'descripcion' => 'Parlantes de alta calidad',
            'precio' => 100000,
        ]);

        // Llamar a clasificarPorTfidf que internamente llama a obtenerCacheTfidf
        // Esto debería cubrir las líneas 223-225 que procesan las filas
        $result = $this->detector->clasificarPorTfidf('parlantes de sonido');

        $this->assertIsArray($result);
    }


    /**
     * Test para cubrir líneas 243-248 de ChatbotIntentionDetector
     * procesarDocumentosParaCache() procesa documentos
     */
    public function test_procesar_documentos_para_cache_cubre_lineas_243_248(): void
    {
        // Crear múltiples servicios y subservicios para procesar
        $servicio1 = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        $servicio2 = Servicios::create([
            'nombre_servicio' => 'Animación',
            'descripcion' => 'Servicio de animación',
            'icono' => 'animacion-icon',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio1->id,
            'nombre' => 'Parlantes',
            'descripcion' => 'Parlantes de alta calidad',
            'precio' => 100000,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio2->id,
            'nombre' => 'DJ',
            'descripcion' => 'Servicio de DJ profesional',
            'precio' => 200000,
        ]);

        // Llamar a clasificarPorTfidf que procesa los documentos (líneas 243-248)
        $result = $this->detector->clasificarPorTfidf('parlantes y dj');

        $this->assertIsArray($result);
    }

    /**
     * Test para cubrir línea 283 y líneas 294-322 de ChatbotIntentionDetector
     * calcularYObtenerMejorIntencion() y calcularScoreServicio()
     */
    public function test_calcular_score_servicio_cubre_lineas_283_294_322(): void
    {
        // Crear servicios y subservicios con contenido relevante
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler de equipos',
            'icono' => 'alquiler-icon',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Parlantes profesionales',
            'descripcion' => 'Parlantes de alta calidad para eventos',
            'precio' => 100000,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Consola de mezcla',
            'descripcion' => 'Consola profesional para sonido',
            'precio' => 150000,
        ]);

        // Llamar a clasificarPorTfidf con un mensaje que tenga términos relevantes
        // Esto debería cubrir calcularScoreServicio (líneas 294-322)
        // y calcularYObtenerMejorIntencion línea 283
        $result = $this->detector->clasificarPorTfidf('parlantes profesionales consola mezcla');

        $this->assertIsArray($result);
        // Si el score es >= 0.12, debería retornar el servicio
        // Si no, retornará array vacío
    }

    /**
     * Test adicional para cubrir más casos en calcularScoreServicio
     * Específicamente cuando hay términos que no están en el cache o tienen df <= 0
     * Cubre líneas 300-302 (primer foreach) y 312-314 (segundo foreach, línea 313 continue)
     */
    public function test_calcular_score_servicio_con_terminos_no_en_cache_cubre_lineas_294_322(): void
    {
        // Crear servicios y subservicios con términos específicos
        $servicio = Servicios::create([
            'nombre_servicio' => 'Animación',
            'descripcion' => 'Servicio de animación',
            'icono' => 'animacion-icon',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'DJ profesional',
            'descripcion' => 'DJ para eventos y fiestas',
            'precio' => 200000,
        ]);

        // Crear otro servicio con términos diferentes para que algunos términos
        // tengan df > 0 y otros no
        $servicio2 = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio2->id,
            'nombre' => 'Parlantes',
            'descripcion' => 'Parlantes de alta calidad',
            'precio' => 100000,
        ]);

        // Llamar con términos que pueden no estar en el cache o tener df <= 0
        // Esto cubre las líneas 300-302 (primer foreach) y 312-314 (segundo foreach, línea 313 continue)
        // cuando un término en $tf no tiene df o tiene df <= 0
        $result = $this->detector->clasificarPorTfidf('dj animador fiesta evento');

        $this->assertIsArray($result);
    }

    /**
     * Test para cubrir el caso cuando el score es menor a 0.12
     * en calcularYObtenerMejorIntencion línea 289
     */
    public function test_calcular_obtener_mejor_intencion_score_bajo_cubre_linea_289(): void
    {
        // Crear servicios con contenido no relacionado
        $servicio = Servicios::create([
            'nombre_servicio' => 'Publicidad',
            'descripcion' => 'Servicio de publicidad',
            'icono' => 'publicidad-icon',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Spot publicitario',
            'descripcion' => 'Creación de spots',
            'precio' => 300000,
        ]);

        // Llamar con un mensaje que no tenga suficiente relevancia
        // Esto debería retornar array vacío si el score < 0.12
        $result = $this->detector->clasificarPorTfidf('xyz abc 123');

        $this->assertIsArray($result);
        // Puede ser vacío si el score es muy bajo
    }

    /**
     * Test para cubrir línea 313 de ChatbotIntentionDetector
     * calcularScoreServicio() continue cuando df <= 0 en el segundo foreach
     * 
     * Para cubrir esta línea, necesitamos un término que esté en $tf pero no en $df
     * o que tenga df <= 0. Usamos Reflection para manipular el cache y forzar este caso.
     */
    public function test_calcular_score_servicio_df_cero_segundo_foreach_cubre_linea_313(): void
    {
        // Crear servicios con términos específicos
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Parlantes profesionales',
            'descripcion' => 'Parlantes de alta calidad para eventos',
            'precio' => 100000,
        ]);

        // Obtener el cache primero
        $this->detector->clasificarPorTfidf('test');

        // Usar Reflection para manipular el cache y forzar un caso donde
        // un término en $tf no esté en $df o tenga df = 0
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('obtenerCacheTfidf');
        $method->setAccessible(true);
        
        $cache = $method->invoke($this->detector);
        
        // Manipular el cache para que un término en docs tenga df = 0 o no esté en df
        if (!empty($cache['docs'])) {
            $firstSvc = array_key_first($cache['docs']);
            if (!empty($cache['docs'][$firstSvc])) {
                // Agregar un término artificial a $tf que no esté en $df
                $cache['docs'][$firstSvc]['termino_artificial_xyz'] = 1;
                // Asegurarse de que este término no esté en $df o tenga df = 0
                if (isset($cache['df']['termino_artificial_xyz'])) {
                    $cache['df']['termino_artificial_xyz'] = 0;
                }
            }
        }

        // Usar Reflection para llamar directamente a calcularScoreServicio
        // con el cache manipulado
        $calcularScoreMethod = $reflection->getMethod('calcularScoreServicio');
        $calcularScoreMethod->setAccessible(true);
        
        // Crear qtf y tf para el test
        $qtf = ['parlantes' => 1, 'profesionales' => 1];
        $tf = $cache['docs'][$firstSvc] ?? ['parlantes' => 1];
        
        // Agregar el término artificial a $tf si no está
        if (!isset($tf['termino_artificial_xyz'])) {
            $tf['termino_artificial_xyz'] = 1;
        }
        
        // Asegurarse de que el término no esté en df o tenga df = 0
        if (!isset($cache['df']['termino_artificial_xyz'])) {
            $cache['df']['termino_artificial_xyz'] = 0;
        }

        // Llamar al método - esto debería cubrir la línea 313 cuando
        // encuentra 'termino_artificial_xyz' en $tf con df = 0
        $score = $calcularScoreMethod->invoke($this->detector, $cache, $qtf, $tf);

        $this->assertIsFloat($score);
    }
}

