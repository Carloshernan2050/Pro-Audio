# Script de inicio para Docker en PowerShell
# Uso: .\docker-start.ps1

Write-Host "=== Iniciando contenedores Docker ===" -ForegroundColor Green

# Verificar si existe el archivo .env
if (-not (Test-Path .env)) {
    Write-Host "Advertencia: No se encontró el archivo .env" -ForegroundColor Yellow
    Write-Host "Por favor, crea un archivo .env con las variables necesarias" -ForegroundColor Yellow
    Write-Host ""
}

# Construir y levantar contenedores
Write-Host "Construyendo y levantando contenedores..." -ForegroundColor Cyan
docker-compose up -d --build

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=== Contenedores iniciados correctamente ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Backend:  http://localhost:8001" -ForegroundColor Cyan
    Write-Host "Frontend: http://localhost:3001" -ForegroundColor Cyan
    Write-Host "Database: http://localhost:3005 (contenedor pro-audio-db)" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Para ver los logs: docker-compose logs -f" -ForegroundColor Yellow
    Write-Host "Para detener: docker-compose down" -ForegroundColor Yellow
} else {
    Write-Host "Error al iniciar los contenedores" -ForegroundColor Red
    exit 1
}

