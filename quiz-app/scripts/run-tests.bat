@echo off
REM Ensure we're in the project root
cd /d "%~dp0.."

REM Create test database if needed
php scripts/create-test-database.php

REM Run the tests
php bin/phpunit 