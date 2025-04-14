<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env');

// Create container
$container = new ContainerBuilder();
$configurator = new ContainerConfigurator($container, new FileLocator(dirname(__DIR__)), null);
$loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__)));

// Load services
$loader->load('config/services.yaml');
$loader->load('config/packages/doctrine.yaml');
$loader->load('config/packages/test/doctrine.yaml');

// Get EntityManager
$entityManager = $container->get(EntityManagerInterface::class);

// Create schema
$schemaTool = new SchemaTool($entityManager);
$metadata = $entityManager->getMetadataFactory()->getAllMetadata();

echo "Creating database schema...\n";
$schemaTool->createSchema($metadata);
echo "Schema created successfully!\n"; 