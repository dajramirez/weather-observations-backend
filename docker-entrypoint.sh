#!/bin/bash
set -e

# Crear .env si no existe
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generar APP_KEY si no está definida
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan config:cache
php artisan route:cache

# Configurar Apache con el puerto que asigna Railway
RENDER_PORT=${PORT:-80}
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