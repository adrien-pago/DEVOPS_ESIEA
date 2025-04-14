<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env.test');
} else {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env');
}

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
    // For SQLite, just ensure the directory exists
    $path = $parsedUrl['path'] ?? '';
    if ($path) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: $dir\n";
        }
        echo "SQLite database will be created at: $path\n";
    }
} elseif (strpos($scheme, 'mysql') !== false) {
    // For MySQL, we can use the doctrine:database:create command
    echo "For MySQL, please use the doctrine:database:create command.\n";
    echo "Example: php bin/console doctrine:database:create --env=test --if-not-exists\n";
} else {
    echo "Unsupported database type: $scheme\n";
    exit(1);
}

echo "Database setup completed successfully.\n"; 