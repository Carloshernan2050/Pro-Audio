# Script helper para ejecutar SonarQube Scanner
# Este script usa el token desde la variable de entorno SONAR_TOKEN

# Buscar el ejecutable real de sonar-scanner (no la función)
# Buscar sonar-scanner.bat en el PATH
$scannerExe = $null
$pathDirs = $env:Path -split ';' | Where-Object { $_ -and $_.Trim() -ne '' }
foreach ($dir in $pathDirs) {
    if ($dir -and $dir.Trim() -ne '') {
        $exePath = Join-Path $dir "sonar-scanner.bat"
        if (Test-Path $exePath) {
            $scannerExe = $exePath
            break
        }
    }
}

if (-not $scannerExe) {
    Write-Host "ERROR: sonar-scanner.bat no se encuentra en el PATH." -ForegroundColor Red
    Write-Host "Asegúrate de que sonar-scanner esté instalado y en el PATH del sistema." -ForegroundColor Yellow
    exit 1
}

# Obtener el token de diferentes fuentes (parámetro, variable de entorno, o prompt)
$token = $null

# 1. Intentar obtener desde parámetro del script
if ($args.Count -gt 0 -and -not [string]::IsNullOrWhiteSpace($args[0])) {
    $token = $args[0]
    Write-Host "[INFO] Token obtenido desde parámetro del script" -ForegroundColor Cyan
}
# 2. Intentar obtener desde variable de entorno de usuario
elseif (-not [string]::IsNullOrEmpty([System.Environment]::GetEnvironmentVariable('SONAR_TOKEN', 'User'))) {
    $token = [System.Environment]::GetEnvironmentVariable('SONAR_TOKEN', 'User')
    Write-Host "[INFO] Token obtenido desde variable de entorno SONAR_TOKEN (User)" -ForegroundColor Cyan
}
# 3. Intentar obtener desde variable de entorno del proceso
elseif (-not [string]::IsNullOrEmpty($env:SONAR_TOKEN)) {
    $token = $env:SONAR_TOKEN
    Write-Host "[INFO] Token obtenido desde variable de entorno SONAR_TOKEN (Process)" -ForegroundColor Cyan
}

if ([string]::IsNullOrEmpty($token)) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ERROR: Token de SonarQube no encontrado" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Opciones para configurar el token:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OPCION 1: Pasar el token como parámetro" -ForegroundColor Cyan
    Write-Host "  .\run-sonar-scanner.ps1 tu-token-aqui" -ForegroundColor Gray
    Write-Host ""
    Write-Host "OPCION 2: Configurar variable de entorno (recomendado)" -ForegroundColor Cyan
    Write-Host "  [System.Environment]::SetEnvironmentVariable('SONAR_TOKEN', 'tu-token-aqui', 'User')" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  Después de configurarla, cierra y vuelve a abrir PowerShell." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OPCION 3: Variable de entorno temporal (solo esta sesión)" -ForegroundColor Cyan
    Write-Host "  `$env:SONAR_TOKEN = 'tu-token-aqui'" -ForegroundColor Gray
    Write-Host "  .\run-sonar-scanner.ps1" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Para obtener tu token:" -ForegroundColor Yellow
    Write-Host "  1. Inicia sesión en: https://sonarqube.dataguaviare.com.co" -ForegroundColor Gray
    Write-Host "  2. Ve a: My Account > Security" -ForegroundColor Gray
    Write-Host "  3. Genera un nuevo token" -ForegroundColor Gray
    Write-Host ""
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ejecutando SonarQube Scanner..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Servidor: https://sonarqube.dataguaviare.com.co" -ForegroundColor Gray
Write-Host "Proyecto: pro-audio" -ForegroundColor Gray
Write-Host "Token: [configurado]" -ForegroundColor Green
Write-Host ""

# Verificar que los reportes existan antes de ejecutar
$coverageFile = "tests\_coverage\coverage.xml"
$testReportFile = "tests\_coverage\test-reporter.xml"

Write-Host "Verificando reportes..." -ForegroundColor Yellow
if (Test-Path $testReportFile) {
    Write-Host "[OK] test-reporter.xml encontrado" -ForegroundColor Green
} else {
    Write-Host "[ADVERTENCIA] test-reporter.xml no encontrado" -ForegroundColor Yellow
    Write-Host "  Ejecuta primero: .\generate-sonar-reports.ps1" -ForegroundColor Gray
}

if (Test-Path $coverageFile) {
    Write-Host "[OK] coverage.xml encontrado" -ForegroundColor Green
} else {
    Write-Host "[ADVERTENCIA] coverage.xml no encontrado" -ForegroundColor Yellow
    Write-Host "  Esto es normal si no tienes Xdebug/PCOV instalado" -ForegroundColor Gray
    Write-Host "  SonarQube funcionará sin coverage, pero no mostrará métricas de cobertura" -ForegroundColor Gray
}
Write-Host ""

# Ejecutar el scanner con el token
Write-Host "Iniciando análisis..." -ForegroundColor Cyan
Write-Host ""

# Verificar conectividad antes de ejecutar
Write-Host "Verificando conectividad con el servidor..." -ForegroundColor Yellow
try {
    $testUrl = "https://sonarqube.dataguaviare.com.co/api/system/status"
    $testResponse = Invoke-WebRequest -Uri $testUrl -TimeoutSec 15 -UseBasicParsing -ErrorAction Stop
    Write-Host "[OK] Servidor SonarQube está accesible" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] No se puede conectar al servidor SonarQube" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Posibles soluciones:" -ForegroundColor Cyan
    Write-Host "  1. Verifica tu conexión a internet" -ForegroundColor Gray
    Write-Host "  2. Verifica que el servidor esté en línea: https://sonarqube.dataguaviare.com.co" -ForegroundColor Gray
    Write-Host "  3. Verifica si hay un firewall bloqueando la conexión" -ForegroundColor Gray
    Write-Host "  4. Intenta nuevamente en unos minutos (el servidor puede estar sobrecargado)" -ForegroundColor Gray
    Write-Host ""
    exit 1
}
Write-Host ""

# Ejecutar el scanner con el token y opciones de timeout aumentadas
# Agregar opciones para aumentar timeout y mejorar la conexión
$scannerArgs = @(
    "-Dsonar.token=$token",
    "-Dsonar.ws.timeout=120",  # Timeout de 120 segundos para conexiones web
    "-Dsonar.forceAuthentication=true"
)

Write-Host "Ejecutando: $scannerExe $($scannerArgs -join ' ')" -ForegroundColor Gray
Write-Host ""
& $scannerExe $scannerArgs

# Verificar el resultado
if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Análisis completado exitosamente!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Revisa los resultados en:" -ForegroundColor Cyan
    Write-Host "  https://sonarqube.dataguaviare.com.co/dashboard?id=pro-audio" -ForegroundColor Yellow
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "El análisis falló" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Revisa los errores arriba para más detalles." -ForegroundColor Yellow
    Write-Host ""
}

