<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Charger les variables d'environnement de test
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env.test');

// Définir l'environnement de test
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';

$kernel = new Kernel('test', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Supprimer le schéma existant
$metadata = $entityManager->getMetadataFactory()->getAllMetadata();
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
$schemaTool->dropSchema($metadata);

// Créer le nouveau schéma
$schemaTool->createSchema($metadata);

echo "Schema created successfully!\n"; 