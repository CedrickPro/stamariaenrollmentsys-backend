#!/usr/bin/env bash
# exit on error
set -o errexit

composer install --no-dev --optimize-autoloader

# Run migrations if you have a DB connected
# php artisan migrate --force

# Optimize
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
