#!/bin/bash

# StudAI Career Platform - Quick Start Script
# This script sets up the development environment

set -e

echo "=========================================="
echo "StudAI Career Platform - Quick Start"
echo "=========================================="
echo ""

# Check prerequisites
echo "Checking prerequisites..."

command -v php >/dev/null 2>&1 || { echo "Error: PHP is not installed"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "Error: Composer is not installed"; exit 1; }
command -v node >/dev/null 2>&1 || { echo "Error: Node.js is not installed"; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo "Error: MySQL is not installed"; exit 1; }
command -v redis-cli >/dev/null 2>&1 || { echo "Error: Redis is not installed"; exit 1; }

echo "✓ All prerequisites installed"
echo ""

# Install dependencies
echo "Installing Composer dependencies..."
composer install

echo "Installing NPM dependencies..."
npm install

echo "Installing WebPush package..."
composer require laravel-notification-channels/webpush

echo "✓ Dependencies installed"
echo ""

# Environment setup
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
    echo "✓ .env file created"
else
    echo "✓ .env file already exists"
fi
echo ""

# Database setup
echo "Setting up databases..."
read -p "Enter MySQL username [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Enter MySQL password: " DB_PASS
echo ""

read -p "Enter main database name [studai_career]: " DB_NAME
DB_NAME=${DB_NAME:-studai_career}

read -p "Enter analytics database name [studai_career_analytics]: " DB_ANALYTICS
DB_ANALYTICS=${DB_ANALYTICS:-studai_career_analytics}

# Create databases
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_ANALYTICS;"

# Update .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_ANALYTICS_DATABASE=.*/DB_ANALYTICS_DATABASE=$DB_ANALYTICS/" .env

echo "✓ Databases created"
echo ""

# Run migrations
echo "Running migrations..."
php artisan migrate --force

echo "✓ Migrations completed"
echo ""

# Generate VAPID keys
echo "Generating VAPID keys for push notifications..."
php artisan webpush:vapid

echo ""
echo "⚠️  IMPORTANT: Copy the VAPID keys above to your .env file:"
echo "VAPID_PUBLIC_KEY=..."
echo "VAPID_PRIVATE_KEY=..."
echo "VAPID_SUBJECT=mailto:admin@studai.com"
echo ""
read -p "Press Enter after you've updated .env with VAPID keys..."

# Build assets
echo "Building frontend assets..."
npm run build

echo "✓ Assets built"
echo ""

# Create admin account
echo "Creating admin account..."
read -p "Enter admin name [Admin]: " ADMIN_NAME
ADMIN_NAME=${ADMIN_NAME:-Admin}

read -p "Enter admin email [admin@studai.com]: " ADMIN_EMAIL
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@studai.com}

read -sp "Enter admin password: " ADMIN_PASSWORD
echo ""

php artisan tinker --execute="
\$user = App\Models\User::create([
    'name' => '$ADMIN_NAME',
    'email' => '$ADMIN_EMAIL',
    'password' => bcrypt('$ADMIN_PASSWORD'),
    'account_type' => 'admin',
    'email_verified_at' => now(),
]);
\$user->assignRole('admin');
echo 'Admin user created successfully';
"

echo "✓ Admin account created"
echo ""

# Cache configuration
echo "Caching configuration..."
php artisan config:cache

echo "✓ Configuration cached"
echo ""

# Create PWA icons directory
echo "Creating PWA icons directory..."
mkdir -p public/icons

echo "✓ Icons directory created"
echo ""
echo "⚠️  Remember to add PWA icons to public/icons/:"
echo "   - icon-192x192.png (required)"
echo "   - icon-512x512.png (required)"
echo "   - icon-72x72.png, icon-96x96.png, etc."
echo ""

# Final instructions
echo "=========================================="
echo "Setup Complete! 🎉"
echo "=========================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Update .env with your API keys:"
echo "   - OPENAI_API_KEY"
echo "   - RAZORPAY_KEY_ID & RAZORPAY_KEY_SECRET"
echo "   - PAYU_MERCHANT_KEY & PAYU_MERCHANT_SALT"
echo "   - MEILISEARCH_HOST & MEILISEARCH_KEY"
echo ""
echo "2. Create PWA icons in public/icons/"
echo ""
echo "3. Start services:"
echo "   Terminal 1: php artisan serve"
echo "   Terminal 2: php artisan queue:work"
echo "   Terminal 3: php artisan horizon"
echo "   Terminal 4: redis-server"
echo "   Terminal 5: meilisearch --master-key=masterKey"
echo ""
echo "4. Access the application:"
echo "   http://localhost:8000"
echo ""
echo "5. Login with admin credentials:"
echo "   Email: $ADMIN_EMAIL"
echo "   Password: (the one you entered)"
echo ""
echo "Documentation: /docs"
echo "API Docs: /api/documentation"
echo ""
echo "Happy coding! 🚀"
