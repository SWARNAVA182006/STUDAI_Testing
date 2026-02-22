<?php
// Check existing database tables

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking existing database structure...\n\n";

try {
    // Get all tables
    $tables = DB::select('SHOW TABLES');
    $tableCount = count($tables);
    
    echo "Found {$tableCount} existing tables:\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  • {$tableName}\n";
        
        // Get row count
        $count = DB::table($tableName)->count();
        echo "    Rows: {$count}\n";
    }
    
    echo str_repeat("=", 50) . "\n\n";
    
    if ($tableCount > 0) {
        echo "⚠️  Database is not empty!\n\n";
        echo "Options:\n";
        echo "1. Drop all tables and start fresh: php artisan migrate:fresh\n";
        echo "2. Keep existing data and work with it\n";
        echo "3. Use a different database for StudAI Career\n\n";
    } else {
        echo "✓ Database is empty, ready for migrations!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
