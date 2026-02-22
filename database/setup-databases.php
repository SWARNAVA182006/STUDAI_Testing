<?php
/**
 * Database Setup Script for StudAI Career Platform
 * Creates both main and analytics databases
 */

echo "==============================================\n";
echo " StudAI Career - Database Setup\n";
echo "==============================================\n\n";

// Database configuration
$host = '127.0.0.1';
$port = 3306;
$root_user = 'root';
$root_password = ''; // Herd usually uses empty password for root

// Database names
$databases = [
    'studai_career' => 'Main transactional database',
    'studai_career_analytics' => 'Analytics and reporting database'
];

try {
    // Connect to MySQL
    echo "Connecting to MySQL at {$host}:{$port}...\n";
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        $root_user,
        $root_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Connected successfully!\n\n";
    
    // Create databases
    foreach ($databases as $db_name => $description) {
        echo "Creating database: {$db_name}\n";
        echo "  Purpose: {$description}\n";
        
        $sql = "CREATE DATABASE IF NOT EXISTS `{$db_name}` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✓ Database '{$db_name}' created successfully!\n\n";
    }
    
    // Verify databases
    echo "Verifying databases...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'studai_career%'");
    $created_dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($created_dbs as $db) {
        echo "  ✓ {$db}\n";
    }
    
    echo "\n==============================================\n";
    echo "SUCCESS! Databases created successfully.\n";
    echo "==============================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Update .env file with database credentials\n";
    echo "2. Run: php artisan migrate\n";
    echo "3. Start building!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    
    if (str_contains($e->getMessage(), 'Access denied')) {
        echo "Please update the credentials in this script:\n";
        echo "  \$root_user = 'root';\n";
        echo "  \$root_password = 'your_password_here';\n\n";
    } elseif (str_contains($e->getMessage(), 'Connection refused')) {
        echo "Make sure MySQL is running via Herd:\n";
        echo "1. Open Laravel Herd application\n";
        echo "2. Ensure MySQL service is started\n";
        echo "3. Try again\n\n";
    }
    
    exit(1);
}
