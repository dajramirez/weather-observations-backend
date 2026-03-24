#!/bin/bash
set -e

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Limpiar y cachear configuración
php artisan config:cache
php artisan route:cache

# Iniciar Apache
apache2-foreground