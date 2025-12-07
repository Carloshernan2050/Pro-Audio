# CASO DE USO EXTENDIDO – UC-HIS-001: Consultar Historial de Cotizaciones y Reservas

**ID:** UC-HIS-001  
**Nombre:** Consultar Historial de Cotizaciones y Reservas  
**Autor:** Carlos Hernan Molina Arenas, Lisseth Katerine Rivas Bedoya, Danna Katherin Vargas Ruiz  
**Actor Principal:** Administrador  
**Versión:** 1.0  
**Fecha:** 03/08/2025

---

## Control de Versiones

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| 1.0 | 03/08/2025 | Creación inicial del documento de Caso de uso extendido "Consultar Historial de Cotizaciones y Reservas" |

---

## Descripción

Permite al Administrador consultar y visualizar el historial completo de cotizaciones y reservas del sistema. El historial puede mostrarse de forma general o agrupado por día o por consulta (cotizaciones), y permite alternar entre la visualización de cotizaciones y reservas.

---

## Precondiciones

1. El usuario debe tener rol de Administrador
2. El usuario debe estar autenticado en el sistema
3. El sistema debe tener acceso a la base de datos
4. Deben existir cotizaciones o reservas en la base de datos (opcional, puede mostrar lista vacía)

---

## Postcondiciones

1. Se muestra la lista completa de cotizaciones o reservas según el tipo seleccionado
2. Se muestran todas las relaciones cargadas (persona, subservicio, servicio, reserva)
3. Los datos están ordenados correctamente (más recientes primero)
4. Si se selecciona agrupación, se muestran los datos agrupados según el criterio

---

## Flujo Principal

1. El administrador accede al módulo de Ajustes
2. El administrador hace clic en la pestaña "Historial"
3. El sistema muestra los botones para seleccionar el tipo de historial:
   - Cotizaciones
   - Reservas
4. El administrador selecciona el tipo de historial deseado (por defecto: Cotizaciones)
5. Si selecciona "Cotizaciones":
   - El sistema consulta todas las cotizaciones con relaciones cargadas:
     - Relación `persona` (primer_nombre, primer_apellido, correo)
     - Relación `subServicio` (nombre)
     - Relación `subServicio.servicio` (nombre_servicio)
   - El sistema ordena las cotizaciones por `fecha_cotizacion` en orden descendente
   - El administrador puede seleccionar un tipo de agrupación (opcional):
     - Sin agrupación: muestra todas las cotizaciones en lista
     - Por día: agrupa las cotizaciones por fecha
     - Por consulta: agrupa las cotizaciones por persona y fecha/hora
6. Si selecciona "Reservas":
   - El sistema consulta todas las reservas confirmadas del historial:
     - Relación `reserva` con `persona` (primer_nombre, primer_apellido, correo)
     - Filtra solo reservas con `confirmado_en` no nulo
   - El sistema ordena las reservas por `confirmado_en` y `id` en orden descendente
7. El sistema muestra la información en una tabla con las columnas correspondientes:
   - Para Cotizaciones: ID, Fecha, Cliente, Subservicio, Servicio, Monto
   - Para Reservas: ID, Fecha Confirmación, Cliente, Descripción, Estado
8. El administrador puede ver los detalles de cada registro
9. El administrador puede exportar el historial a PDF (solo para cotizaciones)

---

## Flujos Alternos

### FA-01 – Agrupación por Día (Cotizaciones)

**Condición de Activación:** El administrador selecciona agrupación "Por día" para cotizaciones

1. El administrador selecciona "Por día" en el selector de agrupación
2. El sistema agrupa las cotizaciones por fecha (formato Y-m-d)
3. Para cada grupo, el sistema calcula:
   - Total de monto de todas las cotizaciones del día
   - Cantidad de cotizaciones del día
4. Se muestra una tabla con:
   - Día (fecha)
   - Lista de cotizaciones del día
   - Total del día
   - Cantidad de cotizaciones

---

### FA-02 – Agrupación por Consulta (Cotizaciones)

**Condición de Activación:** El administrador selecciona agrupación "Por consulta" para cotizaciones

1. El administrador selecciona "Por consulta" en el selector de agrupación
2. El sistema agrupa las cotizaciones por persona y fecha/hora (personas_id + fecha_cotizacion)
3. Para cada grupo, el sistema calcula:
   - Total de monto de todas las cotizaciones de la consulta
   - Cantidad de cotizaciones de la consulta
   - Información de la persona
   - Timestamp de la consulta
4. Los grupos se ordenan por timestamp descendente
5. Se muestra una tabla con:
   - Cliente (persona)
   - Fecha y hora de la consulta
   - Lista de cotizaciones de la consulta
   - Total de la consulta
   - Cantidad de cotizaciones

---

### FA-03 – Exportar Historial a PDF

**Condición de Activación:** El administrador hace clic en "Exportar PDF"

1. El administrador hace clic en el botón "Exportar PDF"
2. El sistema consulta todas las cotizaciones con relaciones cargadas
3. El sistema aplica la agrupación seleccionada (si existe)
4. El sistema genera un documento PDF con la información
5. El sistema descarga el archivo PDF con nombre "historial_cotizaciones.pdf"
6. El formato del PDF depende de la agrupación:
   - Sin agrupación: formato landscape (horizontal)
   - Con agrupación: formato portrait (vertical)

---

### FA-04 – Historial Vacío

**Condición de Activación:** No hay cotizaciones o reservas en el sistema

1. El sistema consulta la base de datos y no encuentra registros
2. Se muestra una tabla vacía con el mensaje: "No hay cotizaciones en el historial." o "No hay reservas en el historial."
3. El administrador puede continuar usando otras funcionalidades del sistema

---

## Excepciones

### EX-01 – Error al Cargar Cotizaciones

**Condición de Activación:** Ocurre un error al consultar las cotizaciones de la base de datos

**Mensaje:** "Error al cargar el historial de cotizaciones: [mensaje de error]"

1. El sistema detecta un error durante la consulta
2. Se muestra un mensaje de error
3. Se muestra la vista con una lista vacía para evitar errores de visualización
4. El error se registra en el log del sistema

---

### EX-02 – Error al Cargar Reservas

**Condición de Activación:** Ocurre un error al consultar las reservas del historial

**Mensaje:** "Error al cargar el historial de reservas: [mensaje de error]"

1. El sistema detecta un error durante la consulta
2. Se muestra un mensaje de error
3. Se muestra la vista con una lista vacía
4. El error se registra en el log del sistema

---

### EX-03 – Relaciones Faltantes

**Condición de Activación:** Una cotización o reserva tiene relaciones faltantes (persona eliminada, subservicio eliminado)

**Mensaje:** Se muestra "N/A" en los campos correspondientes

1. El sistema detecta que una relación no existe
2. Se muestra "N/A" en lugar del dato faltante
3. El registro se muestra igualmente en la lista
4. No se interrumpe la visualización del resto de registros

---

### EX-04 – Error al Generar PDF

**Condición de Activación:** Ocurre un error al intentar generar el PDF

**Mensaje:** "Error al generar el PDF: [mensaje de error]"

1. El sistema detecta un error durante la generación del PDF
2. Se muestra un mensaje de error
3. El PDF no se genera
4. El administrador puede intentar nuevamente

---

## Reglas de Negocio

**RN-01:** Las cotizaciones se ordenan por `fecha_cotizacion` en orden descendente (más recientes primero)

**RN-02:** Las reservas se ordenan por `confirmado_en` y `id` en orden descendente (más recientes primero)

**RN-03:** Solo se muestran reservas que tienen `confirmado_en` no nulo (reservas confirmadas)

**RN-04:** Las cotizaciones siempre se cargan con las relaciones: `persona`, `subServicio`, `subServicio.servicio`

**RN-05:** Las reservas siempre se cargan con las relaciones: `reserva`, `reserva.persona`

**RN-06:** La agrupación por día agrupa por fecha (sin hora)

**RN-07:** La agrupación por consulta agrupa por persona y fecha/hora completa

**RN-08:** El formato del PDF es landscape si no hay agrupación, portrait si hay agrupación

**RN-09:** Si una relación no existe, se muestra "N/A" en lugar de generar error

---

## Criterios de Aceptación

**CA-01:** El administrador puede consultar el historial de cotizaciones con datos válidos

**CA-02:** El administrador puede consultar el historial de reservas confirmadas

**CA-03:** Se retorna una colección de cotizaciones o reservas correctamente

**CA-04:** La cantidad de registros corresponde a las cotizaciones/reservas existentes en la base de datos

**CA-05:** Cada registro de cotización contiene:
   - ID de la cotización
   - `personas_id` correspondiente a un ID válido
   - `fecha_cotizacion` correcta
   - `monto` calculado correctamente
   - Relación `persona` cargada con datos válidos (primer_nombre, primer_apellido, correo)
   - Relación `subServicio` cargada con nombre válido
   - Relación `subServicio.servicio` cargada con nombre_servicio válido

**CA-06:** Cada registro de reserva contiene:
   - ID del historial
   - Fecha de confirmación (`confirmado_en`)
   - Información del cliente (persona)
   - Descripción del evento
   - Estado de la reserva

**CA-07:** Las cotizaciones están ordenadas por `fecha_cotizacion` en orden descendente

**CA-08:** Las reservas están ordenadas por `confirmado_en` y `id` en orden descendente

**CA-09:** El administrador puede agrupar cotizaciones por día o por consulta

**CA-10:** El administrador puede exportar el historial de cotizaciones a PDF

**CA-11:** El sistema maneja correctamente el caso de historial vacío

**CA-12:** Los errores se manejan adecuadamente sin romper la interfaz

---

## Notas Adicionales

- El sistema utiliza Eloquent ORM con eager loading para cargar las relaciones eficientemente
- Las cotizaciones se pueden agrupar para facilitar el análisis de datos
- El PDF se genera usando la librería DomPDF de Laravel
- El sistema muestra "N/A" para relaciones faltantes en lugar de generar errores
- Solo se muestran reservas confirmadas (con fecha de confirmación)
- El historial de cotizaciones y reservas se muestra en la misma interfaz con pestañas para alternar

---

## Autores del Proyecto

- Carlos Hernan Molina Arenas
- Lisseth Katerine Rivas Bedoya
- Danna Katherin Vargas Ruiz


