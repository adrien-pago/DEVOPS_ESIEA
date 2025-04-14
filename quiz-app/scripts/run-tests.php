<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

// Create database and schema
echo "Setting up test database...\n";
require __DIR__.'/create-database.php';
require __DIR__.'/create-schema.php';

// Run PHPUnit tests
echo "\nRunning tests...\n";
$process = new Process(['php', dirname(__DIR__).'/vendor/bin/phpunit']);
$process->setWorkingDirectory(dirname(__DIR__));

try {
    $process->run(function ($type, $buffer) {
        echo $buffer;
    });

    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }
} catch (ProcessFailedException $e) {
    echo $e->getMessage();
    exit(1);
} 