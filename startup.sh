#!/bin/bash
# StudAI Career - Azure App Service Startup Script
# NOTE: Do NOT use set -e here. A failed artisan command should NOT crash the container.

echo "========================================"
echo "StudAI Career - App Service Startup"
echo "$(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"

# ---- 1. Download Azure MySQL SSL Certificate ----
mkdir -p /home/site/ssl
if [ ! -f /home/site/ssl/DigiCertGlobalRootCA.crt.pem ]; then
  echo "Downloading MySQL SSL certificate..."
  curl -sL https://dl.cacerts.digicert.com/DigiCertGlobalRootCA.crt.pem \
    -o /home/site/ssl/DigiCertGlobalRootCA.crt.pem
fi

# ---- 2. Set Document Root and Symlinks ----
cd /home/site/wwwroot

# Create storage directories if they don't exist
mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs/{json,ai,agent,payments,security}
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage bootstrap/cache

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# ---- 3. Apply Custom Nginx Configuration ----
# Copy config to /home/site/default for future container starts
# AND apply to running nginx with reload
if [ -f /home/site/wwwroot/nginx-azure.conf ]; then
  cp /home/site/wwwroot/nginx-azure.conf /home/site/default
  cp /home/site/wwwroot/nginx-azure.conf /etc/nginx/sites-available/default 2>/dev/null || true
  cp /home/site/wwwroot/nginx-azure.conf /etc/nginx/sites-enabled/default 2>/dev/null || true
  echo "Testing nginx config..."
  if nginx -t 2>&1; then
    echo "Nginx config valid, reloading..."
    nginx -s reload 2>&1 || service nginx reload 2>&1 || killall -HUP nginx 2>&1 || echo "WARNING: Could not reload nginx"
    echo "Nginx reloaded with custom config"
  else
    echo "WARNING: Nginx config test failed, keeping default config"
  fi
fi

# ---- 4. Laravel Optimization ----
if [ "$APP_ENV" = "production" ]; then
  echo "Running production optimizations..."
  php artisan config:cache || echo "WARNING: config:cache failed"
  php artisan route:cache || echo "WARNING: route:cache failed"
  php artisan view:cache || echo "WARNING: view:cache failed"
  php artisan event:cache || echo "WARNING: event:cache failed"

  echo "Running migrations..."
  php artisan migrate --force --no-interaction || echo "WARNING: migrations failed"

  echo "Syncing Meilisearch indices..."
  php artisan scout:sync-index-settings 2>/dev/null || true
else
  echo "Development mode - clearing caches..."
  php artisan optimize:clear 2>/dev/null || true

  echo "Running migrations..."
  php artisan migrate --force --no-interaction || echo "WARNING: migrations failed"
fi

# ---- 5. Filament & Icon Cache ----
php artisan filament:cache-components 2>/dev/null || true
php artisan icons:cache 2>/dev/null || true

echo "========================================"
echo "Startup complete!"
echo "========================================"
