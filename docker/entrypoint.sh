#!/bin/sh

set -eu

echo "🔧 Starting Laravel Entrypoint..."

# Ensure vendor/autoload.php exists
if [ ! -f "vendor/autoload.php" ]; then
  echo "❌ vendor/autoload.php not found. Did you forget to run 'composer install' or mount the vendor volume?"
  exit 1
fi

# Ensure .env exists
if [ ! -f ".env" ]; then
  echo "❌ .env file not found. Copy .env.example or provide one."
  exit 1
fi

# Run database migrations
echo "🗃️ Running migrations..."
php artisan migrate --force || echo "⚠️ Warning: Migration failed."

# Seed database
echo "🌱 Seeding database..."
php artisan db:seed --force || echo "⚠️ Warning: Seeding failed."

# Generate Filament Shield permissions
echo "🛡️ Generating Filament Shield permissions..."
php artisan shield:generate --all --panel=admin || echo "⚠️ Warning: Failed to generate Shield permissions."

# Refresh Laravel config if APP_URL is set
if grep -q '^APP_URL=https://' .env; then
  echo "🔁 Detected Ngrok APP_URL. Refreshing Laravel caches..."
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear

  php artisan config:cache || echo "⚠️ config:cache failed"
  php artisan route:cache || echo "⚠️ route:cache failed"
  php artisan view:cache || echo "⚠️ view:cache failed"
else
  echo "ℹ️ APP_URL is not HTTPS. Skipping config cache refresh."
fi

# Start PHP-FPM
echo "🚀 Starting PHP-FPM..."
exec php-fpm
