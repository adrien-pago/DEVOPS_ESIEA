#!/bin/bash

# Définir l'environnement de test
export APP_ENV=test

echo "Création du schéma de la base de données..."
php scripts/create-schema.php

echo "Exécution des tests..."
./vendor/bin/phpunit 