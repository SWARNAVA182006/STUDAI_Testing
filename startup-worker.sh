#!/bin/bash
set -e

echo "========================================"
echo "StudAI Career - Worker Startup"
echo "$(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"

cd /home/site/wwwroot

# ---- 1. Download Azure MySQL SSL Certificate ----
mkdir -p /home/site/ssl
if [ ! -f /home/site/ssl/DigiCertGlobalRootCA.crt.pem ]; then
  echo "Downloading MySQL SSL certificate..."
  curl -sL https://dl.cacerts.digicert.com/DigiCertGlobalRootCA.crt.pem \
    -o /home/site/ssl/DigiCertGlobalRootCA.crt.pem
fi

# ---- 2. Create storage directories ----
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/logs
mkdir -p storage/logs/json
mkdir -p storage/logs/ai
mkdir -p storage/logs/agent
mkdir -p storage/logs/payments
mkdir -p storage/logs/security
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ---- 3. Cache config for production ----
php artisan config:cache

# ---- 4. Start Laravel Horizon ----
# Horizon manages all 5 production supervisors defined in config/horizon.php:
#   - supervisor-critical: high queue (3 processes, 30s timeout)
#   - supervisor-default: default+low queues (2-10 processes, 90s timeout)
#   - supervisor-ai: ai queue (1-5 processes, 300s timeout)
#   - supervisor-search: search queue (1-3 processes, 120s timeout)
#   - supervisor-notifications: notifications queue (2 processes, 60s timeout)
echo "Starting Laravel Horizon..."
php artisan horizon &
HORIZON_PID=$!

# ---- 5. Start Laravel Scheduler ----
# Runs schedule:run every 60 seconds for 30+ scheduled tasks
# All tasks use onOneServer() for distributed safety via Redis atomic locks
echo "Starting Laravel Scheduler..."
while true; do
  php artisan schedule:run --no-interaction >> /home/site/wwwroot/storage/logs/scheduler.log 2>&1
  sleep 60
done &
SCHEDULER_PID=$!

echo "Horizon PID: $HORIZON_PID"
echo "Scheduler PID: $SCHEDULER_PID"
echo "========================================"
echo "Worker startup complete!"
echo "========================================"

# Wait for Horizon (primary process) - if Horizon dies, the container restarts
wait $HORIZON_PID
