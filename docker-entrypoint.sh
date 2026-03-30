#!/bin/bash
set -e

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:clear
php artisan config:cache
php artisan route:cache

apache2-foreground