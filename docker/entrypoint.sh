#!/bin/sh

set -eu

echo "🔧 Starting Laravel Entrypoint..."

# Ensure vendor/autoload.php exists
if [ ! -f "vendor/autoload.php" ]; then
  echo "❌ vendor/autoload.php not found. Did you forget to run 'composer install' or mount the vendor volume?"
  exit 1
fi

# Generate app key if missing
if ! grep -q "^APP_KEY=base64:" .env; then
  echo "🔐 No app key found. Generating app key..."
  php artisan key:generate --no-interaction
fi

# Run database migrations
echo "🗃️ Running migrations..."
php artisan migrate --force || echo "⚠️ Warning: Migration failed."

# Seed database
echo "🌱 Seeding database..."
php artisan db:seed --force || echo "⚠️ Warning: Seeding failed."

# Generate Filament Shield permissions (admin panel)
echo "🛡️ Generating Filament Shield permissions..."
php artisan shield:generate --all --panel=admin || echo "⚠️ Warning: Failed to generate Shield permissions."

# Cache Laravel config, routes, views
echo "🗂️ Caching Laravel config, routes, and views..."
php artisan config:cache || echo "⚠️ config:cache failed"
php artisan route:cache || echo "⚠️ route:cache failed"
php artisan view:cache || echo "⚠️ view:cache failed"

# Start PHP-FPM
echo "🚀 Starting PHP-FPM..."
exec php-fpm
