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

# Obtener el token de la variable de entorno
$token = [System.Environment]::GetEnvironmentVariable('SONAR_TOKEN', 'User')

if ([string]::IsNullOrEmpty($token)) {
    Write-Host "ERROR: La variable de entorno SONAR_TOKEN no está configurada." -ForegroundColor Red
    Write-Host "Configúrala con:" -ForegroundColor Yellow
    Write-Host '[System.Environment]::SetEnvironmentVariable("SONAR_TOKEN", "tu-token-aqui", "User")' -ForegroundColor Gray
    exit 1
}

Write-Host "Ejecutando SonarQube Scanner..." -ForegroundColor Cyan
Write-Host "Token encontrado en variable de entorno." -ForegroundColor Green
Write-Host ""

# Ejecutar el scanner con el token
& $scannerExe -D"sonar.token=$token"

