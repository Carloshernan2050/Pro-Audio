# 11.6 Caso de Prueba: Consultar Historial de Reservas con Datos Válidos

**ID:** CP-014

**Nombre:** Consultar Historial de Reservas con Datos Válidos

**Tipo:** Prueba Unitaria

**Componente:** AjustesController / getReservas()

**Prioridad:** Alta

**Responsable:** Desarrollador Backend

## Objetivo:

Verificar que el sistema consulta y retorna el historial de reservas cuando existen reservas confirmadas guardadas en la base de datos con relaciones válidas.

## Precondiciones:

- Base de datos con reservas confirmadas existentes en la tabla `historial`.
- Registros de historial con `confirmado_en` no nulo (solo reservas confirmadas).
- Reservas asociadas a personas / usuarios válidos.
- Relación entre `historial` y `reservas` establecida correctamente.
- Personas con información completa (primer_nombre, primer_apellido, correo).

## Datos de Entrada:

- No requiere parámetros adicionales para la consulta general.
- El método `getReservas()` es privado y se llama internamente desde `AjustesController::index()`.
- Parámetro opcional `historial_type` con valor `'reservas'` en la query string para activar la visualización de reservas.

## Pasos de Ejecución:

1. Inicializar `AjustesController`.
2. Preparar datos de prueba:
   - Crear usuario (persona) válido en la tabla `usuarios`.
   - Crear reserva asociada a la persona.
   - Crear registro de historial con:
     - `reserva_id` apuntando a la reserva creada.
     - `accion` = 'confirmada'.
     - `confirmado_en` con fecha/hora válida (no nulo).
3. Ejecutar el método `getReservas()` del controlador (a través de `index()` con parámetro `historial_type=reservas`).
4. Verificar los datos retornados por el método.

## Resultados Esperados:

1. El método debe ejecutarse sin errores.
2. Debe retornar una colección de instancias de `Historial`.
3. La cantidad de registros corresponde a las reservas confirmadas existentes en la base de datos (solo con `confirmado_en` no nulo).
4. Cada registro debe incluir:
   - ID del historial (`id`).
   - `reserva_id` correspondiente a un ID válido de reserva.
   - `accion` = 'confirmada'.
   - `confirmado_en` con fecha/hora válida (no nulo).
   - Relación `reserva` cargada con datos válidos:
     - `id` de la reserva.
     - `personas_id` correspondiente a un ID válido.
     - `fecha_inicio` correcta.
     - `fecha_fin` correcta.
     - `descripcion_evento` (si existe).
     - `estado` de la reserva.
     - `cantidad_total` (si existe).
   - Relación `reserva.persona` cargada con datos válidos:
     - `id` de la persona.
     - `primer_nombre`.
     - `primer_apellido`.
     - `correo`.
     - Información adicional si existe (teléfono, etc.).

## Ordenamiento:

- Las reservas deben estar ordenadas por `confirmado_en` en orden descendente (la más reciente primero).
- Como ordenamiento secundario, por `id` en orden descendente.

## Filtrado:

- Solo se retornan registros de historial que tienen `confirmado_en` no nulo (reservas confirmadas).
- Se excluyen automáticamente las reservas pendientes o canceladas del historial.

## Resultados Obtenidos:

**PASS**

## Estado:

**Aprobado**

## Notas Adicionales:

- El método utiliza eager loading (`with(['reserva.persona'])`) para cargar las relaciones de forma eficiente.
- Solo se muestran reservas que han sido confirmadas (tienen fecha de confirmación).
- El método se accede a través de `AjustesController::index()` con el parámetro `historial_type=reservas`.
- La consulta utiliza `whereNotNull('confirmado_en')` para filtrar solo reservas confirmadas.


