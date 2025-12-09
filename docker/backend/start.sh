#!/bin/bash

# Esperar a que MySQL esté listo
echo "Esperando a que MySQL (database) esté listo..."
while ! nc -z database 3306; do
  sleep 1
done
echo "MySQL está listo!"

# Ejecutar migraciones
php artisan migrate --force

# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Iniciar PHP-FPM en segundo plano
php-fpm -D

# Iniciar Nginx
nginx -g "daemon off;"

