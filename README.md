# Quiz Application - DevOps Project

# Question DEVOPS

## Comment définiriez-vous le DevOps ?
Le DevOps est une démarche collaborative entre les équipes de développement et d'opérations. 
Il vise à automatiser les processus de déploiement.

## Qu'impose le DevOps ?
Le DevOps impose une très bonne organisation des tâches et une très bonne méthodologie.
Il requiert également une collaboration continue entre les différentes équipes impliquées dans le cycle de vie d'une application.

## Quels sont les inconvénients ou les faiblesses du DevOps ?
La mise en place initiale qui peut être complexe, la nécessité d'instaurer une forte culture d'entreprise et l'investissement en formation pour maîtriser les outils d'automatisation indispensables à sa mise en œuvre.

## Quel est votre avis sur le DevOps ?
Le DevOps est indispensable pour faire évoluer un projet mais plus ou moins lourd à mettre en place selon les projets.

## Quels sont les tests primordiaux pour toute application ?
Pour assurer la qualité d'une application, il est essentiel de mettre en place :
- Des **tests unitaires** pour vérifier le bon fonctionnement des composants individuels ;
- Des **tests d'intégration** pour s'assurer de la cohérence entre les différents modules ;
- Des **tests fonctionnels** pour valider que l'application répond aux besoins métier et se comporte comme prévu lors des scénarios d'utilisation.
- Des **tests de sécurité** pour identifier et corriger les vulnérabilités (comme les injections SQL, les failles XSS, etc.).

## Description
Application de quiz en ligne développée avec Symfony, suivant les principes DevOps.

## Technologies utilisées
- Symfony 6.4
- Docker
- PHP 8.2
- SQLite
- PHPUnit pour les tests

## Structure du projet
```
.
├── docker/                    # Configuration Docker
│   ├── Dockerfile            # Configuration PHP de développement
│   ├── Dockerfile.nginx      # Configuration Nginx de développement
│   ├── Dockerfile.prod       # Configuration PHP de production
│   ├── docker-compose.yml    # Configuration des services de développement
│   ├── docker-compose.prod.yml # Configuration des services de production
│   └── nginx/               # Configuration Nginx
│       ├── default.conf     # Configuration Nginx de développement
│       └── prod.conf        # Configuration Nginx de production
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
- [x] Back des quiz avec persistance
- [x] Back pour répondre aux quiz
- [x] Calcul automatique de la note du quiz
- [x] Déploiement sur DockerHub
- [x] Ajout des utilisateurs créateur de quiz

## Workflow Git
- Branche `main` : code en production
- Branches feature : développement des nouvelles fonctionnalités
- Tests automatisés avant merge sur main
- CI/CD avec Docker