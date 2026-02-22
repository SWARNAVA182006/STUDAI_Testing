@echo off
echo ========================================
echo StudAI Career - Autonomous Agent Setup
echo ========================================
echo.

cd /d E:\downloads\career\studai-career

echo [1/5] Checking database migrations...
php artisan migrate:status
echo.

echo [2/5] Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo.

echo [3/5] Checking scheduler tasks...
php artisan schedule:list
echo.

echo [4/5] Testing queue connection...
php artisan queue:work --tries=1 --timeout=5 --max-jobs=1
echo.

echo [5/5] Setup complete!
echo.
echo ========================================
echo Next Steps:
echo ========================================
echo 1. Start queue worker: start-queue.bat
echo 2. Configure Task Scheduler (see DEPLOYMENT.md)
echo 3. Access agent at: http://localhost:8000/agent/dashboard
echo.
echo For full instructions, see:
echo   AUTONOMOUS_AGENT_DEPLOYMENT.md
echo ========================================
pause
