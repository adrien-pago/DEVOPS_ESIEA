<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env');

// Get database path from environment
$dbPath = dirname(__DIR__).'/var/test.db';

// Create database directory if it doesn't exist
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Create empty database file
if (!file_exists($dbPath)) {
    touch($dbPath);
    chmod($dbPath, 0666);
    echo "Database file created at: $dbPath\n";
} else {
    echo "Database file already exists at: $dbPath\n";
} 