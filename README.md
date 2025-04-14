# Quiz Application - DevOps Project

## Description
Application de quiz en ligne développée avec Symfony, suivant les principes DevOps.

## Technologies utilisées
- Symfony 6.4
- Docker
- PHP 8.2
- MySQL
- PHPUnit pour les tests

## Structure du projet
```
.
├── docker/                    # Configuration Docker
│   ├── Dockerfile            # Configuration PHP
│   ├── docker-compose.yml    # Configuration des services
│   └── nginx/               # Configuration Nginx
│
├── quiz-app/                 # Application Symfony
│   ├── src/                 # Code source de l'application
│   ├── tests/              # Tests automatisés
│   ├── config/             # Configuration Symfony
│   ├── public/             # Point d'entrée web
│   └── ...
│
├── README.md                # Documentation principale
└── cahier des charges.txt   # Spécifications du projet
```

## Installation
1. Cloner le repository
2. Lancer les conteneurs Docker : `docker-compose up -d`
3. Installer les dépendances : `composer install`
4. Configurer la base de données
5. Lancer les migrations : `php bin/console doctrine:migrations:migrate`

## Tests
Pour lancer les tests : `php bin/phpunit`

## Fonctionnalités implémentées
- [ ] Back des quiz avec persistance
- [ ] Back pour répondre aux quiz
- [ ] Calcul automatique de la note du quiz
- [ ] Déploiement sur DockerHub

## Workflow Git
- Branche `main` : code en production
- Branches feature : développement des nouvelles fonctionnalités
- Tests automatisés avant merge sur main
- CI/CD avec Docker.