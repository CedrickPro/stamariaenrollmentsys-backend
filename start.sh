#!/usr/bin/env bash

# Clear all Laravel caches at startup
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run migrations to ensure DB is up to date
php artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
