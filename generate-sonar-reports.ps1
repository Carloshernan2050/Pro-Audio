# Script para generar reportes de cobertura para SonarQube
# Este script debe ejecutarse ANTES de ejecutar SonarQube

# Configurar codificación UTF-8 para evitar problemas con caracteres especiales
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# Ruta de extensiones de PHP para coverage
$phpExtPath = "C:\xampp\php\ext"

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
            # Actualizar ruta de extensiones si es XAMPP
            if ($phpPath -eq "C:\xampp\php") {
                $phpExtPath = "$phpPath\ext"
            }
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

# Verificar ruta de extensiones
Write-Host "Ruta de extensiones PHP: $phpExtPath" -ForegroundColor Gray
if (Test-Path $phpExtPath) {
    Write-Host "[OK] Directorio de extensiones encontrado" -ForegroundColor Green
} else {
    Write-Host "[ADVERTENCIA] Directorio de extensiones no encontrado en: $phpExtPath" -ForegroundColor Yellow
    Write-Host "  Verificando extensiones en ubicacion por defecto..." -ForegroundColor Gray
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
    
    Write-Host "NOTA: Ejecutando todos los tests (Unit + Feature) para generar reportes." -ForegroundColor Yellow
    Write-Host "  PHPUnit generara reportes incluso si algunos tests tienen errores." -ForegroundColor Yellow
    Write-Host ""

    # Verificar que ambos testsuites estén configurados
    Write-Host "Verificando testsuites configurados..." -ForegroundColor Yellow
    $unitTests = 0
    $featureTests = 0
    
    if (Test-Path "tests\Unit") {
        $unitTests = (Get-ChildItem -Path "tests\Unit\*.php" -ErrorAction SilentlyContinue | Measure-Object).Count
    }
    if (Test-Path "tests\Feature") {
        $featureTests = (Get-ChildItem -Path "tests\Feature\*.php" -ErrorAction SilentlyContinue | Measure-Object).Count
    }
    
    Write-Host "  Tests Unit encontrados: $unitTests" -ForegroundColor Cyan
    Write-Host "  Tests Feature encontrados: $featureTests" -ForegroundColor Cyan
    Write-Host ""

    Write-Host "Comando: vendor\bin\phpunit.bat $($phpunitArgs -join ' ')" -ForegroundColor Gray
    Write-Host "  (Esto ejecutará TODOS los testsuites: Unit y Feature)" -ForegroundColor Gray
    Write-Host ""

    # Verificar si existe extensión de cobertura (Xdebug o PCOV)
    Write-Host "Verificando extension de cobertura..." -ForegroundColor Yellow
    
    # Verificar archivos DLL en la ruta de extensiones
    if (Test-Path $phpExtPath) {
        $xdebugDll = Get-ChildItem -Path $phpExtPath -Filter "php_xdebug.dll" -ErrorAction SilentlyContinue
        $pcovDll = Get-ChildItem -Path $phpExtPath -Filter "php_pcov.dll" -ErrorAction SilentlyContinue
        
        if ($xdebugDll) {
            Write-Host "[INFO] php_xdebug.dll encontrado en: $phpExtPath" -ForegroundColor Cyan
        }
        if ($pcovDll) {
            Write-Host "[INFO] php_pcov.dll encontrado en: $phpExtPath" -ForegroundColor Cyan
        }
        if (-not $xdebugDll -and -not $pcovDll) {
            Write-Host "[ADVERTENCIA] No se encontraron DLLs de cobertura en: $phpExtPath" -ForegroundColor Yellow
            Write-Host "  Para generar coverage.xml, necesitas instalar Xdebug o PCOV en esta ruta." -ForegroundColor Yellow
        }
    }
    
    $phpInfo = & php -m 2>&1 | Out-String
    
    if ($phpInfo -notmatch "xdebug" -and $phpInfo -notmatch "pcov") {
        Write-Host ""
        Write-Host "ADVERTENCIA: No se detecto ninguna extension de cobertura (Xdebug o PCOV) cargada." -ForegroundColor Yellow
        Write-Host "Los reportes de cobertura pueden no generarse correctamente." -ForegroundColor Yellow
        Write-Host "Los reportes de tests (JUnit XML) se generaran normalmente." -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Para instalar extension de cobertura:" -ForegroundColor Cyan
        Write-Host "  1. Descarga Xdebug desde: https://xdebug.org/download" -ForegroundColor Gray
        Write-Host "  2. Copia el DLL a: $phpExtPath" -ForegroundColor Gray
        Write-Host "  3. Configura php.ini con:" -ForegroundColor Gray
        Write-Host "     [Xdebug]" -ForegroundColor Gray
        Write-Host "     zend_extension=xdebug" -ForegroundColor Gray
        Write-Host "     xdebug.mode=coverage" -ForegroundColor Gray
        Write-Host "  4. Reinicia Apache/PHP" -ForegroundColor Gray
        Write-Host ""
        Write-Host "  O usa el script: .\install-xdebug.ps1" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Continuando sin extension de cobertura..." -ForegroundColor Yellow
    } else {
        if ($phpInfo -match "xdebug") {
            Write-Host "[OK] Xdebug detectado y cargado" -ForegroundColor Green
            # Verificar configuración de modo
            try {
                $phpIniOutput = & php --ini 2>&1
                $phpIniContent = $phpIniOutput | Select-String "Loaded Configuration File" | ForEach-Object { 
                    if ($_ -match ":\s*(.+)") {
                        $matches[1].Trim()
                    }
                }
                if ($phpIniContent -and [string]::IsNullOrWhiteSpace($phpIniContent) -eq $false -and (Test-Path $phpIniContent)) {
                    $iniContent = Get-Content $phpIniContent -Raw
                    if ($iniContent -match "xdebug\.mode\s*=\s*coverage") {
                        Write-Host "[OK] Xdebug configurado para coverage" -ForegroundColor Green
                    } else {
                        Write-Host "[ADVERTENCIA] Xdebug no tiene modo coverage configurado" -ForegroundColor Yellow
                        Write-Host "  Agrega 'xdebug.mode=coverage' en php.ini" -ForegroundColor Gray
                    }
                }
            } catch {
                Write-Host "[INFO] No se pudo verificar configuración de php.ini" -ForegroundColor Gray
            }
        }
        if ($phpInfo -match "pcov") {
            Write-Host "[OK] PCOV detectado y cargado" -ForegroundColor Green
        }
    }
    Write-Host ""

    # Ejecutar PHPUnit - esto ejecutará TODOS los testsuites configurados en phpunit.xml
    Write-Host "Ejecutando PHPUnit (esto puede tardar varios minutos)..." -ForegroundColor Cyan
    Write-Host ""
    
    & vendor\bin\phpunit.bat @phpunitArgs
    
    Write-Host ""
    Write-Host "Verificando qué tests se ejecutaron..." -ForegroundColor Yellow
    
    # Verificar el reporte generado
    if (Test-Path $testReportFile) {
        $reportContent = Get-Content $testReportFile -Raw
        if ($reportContent -match 'tests="(\d+)"') {
            $totalTests = $matches[1]
            Write-Host "  Total de tests ejecutados: $totalTests" -ForegroundColor Cyan
            
            if ($reportContent -match '<testsuite name="Unit"') {
                Write-Host "  [OK] Tests Unit ejecutados" -ForegroundColor Green
            } else {
                Write-Host "  [ADVERTENCIA] Tests Unit NO ejecutados" -ForegroundColor Yellow
                Write-Host "    Esto puede causar baja cobertura. Verifica errores arriba." -ForegroundColor Yellow
            }
            
            if ($reportContent -match '<testsuite name="Feature"') {
                Write-Host "  [OK] Tests Feature ejecutados" -ForegroundColor Green
            } else {
                Write-Host "  [ADVERTENCIA] Tests Feature NO ejecutados" -ForegroundColor Yellow
            }
        }
    }
    Write-Host ""
    
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
        
        # Verificar si el archivo tiene datos de cobertura reales
        $coverageContent = Get-Content $coverageFile -Raw
        if ($coverageContent -match 'coveredstatements="0"|count="0"') {
            # Verificar si TODOS los valores están en 0
            $allZero = $coverageContent -match 'coveredstatements="0".*coveredmethods="0".*coveredelements="0"'
            if ($allZero -or ($coverageContent -notmatch 'coveredstatements="[1-9]')) {
                Write-Host ""
                Write-Host "========================================" -ForegroundColor Yellow
                Write-Host "ADVERTENCIA: coverage.xml tiene 0% de cobertura" -ForegroundColor Yellow
                Write-Host "========================================" -ForegroundColor Yellow
                Write-Host ""
                Write-Host "El archivo coverage.xml se generó pero todos los valores están en 0." -ForegroundColor Yellow
                Write-Host "Esto significa que Xdebug/PCOV no está instalado o no está funcionando." -ForegroundColor Yellow
                Write-Host ""
                Write-Host "Para solucionarlo:" -ForegroundColor Cyan
                Write-Host "  1. Ejecuta: .\install-xdebug-automatic.ps1" -ForegroundColor White
                Write-Host "  2. Sigue las instrucciones para instalar Xdebug" -ForegroundColor Gray
                Write-Host "  3. Reinicia Apache/PHP" -ForegroundColor Gray
                Write-Host "  4. Vuelve a ejecutar este script" -ForegroundColor Gray
                Write-Host ""
            } else {
                # Extraer porcentaje de cobertura si está disponible
                if ($coverageContent -match 'line-rate="([^"]+)"') {
                    $lineRate = [double]$matches[1] * 100
                    Write-Host "[INFO] Cobertura de líneas: $([math]::Round($lineRate, 2))%" -ForegroundColor Cyan
                }
            }
        }
        $reportsGenerated = $true
    } else {
        Write-Host "[ADVERTENCIA] coverage.xml NO encontrado" -ForegroundColor Yellow
        Write-Host "  Esto es normal si no tienes extension de cobertura instalada." -ForegroundColor Gray
        Write-Host "  SonarQube puede funcionar sin coverage, pero lo necesita para mostrar cobertura." -ForegroundColor Gray
        Write-Host ""
        Write-Host "  Para instalar Xdebug: .\install-xdebug-automatic.ps1" -ForegroundColor Cyan
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

