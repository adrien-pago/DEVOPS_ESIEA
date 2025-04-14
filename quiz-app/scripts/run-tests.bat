@echo off
setlocal

REM Définir l'environnement de test
set APP_ENV=test

echo Creation du schema de la base de donnees...
php scripts/create-schema.php

echo Execution des tests...
vendor\bin\phpunit.bat

REM Clean up
echo Cleaning up...
if exist var\test.db del var\test.db

endlocal 