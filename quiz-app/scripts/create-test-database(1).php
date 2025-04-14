<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env');

// Get database URL
$databaseUrl = $_ENV['DATABASE_URL'] ?? null;

if (!$databaseUrl) {
    echo "No DATABASE_URL found in environment variables.\n";
    exit(1);
}

// Parse database URL
$parsedUrl = parse_url($databaseUrl);
$scheme = $parsedUrl['scheme'] ?? '';

// Handle different database types
if (strpos($scheme, 'sqlite') !== false) {
    // For SQLite, we just need to ensure the directory exists
    $path = str_replace('%kernel.project_dir%', dirname(__DIR__), $parsedUrl['path']);
    $dir = dirname($path);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Create empty database file if it doesn't exist
    if (!file_exists($path)) {
        touch($path);
    }
    
    echo "SQLite database created at: $path\n";
} elseif (strpos($scheme, 'mysql') !== false) {
    // For MySQL, we need to create the database
    $dbname = substr($parsedUrl['path'], 1); // Remove leading slash
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = $parsedUrl['port'] ?? 3306;
    $user = $parsedUrl['user'] ?? 'root';
    $pass = $parsedUrl['pass'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        echo "MySQL database '$dbname' created successfully.\n";
    } catch (PDOException $e) {
        echo "Error creating MySQL database: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Unsupported database type: $scheme\n";
    exit(1);
}

echo "Database creation completed successfully.\n"; 