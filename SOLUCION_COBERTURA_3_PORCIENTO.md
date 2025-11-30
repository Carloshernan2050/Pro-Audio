# Solución: Cobertura baja en SonarQube (3.2%)

## Problema Identificado

**Métrica real en coverage.xml**: ~90.6% de cobertura de statements
**Métrica mostrada en SonarQube**: 3.2%

### Causa Raíz

SonarQube está calculando la cobertura sobre un conjunto de archivos **mucho más grande** que los 44 archivos que están en `coverage.xml`. Los archivos que no tienen datos de cobertura (porque están excluidos de PHPUnit) cuentan como 0% cubiertos, reduciendo el porcentaje total.

## Solución Aplicada

### 1. Sincronización de Exclusiones

Se actualizó `sonar-project.properties` para que las exclusiones coincidan exactamente con `phpunit.xml`:

```properties
# Exclusiones que coinciden con phpunit.xml
sonar.exclusions=...,**/app/Console/**,**/app/Providers/AppServiceProvider.php

# Exclusiones adicionales solo para cobertura
sonar.coverage.exclusions=...,**/app/Exceptions/**
```

### 2. Configuración de Cobertura Específica

Se agregó `sonar.coverage.exclusions` para excluir archivos adicionales solo del cálculo de cobertura, no del análisis de código.

## Pasos para Resolver

### Paso 1: Regenerar Reportes

```powershell
.\generate-sonar-reports.ps1
```

Esto generará un nuevo `coverage.xml` con rutas actualizadas y métricas correctas.

### Paso 2: Ejecutar SonarQube Scanner

```powershell
.\run-sonar-scanner.ps1
```

### Paso 3: Verificar en SonarQube

Después de ejecutar el scanner, verificar:

1. **Dashboard → Metrics → Code Coverage**
   - Debe mostrar un porcentaje cercano al 90.6%

2. **Coverage → Files**
   - Debe mostrar aproximadamente 44 archivos con cobertura

3. **Si aún muestra 3.2%**:
   - Verificar que el `coverage.xml` esté siendo leído correctamente
   - Verificar que las exclusiones estén aplicadas
   - Revisar los logs de SonarQube para errores

## Verificación de Métricas

### En coverage.xml

```powershell
# Ver métricas totales
Select-String -Path "tests\_coverage\coverage.xml" -Pattern '<metrics files=' | Select-Object -Last 1
```

**Resultado esperado**:
- files="44"
- statements="2273" coveredstatements="2060" (~90.6%)
- methods="223" coveredmethods="168" (~75.3%)

### En SonarQube

1. Ir a: `https://sonarqube.dataguaviare.com.co/dashboard?id=pro-audio`
2. Ver métrica: **Code Coverage**
3. Debe ser cercano al 90.6%

## Notas Importantes

1. **Las exclusiones deben coincidir** entre `phpunit.xml` y `sonar-project.properties`
2. **SonarQube puede cachear** datos anteriores - puede ser necesario ejecutar el análisis múltiples veces
3. **Verificar rutas**: El `coverage.xml` usa rutas absolutas que SonarQube debe mapear correctamente

## Si el Problema Persiste

1. **Limpiar cache de SonarQube**:
   - En SonarQube: Project Settings → Analysis Scope → Clear Cache

2. **Verificar formato del coverage.xml**:
   - Asegurarse de que es formato Clover válido
   - Verificar que las rutas sean correctas

3. **Ver logs de SonarQube**:
   - Buscar errores relacionados con `coverage.xml`
   - Verificar que el archivo se esté procesando

## Métricas Esperadas Después de la Solución

- **Cobertura de líneas**: ~90-91%
- **Cobertura de métodos**: ~75-76%
- **Archivos analizados**: 44
- **Statements cubiertos**: ~2,060 de 2,273

