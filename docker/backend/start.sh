#!/bin/bash

# Esperar a que MySQL esté listo
echo "Esperando a que MySQL (database) esté listo..."
while ! nc -z database 3306; do
  sleep 1
done
echo "MySQL está listo!"

# Configurar permisos de storage (necesario cuando se montan volúmenes)
echo "Configurando permisos de storage..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Crear enlace simbólico de storage si no existe
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creando enlace simbólico de storage..."
    php artisan storage:link || echo "Advertencia: No se pudo crear el enlace de storage (puede que ya exista)"
fi

# Generar APP_KEY si no está configurado
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "APP_KEY no está configurado. Generando nueva clave..."
    php artisan key:generate --force
fi

# Limpiar caché antes de migraciones
echo "Limpiando caché..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Iniciar PHP-FPM en segundo plano
echo "Iniciando PHP-FPM..."
php-fpm -D

# Iniciar Nginx
echo "Iniciando Nginx..."
nginx -g "daemon off;"

