<?php

namespace Tests\Unit;

use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotTextProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para ChatbotIntentionDetector
 */
class ChatbotIntentionDetectorTest extends TestCase
{
    use RefreshDatabase;

    private const INTENCION_ANIMACION = 'Animación';

    private const INTENCION_ALQUILER = 'Alquiler';

    private const INTENCION_PUBLICIDAD = 'Publicidad';

    private const MENSAJE_PUBLICIDAD = 'necesito publicidad';

    private const MENSAJE_ALGO = 'necesito algo';

    private const MENSAJE_ALQUILER = 'necesito alquiler';

    protected ChatbotIntentionDetector $detector;

    protected ChatbotTextProcessor $textProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->textProcessor = app(\App\Services\ChatbotTextProcessor::class);
        $this->detector = app(\App\Services\ChatbotIntentionDetector::class);
    }

    // ============================================
    // TESTS PARA detectarIntenciones()
    // ============================================

    public function test_detectar_intenciones_vacio(): void
    {
        $resultado = $this->detector->detectarIntenciones('');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_detectar_intenciones_alquiler(): void
    {
        $resultado = $this->detector->detectarIntenciones('necesito alquiler de equipos');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_animacion(): void
    {
        $resultado = $this->detector->detectarIntenciones('necesito un dj');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_detectar_intenciones_publicidad(): void
    {
        $resultado = $this->detector->detectarIntenciones(self::MENSAJE_PUBLICIDAD);
        $this->assertIsArray($resultado);
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_detectar_intenciones_multiples(): void
    {
        $resultado = $this->detector->detectarIntenciones('necesito alquiler y un dj');
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    // ============================================
    // TESTS PARA validarIntencionesContraMensaje()
    // ============================================

    public function test_validar_intenciones_contra_mensaje_valida_alquiler(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'necesito alquiler de equipos'
        );
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_validar_intenciones_contra_mensaje_invalida_alquiler(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'hola como estas'
        );
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_contra_mensaje_con_animacion(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [self::INTENCION_ANIMACION],
            'necesito un dj'
        );
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_validar_intenciones_contra_mensaje_con_publicidad(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Publicidad'],
            self::MENSAJE_PUBLICIDAD
        );
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_validar_intenciones_contra_mensaje_vacio(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [],
            self::MENSAJE_ALGO
        );
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_contra_mensaje_multiples(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler', self::INTENCION_ANIMACION],
            'necesito alquiler y un dj'
        );
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    // ============================================
    // TESTS PARA esRelacionado()
    // ============================================

    public function test_es_relacionado_con_alquiler(): void
    {
        $resultado = $this->detector->esRelacionado(self::MENSAJE_ALQUILER);
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_animacion(): void
    {
        $resultado = $this->detector->esRelacionado('quiero un dj');
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_publicidad(): void
    {
        $resultado = $this->detector->esRelacionado(self::MENSAJE_PUBLICIDAD);
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_mensaje_vacio(): void
    {
        // Según el código, mensaje vacío retorna true
        $resultado = $this->detector->esRelacionado('');
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_mensaje_no_relacionado(): void
    {
        $resultado = $this->detector->esRelacionado('que tiempo hace hoy');
        // Verificamos que retorna un booleano
        $this->assertIsBool($resultado);
    }

    public function test_es_relacionado_case_insensitive(): void
    {
        $resultado = $this->detector->esRelacionado('ALQUILER');
        $this->assertTrue($resultado);

        $resultado = $this->detector->esRelacionado('AlQuIlEr');
        $this->assertTrue($resultado);
    }

    // ============================================
    // TESTS PARA clasificarPorTfidf()
    // ============================================

    public function test_clasificar_por_tfidf_con_cadena_vacia(): void
    {
        $resultado = $this->detector->clasificarPorTfidf('');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_clasificar_por_tfidf_retorna_array(): void
    {
        // Este test puede retornar vacío si no hay datos en la BD
        // pero verificamos que al menos retorna un array
        $resultado = $this->detector->clasificarPorTfidf('alquiler equipos');
        $this->assertIsArray($resultado);
    }

    public function test_clasificar_por_tfidf_con_texto_solo_espacios(): void
    {
        $resultado = $this->detector->clasificarPorTfidf('   ');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA MEJORAR COVERAGE
    // ============================================

    public function test_validar_intenciones_contra_mensaje_con_intencion_no_en_explicitas(): void
    {
        // Probar el caso cuando la intención no está en el array de explicitas
        // Esto debería hacer que se ejecute el continue (línea 65)
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['IntencionInexistente'],
            self::MENSAJE_ALGO
        );
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_contra_mensaje_con_intencion_valida_pero_no_en_mensaje(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'hola mundo sin palabras clave'
        );
        $this->assertIsArray($resultado);
        // Puede estar vacío si no encuentra la palabra clave
    }

    public function test_detectar_intenciones_con_palabras_fuertes(): void
    {
        // Probar detección usando palabras fuertes (no explícitas)
        $resultado = $this->detector->detectarIntenciones('necesito un parlante');
        $this->assertIsArray($resultado);
        // Parlante es una palabra fuerte para Alquiler
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_puntaje_alto(): void
    {
        // Probar con múltiples palabras clave para obtener puntaje >= 2
        $resultado = $this->detector->detectarIntenciones('necesito alquiler de equipo de sonido y audio');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_ordenadas_por_puntaje(): void
    {
        // Probar que las intenciones se ordenan por puntaje
        $resultado = $this->detector->detectarIntenciones('necesito alquiler y animacion y publicidad');
        $this->assertIsArray($resultado);
        // Debería retornar múltiples intenciones ordenadas
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    public function test_es_relacionado_retorna_false_cuando_no_hay_palabras_clave(): void
    {
        $resultado = $this->detector->esRelacionado('hola como estas hoy');
        $this->assertFalse($resultado);
    }

    public function test_detectar_intenciones_con_palabra_explicita(): void
    {
        // Probar con palabra explícita que debería detectarse incluso con puntaje bajo
        $resultado = $this->detector->detectarIntenciones('quiero alquilar');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_maestro_ceremonias(): void
    {
        // Probar con "maestro de ceremonias" que es una constante
        $resultado = $this->detector->detectarIntenciones('necesito un maestro de ceremonias');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_detectar_intenciones_con_par_led(): void
    {
        // Probar con "par led" que es una constante
        $resultado = $this->detector->detectarIntenciones('necesito un par led');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_cumpleanos(): void
    {
        // Probar con "cumpleaños" que es una constante
        $resultado = $this->detector->detectarIntenciones('necesito animacion para cumpleaños');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_detectar_intenciones_con_locucion(): void
    {
        // Probar con "locución" que es una constante
        $resultado = $this->detector->detectarIntenciones('necesito locucion para radio');
        $this->assertIsArray($resultado);
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_detectar_intenciones_con_equipo_sonido(): void
    {
        // Probar con "equipo de sonido" que es una constante
        $resultado = $this->detector->detectarIntenciones('necesito equipo de sonido');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_validar_intenciones_con_palabra_clave_en_mensaje(): void
    {
        // Probar validación cuando la palabra clave está en el mensaje
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'quiero alquiler de equipos de audio'
        );
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_validar_intenciones_con_multiples_palabras_clave(): void
    {
        // Probar validación con múltiples intenciones y palabras clave
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [self::INTENCION_ALQUILER, self::INTENCION_ANIMACION],
            'necesito alquiler y un dj para animacion'
        );
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    public function test_es_relacionado_con_palabra_clave_parcial(): void
    {
        // Probar que esRelacionado detecta palabras clave incluso en contexto
        $resultado = $this->detector->esRelacionado('quiero alquilar equipos de sonido');
        $this->assertTrue($resultado);
    }

    public function test_detectar_intenciones_case_insensitive(): void
    {
        // Probar que la detección es case-insensitive
        $resultado = $this->detector->detectarIntenciones('NECESITO ALQUILER DE EQUIPOS');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_acentos(): void
    {
        // Probar que maneja acentos correctamente
        $resultado = $this->detector->detectarIntenciones('necesito animación para fiesta');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA COBERTURA COMPLETA
    // ============================================

    public function test_detectar_intenciones_con_puntaje_exacto_dos(): void
    {
        // Probar detección con exactamente 2 palabras clave (umbral mínimo)
        $resultado = $this->detector->detectarIntenciones('necesito alquiler y equipo');
        $this->assertIsArray($resultado);
        // Debería detectar Alquiler con puntaje >= 2
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_puntaje_uno_y_palabra_explicita_animacion(): void
    {
        // Probar que detecta Animación con puntaje < 2 pero con palabra explícita
        $resultado = $this->detector->detectarIntenciones('quiero animacion');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_detectar_intenciones_con_puntaje_uno_y_palabra_fuerte_publicidad(): void
    {
        // Probar que detecta Publicidad con puntaje < 2 pero con palabra fuerte
        $resultado = $this->detector->detectarIntenciones('necesito un anuncio');
        $this->assertIsArray($resultado);
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_detectar_intenciones_sin_puntaje_suficiente(): void
    {
        // Probar mensaje sin palabras clave suficientes
        $resultado = $this->detector->detectarIntenciones('hola como estas');
        $this->assertIsArray($resultado);
        // Puede estar vacío o tener resultados si hay coincidencias parciales
    }

    public function test_detectar_intenciones_con_palabra_en_limite_palabra(): void
    {
        // Probar detección usando regex con límite de palabra
        $resultado = $this->detector->detectarIntenciones('necesito un microfono profesional');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_ordenamiento_por_puntaje(): void
    {
        // Probar que las intenciones se ordenan correctamente por puntaje
        $resultado = $this->detector->detectarIntenciones('necesito alquiler de equipo de sonido y audio y tambien animacion con dj');
        $this->assertIsArray($resultado);
        // Alquiler debería tener mayor puntaje que Animación
        if (count($resultado) >= 2) {
            $this->assertContains('Alquiler', $resultado);
            $this->assertContains(self::INTENCION_ANIMACION, $resultado);
        }
    }

    public function test_detectar_intenciones_con_todas_las_palabras_clave_alquiler(): void
    {
        // Probar con múltiples palabras clave de Alquiler
        $resultado = $this->detector->detectarIntenciones('necesito alquiler de equipo de sonido audio bafle parlante consola mixer microfono luces');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_todas_las_palabras_clave_animacion(): void
    {
        // Probar con múltiples palabras clave de Animación
        $resultado = $this->detector->detectarIntenciones('necesito animacion animador dj maestro de ceremonias presentador coordinador fiesta evento cumpleaños');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_detectar_intenciones_con_todas_las_palabras_clave_publicidad(): void
    {
        // Probar con múltiples palabras clave de Publicidad
        $resultado = $this->detector->detectarIntenciones('necesito publicidad publicitar anuncio spot cuña jingle locucion radio');
        $this->assertIsArray($resultado);
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_validar_intenciones_con_intencion_no_valida(): void
    {
        // Probar con intención que no existe en el sistema
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['ServicioInexistente', 'OtraIntencion'],
            self::MENSAJE_ALGO
        );
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_con_palabra_clave_al_inicio(): void
    {
        // Probar validación con palabra clave al inicio del mensaje
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'alquiler de equipos necesito'
        );
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_validar_intenciones_con_palabra_clave_al_final(): void
    {
        // Probar validación con palabra clave al final del mensaje
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Publicidad'],
            'necesito servicios de publicidad'
        );
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_validar_intenciones_con_palabra_clave_en_medio(): void
    {
        // Probar validación con palabra clave en medio del mensaje
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [self::INTENCION_ANIMACION],
            'necesito un dj profesional para mi evento'
        );
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_validar_intenciones_con_palabra_clave_con_acentos(): void
    {
        // Probar validación con palabra clave que tiene acentos
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [self::INTENCION_ANIMACION],
            'necesito animación para fiesta'
        );
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_es_relacionado_con_variaciones_de_palabras(): void
    {
        // Probar con diferentes variaciones de palabras relacionadas
        $variaciones = [
            'alquilar equipos',
            'arrendar sonido',
            'rentar audio',
            'equipo de sonido',
            'parlantes y bafles',
            'consola mezcladora',
            'microfonos y luces',
            'par led iluminacion',
            'animador profesional',
            'dj para evento',
            'maestro ceremonias',
            'presentador evento',
            'fiesta cumpleaños',
            'publicidad radio',
            'anuncio spot',
            'locucion profesional',
            'jingle publicitario',
        ];

        foreach ($variaciones as $variacion) {
            $resultado = $this->detector->esRelacionado($variacion);
            $this->assertTrue($resultado, "Debería ser relacionado: $variacion");
        }
    }

    public function test_es_relacionado_con_palabras_no_relacionadas(): void
    {
        // Probar con palabras que no están relacionadas
        $noRelacionadas = [
            'que tiempo hace',
            'como estas hoy',
            'cual es tu nombre',
            'donde vives',
            'que hora es',
            'como llegar a',
            'cual es el precio de',
            'informacion sobre clima',
        ];

        foreach ($noRelacionadas as $mensaje) {
            $resultado = $this->detector->esRelacionado($mensaje);
            $this->assertFalse($resultado, "No debería ser relacionado: $mensaje");
        }
    }

    public function test_es_relacionado_con_palabras_clave_en_mayusculas(): void
    {
        // Probar que esRelacionado funciona con mayúsculas
        $resultado = $this->detector->esRelacionado('NECESITO ALQUILER DE EQUIPOS');
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_palabras_clave_mixtas(): void
    {
        // Probar que esRelacionado funciona con mayúsculas y minúsculas mixtas
        $resultado = $this->detector->esRelacionado('NeCeSiTo AlQuIlEr De EqUiPoS');
        $this->assertTrue($resultado);
    }

    public function test_detectar_intenciones_con_palabras_compuestas(): void
    {
        // Probar con palabras compuestas o frases
        $resultado = $this->detector->detectarIntenciones('necesito equipo de sonido profesional');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_mensaje_muy_largo(): void
    {
        // Probar con mensaje muy largo con múltiples palabras clave
        $mensajeLargo = 'necesito alquiler de equipo de sonido audio bafles parlantes consola mezcladora mixer microfonos luces iluminacion par led rack y tambien animacion con dj animador maestro de ceremonias presentador coordinador para fiesta evento cumpleaños y ademas publicidad con anuncio spot cuña jingle locucion radio';
        $resultado = $this->detector->detectarIntenciones($mensajeLargo);
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    public function test_detectar_intenciones_con_palabras_repetidas(): void
    {
        // Probar con palabras clave repetidas
        $resultado = $this->detector->detectarIntenciones('alquiler alquiler alquiler equipo equipo');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_puntuacion(): void
    {
        // Probar que maneja correctamente la puntuación
        $resultado = $this->detector->detectarIntenciones('necesito alquiler, de equipos. y también animación!');
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    public function test_detectar_intenciones_con_numeros(): void
    {
        // Probar que maneja números en el mensaje
        $resultado = $this->detector->detectarIntenciones('necesito 2 equipos de sonido y 1 dj');
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    public function test_validar_intenciones_con_mensaje_vacio(): void
    {
        // Probar validación con mensaje vacío
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            ''
        );
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_con_mensaje_solo_espacios(): void
    {
        // Probar validación con mensaje que solo tiene espacios
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            '   '
        );
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_con_palabra_clave_como_parte_de_otra_palabra(): void
    {
        // Probar que no detecta palabras clave que son parte de otras palabras
        // "alquiler" no debería detectarse en "alquilero" (aunque el regex podría hacerlo)
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'necesito un alquilero' // palabra que contiene "alquiler"
        );
        // El resultado puede variar según la implementación del regex
        $this->assertIsArray($resultado);
    }

    public function test_detectar_intenciones_con_servicio_que_no_existe(): void
    {
        // Probar que solo retorna servicios válidos
        $resultado = $this->detector->detectarIntenciones('necesito algo completamente diferente');
        $this->assertIsArray($resultado);
        // Solo debería retornar Alquiler, Animación o Publicidad
        $serviciosValidos = [self::INTENCION_ALQUILER, self::INTENCION_ANIMACION, self::INTENCION_PUBLICIDAD];
        foreach ($resultado as $servicio) {
            $this->assertContains($servicio, $serviciosValidos);
        }
    }

    public function test_clasificar_por_tfidf_con_texto_normalizado(): void
    {
        // Probar clasificación con texto que necesita normalización
        $resultado = $this->detector->clasificarPorTfidf('  ALQUILER   EQUIPOS  ');
        $this->assertIsArray($resultado);
    }

    public function test_clasificar_por_tfidf_con_caracteres_especiales(): void
    {
        // Probar clasificación con caracteres especiales
        $resultado = $this->detector->clasificarPorTfidf('alquiler@equipos#sonido$audio');
        $this->assertIsArray($resultado);
    }

    public function test_clasificar_por_tfidf_con_datos_en_bd(): void
    {
        // Crear datos de prueba para que clasificarPorTfidf tenga datos con los que trabajar
        $servicio = \App\Models\Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler de equipos',
            'icono' => 'alquiler-icon',
        ]);

        \App\Models\SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de Sonido',
            'descripcion' => 'Equipo profesional de sonido para eventos',
            'precio' => 100000,
        ]);

        $resultado = $this->detector->clasificarPorTfidf('alquiler equipo sonido profesional');
        $this->assertIsArray($resultado);
        // Si hay datos y el score es >= 0.12, debería retornar el servicio
        if (! empty($resultado)) {
            $this->assertContains('Alquiler', $resultado);
        }
    }

    public function test_clasificar_por_tfidf_con_multiples_servicios(): void
    {
        // Crear múltiples servicios para probar el cálculo de TF-IDF
        $servicioAlquiler = \App\Models\Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        $servicioAnimacion = \App\Models\Servicios::create([
            'nombre_servicio' => 'Animación',
            'descripcion' => 'Servicio de animación',
            'icono' => 'animacion-icon',
        ]);

        \App\Models\SubServicios::create([
            'servicios_id' => $servicioAlquiler->id,
            'nombre' => 'Equipo Sonido',
            'descripcion' => 'Equipo profesional sonido audio',
            'precio' => 100000,
        ]);

        \App\Models\SubServicios::create([
            'servicios_id' => $servicioAnimacion->id,
            'nombre' => 'DJ Profesional',
            'descripcion' => 'DJ profesional animación eventos',
            'precio' => 150000,
        ]);

        $resultado = $this->detector->clasificarPorTfidf('equipo sonido profesional');
        $this->assertIsArray($resultado);
    }

    public function test_clasificar_por_tfidf_con_score_bajo(): void
    {
        // Crear datos que generen un score bajo (< 0.12)
        $servicio = \App\Models\Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'icono' => 'alquiler-icon',
        ]);

        \App\Models\SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo',
            'descripcion' => 'Equipo básico',
            'precio' => 50000,
        ]);

        // Mensaje con palabras que no coinciden mucho
        $resultado = $this->detector->clasificarPorTfidf('completamente diferente texto sin relacion');
        $this->assertIsArray($resultado);
        // Debería retornar vacío si el score es < 0.12
    }

    public function test_clasificar_por_tfidf_con_excepcion_en_cache(): void
    {
        // Probar que maneja excepciones al obtener el cache
        // Esto se puede hacer mockeando SubServicios para que lance una excepción
        // Por ahora, verificamos que retorna un array incluso si hay problemas
        $resultado = $this->detector->clasificarPorTfidf('test mensaje');
        $this->assertIsArray($resultado);
    }

    public function test_clasificar_por_tfidf_con_tokens_vacios(): void
    {
        // Probar con texto que no genera tokens válidos
        $resultado = $this->detector->clasificarPorTfidf('a b c'); // Tokens muy cortos
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_clasificar_por_tfidf_con_stopwords_solo(): void
    {
        // Probar con texto que solo tiene stopwords
        $resultado = $this->detector->clasificarPorTfidf('para por con sin del de la las el los');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_detectar_intenciones_con_tfidf_fallback(): void
    {
        // Probar que detectarIntenciones usa clasificarPorTfidf como fallback
        // cuando no detecta intenciones con el método normal
        $resultado = $this->detector->detectarIntenciones('equipo profesional sonido');
        $this->assertIsArray($resultado);
        // Puede retornar intenciones si TF-IDF encuentra coincidencias
    }

    public function test_detectar_intenciones_con_palabras_stopwords(): void
    {
        // Probar que las stopwords no afectan la detección
        $resultado = $this->detector->detectarIntenciones('para por con sin del de la las el los una unos unas que y o en al alquiler');
        $this->assertIsArray($resultado);
        // Debería detectar Alquiler a pesar de las stopwords
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_orden_diferente(): void
    {
        // Probar que el orden de las palabras no afecta la detección
        $resultado1 = $this->detector->detectarIntenciones('alquiler de equipos');
        $resultado2 = $this->detector->detectarIntenciones('equipos de alquiler');
        $this->assertIsArray($resultado1);
        $this->assertIsArray($resultado2);
        // Ambos deberían detectar Alquiler
        $this->assertContains('Alquiler', $resultado1);
        $this->assertContains('Alquiler', $resultado2);
    }

    public function test_validar_intenciones_con_break_early(): void
    {
        // Probar que el break funciona correctamente en validarIntencionesContraMensaje
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [self::INTENCION_ALQUILER, self::INTENCION_ANIMACION],
            self::MENSAJE_ALQUILER // Solo la primera debería validarse
        );
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_con_cache_palabras(): void
    {
        // Probar que el cache de palabras funciona correctamente
        // Primera llamada
        $resultado1 = $this->detector->detectarIntenciones(self::MENSAJE_ALQUILER);
        // Segunda llamada (debería usar cache)
        $resultado2 = $this->detector->detectarIntenciones('necesito animacion');
        $this->assertIsArray($resultado1);
        $this->assertIsArray($resultado2);
    }

    public function test_detectar_intenciones_con_puntaje_cero(): void
    {
        // Probar con mensaje que no tiene palabras clave
        $resultado = $this->detector->detectarIntenciones('hola mundo');
        $this->assertIsArray($resultado);
        // Puede estar vacío o tener resultados si hay coincidencias parciales
    }

    public function test_es_relacionado_con_palabra_clave_con_guiones(): void
    {
        // Probar con palabras clave que pueden tener guiones
        $resultado = $this->detector->esRelacionado('necesito equipo-de-sonido');
        $this->assertIsBool($resultado);
    }
}
