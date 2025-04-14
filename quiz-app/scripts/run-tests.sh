#!/bin/bash

# Ensure we're in the project root
cd "$(dirname "$0")/.." || exit 1

# Create test database if needed
php scripts/create-test-database.php

# Run the tests
php bin/phpunit 