<?php
/**
 * Try common MySQL passwords for Herd
 */

echo "Testing common Laravel Herd MySQL passwords...\n\n";

$passwords = [
    '' => 'empty password',
    'root' => 'password: root',
    'password' => 'password: password',
    'herd' => 'password: herd',
    'secret' => 'password: secret',
];

$host = '127.0.0.1';
$port = 3306;
$user = 'root';

foreach ($passwords as $password => $description) {
    echo "Trying {$description}... ";
    
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port}",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "✓ SUCCESS!\n\n";
        echo "==============================================\n";
        echo " FOUND WORKING CREDENTIALS:\n";
        echo "==============================================\n";
        echo " Username: {$user}\n";
        echo " Password: " . ($password === '' ? '(empty)' : $password) . "\n";
        echo "==============================================\n\n";
        
        echo "Update your .env file:\n";
        echo "DB_PASSWORD=" . $password . "\n";
        echo "DB_PASSWORD_ANALYTICS=" . $password . "\n\n";
        
        // Try to create databases
        echo "Creating databases...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS studai_career CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ studai_career\n";
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS studai_career_analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ studai_career_analytics\n\n";
        
        echo "SUCCESS! Databases created. You can now run migrations!\n";
        exit(0);
        
    } catch (PDOException $e) {
        echo "✗ Failed\n";
    }
}

echo "\n❌ None of the common passwords worked.\n\n";
echo "Please check Laravel Herd application for MySQL credentials\n";
echo "or set a new password using Herd's database manager.\n\n";
echo "See MYSQL-SETUP-HELP.md for detailed instructions.\n";
