<?php
// Quick MySQL Connection Test

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing MySQL connection...\n\n";

try {
    // Try to connect
    $pdo = DB::connection()->getPdo();
    echo "✓ SUCCESS! Connected to MySQL\n";
    echo "  Server: " . DB::connection()->getConfig('host') . "\n";
    echo "  Database: " . DB::connection()->getConfig('database') . "\n\n";
    
    // Try to create databases
    echo "Creating databases...\n";
    DB::statement("CREATE DATABASE IF NOT EXISTS studai_career CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ studai_career created\n";
    
    DB::statement("CREATE DATABASE IF NOT EXISTS studai_career_analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ studai_career_analytics created\n\n";
    
    echo "Databases ready! You can now run migrations.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    
    if (str_contains($e->getMessage(), 'Access denied')) {
        echo "Solution: Set MySQL root password in .env:\n";
        echo "DB_PASSWORD=your_password\n\n";
        echo "Or run this in Laravel Herd/MySQL:\n";
        echo "ALTER USER 'root'@'localhost' IDENTIFIED BY '';\n";
        echo "FLUSH PRIVILEGES;\n";
    } elseif (str_contains($e->getMessage(), 'Connection refused')) {
        echo "Solution:\n";
        echo "1. Open Laravel Herd app\n";
        echo "2. Start MySQL service\n";
        echo "3. Try again\n";
    }
}
