#!/bin/bash
set -e

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan config:cache
php artisan route:cache

# Usar el puerto que asigna Render, o 80 por defecto
RENDER_PORT=${PORT:-80}

# Actualizar configuración de Apache con el puerto correcto
echo "Listen ${RENDER_PORT}" > /etc/apache2/ports.conf
echo "<VirtualHost *:${RENDER_PORT}>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

apache2-foreground