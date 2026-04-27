#!/usr/bin/env bash
# exit on error
set -o errexit

composer install --no-dev --optimize-autoloader

# Clear all caches to ensure fresh routes
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# No caching during build to avoid 404s on new routes
# php artisan optimize
