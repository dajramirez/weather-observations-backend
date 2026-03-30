#!/bin/bash
set -e

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan config:cache
php artisan route:cache

# Mostrar el error de Laravel en los logs
php artisan tinker --execute="DB::connection()->getPdo();" 2>&1 || echo "DB CONNECTION FAILED"

apache2-foreground