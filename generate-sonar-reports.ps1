# Script para generar reportes de cobertura para SonarQube
# Este script debe ejecutarse ANTES de ejecutar SonarQube

# Configurar codificación UTF-8 para evitar problemas con caracteres especiales
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# Verificar y configurar PHP en el PATH si no está disponible
Write-Host "Verificando PHP..." -ForegroundColor Yellow
try {
    $null = & php --version 2>&1
    Write-Host "[OK] PHP encontrado en el PATH" -ForegroundColor Green
} catch {
    # Buscar PHP en ubicaciones comunes
    $phpPaths = @(
        "C:\xampp\php",
        "C:\wamp64\bin\php",
        "C:\laragon\bin\php",
        "C:\php",
        "$env:ProgramFiles\PHP",
        "$env:ProgramFiles(x86)\PHP"
    )
    
    $phpFound = $false
    foreach ($phpPath in $phpPaths) {
        if (Test-Path "$phpPath\php.exe") {
            $env:Path += ";$phpPath"
            Write-Host "[OK] PHP encontrado en: $phpPath" -ForegroundColor Green
            Write-Host "     Agregado al PATH para esta sesion." -ForegroundColor Gray
            $phpFound = $true
            break
        }
    }
    
    if (-not $phpFound) {
        Write-Host "ERROR: PHP no encontrado. Por favor:" -ForegroundColor Red
        Write-Host "  1. Instala PHP o XAMPP/WAMP/Laragon" -ForegroundColor Yellow
        Write-Host "  2. Agrega PHP al PATH del sistema" -ForegroundColor Yellow
        Write-Host "  3. O ejecuta este script desde una terminal con PHP en el PATH" -ForegroundColor Yellow
        exit 1
    }
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Generando reportes para SonarQube..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Crear directorio de coverage si no existe
$coverageDir = "tests\_coverage"
if (-not (Test-Path $coverageDir)) {
    Write-Host "Creando directorio: $coverageDir" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $coverageDir -Force | Out-Null
}

Write-Host "Ejecutando tests con cobertura..." -ForegroundColor Green
Write-Host ""

# Ejecutar tests con cobertura usando PHPUnit directamente
# Esto generará los reportes configurados en phpunit.xml
try {
    # Verificar que PHPUnit esté disponible
    if (-not (Test-Path "vendor\bin\phpunit.bat")) {
        Write-Host "ERROR: PHPUnit no encontrado. Ejecuta 'composer install' primero." -ForegroundColor Red
        exit 1
    }

    # Ejecutar PHPUnit con cobertura
    # Usamos --coverage-clover y --log-junit para generar los reportes explícitamente
    # PHPUnit deberia generar reportes incluso si algunos tests fallan
    # NOTA: --coverage-html es opcional (solo para visualización local, no necesario para SonarQube)
    $phpunitArgs = @(
        "--coverage-clover",
        "tests\_coverage\coverage.xml",
        "--log-junit",
        "tests\_coverage\test-reporter.xml"
        # "--coverage-html", "tests\_coverage\html"  # Descomenta si quieres reporte HTML local
    )
    
    Write-Host "NOTA: Ejecutando todos los tests para generar reportes." -ForegroundColor Yellow
    Write-Host "  PHPUnit generara reportes incluso si algunos tests tienen errores." -ForegroundColor Yellow
    Write-Host ""

    Write-Host "Comando: vendor\bin\phpunit.bat $($phpunitArgs -join ' ')" -ForegroundColor Gray
    Write-Host ""

    # Verificar si existe extensión de cobertura (Xdebug o PCOV)
    Write-Host "Verificando extension de cobertura..." -ForegroundColor Yellow
    $phpInfo = & php -m 2>&1 | Out-String
    
    if ($phpInfo -notmatch "xdebug" -and $phpInfo -notmatch "pcov") {
        Write-Host ""
        Write-Host "ADVERTENCIA: No se detecto ninguna extension de cobertura (Xdebug o PCOV)." -ForegroundColor Yellow
        Write-Host "Los reportes de cobertura pueden no generarse correctamente." -ForegroundColor Yellow
        Write-Host "Los reportes de tests (JUnit XML) se generaran normalmente." -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Para instalar extension de cobertura:" -ForegroundColor Cyan
        Write-Host "  - Xdebug: https://xdebug.org/docs/install" -ForegroundColor Gray
        Write-Host "  - PCOV: pecl install pcov (mas rapido, recomendado)" -ForegroundColor Gray
        Write-Host ""
        Write-Host "Continuando sin extension de cobertura..." -ForegroundColor Yellow
    } else {
        if ($phpInfo -match "xdebug") {
            Write-Host "[OK] Xdebug detectado" -ForegroundColor Green
        }
        if ($phpInfo -match "pcov") {
            Write-Host "[OK] PCOV detectado" -ForegroundColor Green
        }
    }
    Write-Host ""

    & vendor\bin\phpunit.bat @phpunitArgs
    
    # PHPUnit puede generar reportes incluso si hay errores en algunos tests
    # Verificamos si los reportes se generaron independientemente del código de salida
    Write-Host ""
    Write-Host "Verificando reportes generados..." -ForegroundColor Yellow
    Write-Host ""
    
    # Verificar que los archivos se generaron
    $coverageFile = "tests\_coverage\coverage.xml"
    $testReportFile = "tests\_coverage\test-reporter.xml"
    $reportsGenerated = $false
    $hasErrors = $LASTEXITCODE -ne 0
    
    if (Test-Path $testReportFile) {
        $size = (Get-Item $testReportFile).Length
        Write-Host "[OK] test-reporter.xml generado - $size bytes" -ForegroundColor Green
        $reportsGenerated = $true
    } else {
        Write-Host "[ERROR] test-reporter.xml NO encontrado" -ForegroundColor Red
        Write-Host "  Este archivo es requerido por SonarQube para detectar los tests." -ForegroundColor Red
    }
    
    if (Test-Path $coverageFile) {
        $size = (Get-Item $coverageFile).Length
        Write-Host "[OK] coverage.xml generado - $size bytes" -ForegroundColor Green
        $reportsGenerated = $true
    } else {
        Write-Host "[ADVERTENCIA] coverage.xml NO encontrado" -ForegroundColor Yellow
        Write-Host "  Esto es normal si no tienes extension de cobertura instalada." -ForegroundColor Gray
        Write-Host "  SonarQube puede funcionar sin coverage, pero lo necesita para mostrar cobertura." -ForegroundColor Gray
    }
    
    Write-Host ""
    
    if ($hasErrors) {
        Write-Host "ADVERTENCIA: Algunos tests fallaron durante la ejecucion." -ForegroundColor Yellow
        Write-Host "  Revisa los errores arriba para mas detalles." -ForegroundColor Yellow
        Write-Host ""
    }
    
    if ($reportsGenerated -and (Test-Path $testReportFile)) {
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "Reportes generados para SonarQube!" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "El archivo test-reporter.xml fue generado correctamente." -ForegroundColor Cyan
        Write-Host "SonarQube podra detectar tus tests con este archivo." -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Ahora puedes ejecutar SonarQube Scanner:" -ForegroundColor Cyan
        Write-Host "  sonar-scanner -Dsonar.login=TU_TOKEN" -ForegroundColor Yellow
        Write-Host ""
        exit 0
    } else {
        Write-Host "========================================" -ForegroundColor Red
        Write-Host "ERROR: No se pudieron generar los reportes." -ForegroundColor Red
        Write-Host "========================================" -ForegroundColor Red
        Write-Host ""
        Write-Host "El archivo test-reporter.xml es requerido para SonarQube." -ForegroundColor Red
        Write-Host "Revisa los errores arriba y corrige los problemas en los tests." -ForegroundColor Red
        Write-Host ""
        exit 1
    }
} catch {
    Write-Host ""
    Write-Host "ERROR al ejecutar los tests: $_" -ForegroundColor Red
    exit 1
}

