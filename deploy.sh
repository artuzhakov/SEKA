#!/bin/bash
echo "🚀 Starting deployment..."

# Pull latest changes
git pull origin main

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install JS dependencies
npm install

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment completed!"