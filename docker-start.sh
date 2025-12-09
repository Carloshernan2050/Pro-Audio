#!/bin/bash

# Script de inicio para Docker en Linux/Mac
# Uso: ./docker-start.sh

echo "=== Iniciando contenedores Docker ==="

# Verificar si existe el archivo .env
if [ ! -f .env ]; then
    echo "Advertencia: No se encontró el archivo .env"
    echo "Por favor, crea un archivo .env con las variables necesarias"
    echo ""
fi

# Construir y levantar contenedores
echo "Construyendo y levantando contenedores..."
docker-compose up -d --build

if [ $? -eq 0 ]; then
    echo ""
    echo "=== Contenedores iniciados correctamente ==="
    echo ""
    echo "Backend:  http://localhost:8001"
    echo "Frontend: http://localhost:3001"
    echo "Database: http://localhost:3005 (contenedor pro-audio-db)"
    echo ""
    echo "Para ver los logs: docker-compose logs -f"
    echo "Para detener: docker-compose down"
else
    echo "Error al iniciar los contenedores"
    exit 1
fi

