#!/bin/bash

echo "📦 Running Laravel setup..."

# Run Laravel setup
php artisan optimize:clear
php artisan migrate --force

# Start the built-in server
php artisan serve --host=0.0.0.0 --port=10000
