@echo off
REM StudAI Career Platform - Quick Start Script (Windows)
REM This script sets up the development environment

echo ==========================================
echo StudAI Career Platform - Quick Start
echo ==========================================
echo.

REM Check prerequisites
echo Checking prerequisites...

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: PHP is not installed
    exit /b 1
)

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: Composer is not installed
    exit /b 1
)

where node >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: Node.js is not installed
    exit /b 1
)

echo + All prerequisites installed
echo.

REM Install dependencies
echo Installing Composer dependencies...
call composer install

echo Installing NPM dependencies...
call npm install

echo Installing WebPush package...
call composer require laravel-notification-channels/webpush

echo + Dependencies installed
echo.

REM Environment setup
if not exist .env (
    echo Creating .env file...
    copy .env.example .env
    php artisan key:generate
    echo + .env file created
) else (
    echo + .env file already exists
)
echo.

REM Database setup
echo Setting up databases...
set /p DB_USER="Enter MySQL username [root]: "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_PASS="Enter MySQL password: "

set /p DB_NAME="Enter main database name [studai_career]: "
if "%DB_NAME%"=="" set DB_NAME=studai_career

set /p DB_ANALYTICS="Enter analytics database name [studai_career_analytics]: "
if "%DB_ANALYTICS%"=="" set DB_ANALYTICS=studai_career_analytics

REM Create databases
mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME%;"
mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_ANALYTICS%;"

echo + Databases created
echo.

REM Update .env (manual step required on Windows)
echo.
echo Please update your .env file with these database credentials:
echo DB_USERNAME=%DB_USER%
echo DB_PASSWORD=%DB_PASS%
echo DB_DATABASE=%DB_NAME%
echo DB_ANALYTICS_DATABASE=%DB_ANALYTICS%
echo.
pause

REM Run migrations
echo Running migrations...
php artisan migrate --force

echo + Migrations completed
echo.

REM Generate VAPID keys
echo Generating VAPID keys for push notifications...
php artisan webpush:vapid

echo.
echo WARNING: Copy the VAPID keys above to your .env file:
echo VAPID_PUBLIC_KEY=...
echo VAPID_PRIVATE_KEY=...
echo VAPID_SUBJECT=mailto:admin@studai.com
echo.
pause

REM Build assets
echo Building frontend assets...
call npm run build

echo + Assets built
echo.

REM Create admin account
echo Creating admin account...
set /p ADMIN_NAME="Enter admin name [Admin]: "
if "%ADMIN_NAME%"=="" set ADMIN_NAME=Admin

set /p ADMIN_EMAIL="Enter admin email [admin@studai.com]: "
if "%ADMIN_EMAIL%"=="" set ADMIN_EMAIL=admin@studai.com

set /p ADMIN_PASSWORD="Enter admin password: "

php artisan tinker --execute="$user = App\Models\User::create(['name' => '%ADMIN_NAME%', 'email' => '%ADMIN_EMAIL%', 'password' => bcrypt('%ADMIN_PASSWORD%'), 'account_type' => 'admin', 'email_verified_at' => now()]); $user->assignRole('admin'); echo 'Admin user created';"

echo + Admin account created
echo.

REM Cache configuration
echo Caching configuration...
php artisan config:cache

echo + Configuration cached
echo.

REM Create PWA icons directory
echo Creating PWA icons directory...
if not exist public\icons mkdir public\icons

echo + Icons directory created
echo.
echo WARNING: Remember to add PWA icons to public/icons/:
echo    - icon-192x192.png (required)
echo    - icon-512x512.png (required)
echo    - icon-72x72.png, icon-96x96.png, etc.
echo.

REM Final instructions
echo ==========================================
echo Setup Complete! 🎉
echo ==========================================
echo.
echo Next steps:
echo.
echo 1. Update .env with your API keys:
echo    - OPENAI_API_KEY
echo    - RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET
echo    - PAYU_MERCHANT_KEY and PAYU_MERCHANT_SALT
echo    - MEILISEARCH_HOST and MEILISEARCH_KEY
echo.
echo 2. Create PWA icons in public/icons/
echo.
echo 3. Start services (open separate terminals):
echo    Terminal 1: php artisan serve
echo    Terminal 2: php artisan queue:work
echo    Terminal 3: php artisan horizon
echo    Terminal 4: redis-server
echo    Terminal 5: meilisearch --master-key=masterKey
echo.
echo 4. Access the application:
echo    http://localhost:8000
echo.
echo 5. Login with admin credentials:
echo    Email: %ADMIN_EMAIL%
echo    Password: (the one you entered)
echo.
echo Documentation: /docs
echo API Docs: /api/documentation
echo.
echo Happy coding! 🚀
echo.
pause
