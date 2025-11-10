#!/bin/bash

# Deployment script for SmallTime v3
# Run this script on your AWS EC2 server in /var/www/html/donsmoore.com/timeclock/v3

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Navigate to application directory
cd /var/www/html/donsmoore.com/timeclock/v3

# Fix Git ownership and permissions (if needed)
echo "🔧 Fixing Git permissions..."
CURRENT_USER=$(whoami)
sudo chown -R $CURRENT_USER:$CURRENT_USER .git
git config --global --add safe.directory /var/www/html/donsmoore.com/timeclock/v3

# Pull latest changes from GitHub
echo "📥 Pulling latest changes from GitHub..."
git pull origin main

# Install/update PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Install/update Node dependencies
echo "📦 Installing Node dependencies..."
npm ci

# Build frontend assets
echo "🔨 Building frontend assets..."
npm run build

# Run database migrations (if needed)
# echo "🗄️  Running database migrations..."
# php artisan migrate --force

# Clear and cache configuration
echo "🧹 Clearing and caching configuration..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "✅ Deployment complete!"

