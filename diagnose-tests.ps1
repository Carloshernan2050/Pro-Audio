# Script de diagnóstico para verificar por qué los tests Unit no se ejecutan

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Diagnóstico de Tests" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar estructura de tests
Write-Host "1. Verificando estructura de tests..." -ForegroundColor Yellow
$unitTests = Get-ChildItem -Path "tests\Unit\*.php" -ErrorAction SilentlyContinue
$featureTests = Get-ChildItem -Path "tests\Feature\*.php" -ErrorAction SilentlyContinue

Write-Host "   Tests Unit encontrados: $($unitTests.Count)" -ForegroundColor Cyan
Write-Host "   Tests Feature encontrados: $($featureTests.Count)" -ForegroundColor Cyan
Write-Host ""

# 2. Verificar phpunit.xml
Write-Host "2. Verificando configuración de phpunit.xml..." -ForegroundColor Yellow
if (Test-Path "phpunit.xml") {
    $phpunitContent = Get-Content "phpunit.xml" -Raw
    if ($phpunitContent -match '<testsuite name="Unit"') {
        Write-Host "   [OK] Testsuite Unit configurado" -ForegroundColor Green
    } else {
        Write-Host "   [ERROR] Testsuite Unit NO encontrado en phpunit.xml" -ForegroundColor Red
    }
    if ($phpunitContent -match '<testsuite name="Feature"') {
        Write-Host "   [OK] Testsuite Feature configurado" -ForegroundColor Green
    } else {
        Write-Host "   [ERROR] Testsuite Feature NO encontrado en phpunit.xml" -ForegroundColor Red
    }
} else {
    Write-Host "   [ERROR] phpunit.xml no encontrado" -ForegroundColor Red
}
Write-Host ""

# 3. Verificar reporte actual
Write-Host "3. Analizando reporte de tests actual..." -ForegroundColor Yellow
$testReportFile = "tests\_coverage\test-reporter.xml"
if (Test-Path $testReportFile) {
    $reportContent = Get-Content $testReportFile -Raw
    if ($reportContent -match 'tests="(\d+)"') {
        $totalTests = $matches[1]
        Write-Host "   Total de tests ejecutados según reporte: $totalTests" -ForegroundColor Cyan
    }
    
    if ($reportContent -match '<testsuite name="Unit"') {
        Write-Host "   [OK] Tests Unit encontrados en el reporte" -ForegroundColor Green
    } else {
        Write-Host "   [ADVERTENCIA] Tests Unit NO encontrados en el reporte" -ForegroundColor Yellow
        Write-Host "   Esto significa que los tests Unit no se ejecutaron" -ForegroundColor Yellow
    }
    
    if ($reportContent -match '<testsuite name="Feature"') {
        Write-Host "   [OK] Tests Feature encontrados en el reporte" -ForegroundColor Green
    } else {
        Write-Host "   [ADVERTENCIA] Tests Feature NO encontrados en el reporte" -ForegroundColor Yellow
    }
} else {
    Write-Host "   [INFO] No hay reporte de tests generado aún" -ForegroundColor Gray
}
Write-Host ""

# 4. Verificar si hay errores en tests Unit
Write-Host "4. Verificando sintaxis de algunos tests Unit..." -ForegroundColor Yellow
$sampleUnitTests = $unitTests | Select-Object -First 3
foreach ($testFile in $sampleUnitTests) {
    $content = Get-Content $testFile.FullName -Raw -ErrorAction SilentlyContinue
    if ($content) {
        if ($content -match 'class\s+\w+\s+extends') {
            Write-Host "   [OK] $($testFile.Name) - Sintaxis válida" -ForegroundColor Green
        } else {
            Write-Host "   [ADVERTENCIA] $($testFile.Name) - No se encontró clase que extienda TestCase" -ForegroundColor Yellow
        }
    }
}
Write-Host ""

# 5. Verificar PHPUnit
Write-Host "5. Verificando PHPUnit..." -ForegroundColor Yellow
if (Test-Path "vendor\bin\phpunit.bat") {
    Write-Host "   [OK] PHPUnit encontrado" -ForegroundColor Green
} else {
    Write-Host "   [ERROR] PHPUnit no encontrado" -ForegroundColor Red
    Write-Host "   Ejecuta: composer install" -ForegroundColor Yellow
}
Write-Host ""

# 6. Recomendaciones
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Recomendaciones" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Si los tests Unit no se ejecutan:" -ForegroundColor Yellow
Write-Host "  1. Verifica que no haya errores de sintaxis en los tests Unit" -ForegroundColor Gray
Write-Host "  2. Ejecuta manualmente: vendor\bin\phpunit.bat --testsuite=Unit" -ForegroundColor Cyan
Write-Host "  3. Ejecuta manualmente: vendor\bin\phpunit.bat --testsuite=Feature" -ForegroundColor Cyan
Write-Host "  4. Ejecuta todos: vendor\bin\phpunit.bat" -ForegroundColor Cyan
Write-Host "  5. Regenera reportes: .\generate-sonar-reports.ps1" -ForegroundColor Cyan
Write-Host ""

