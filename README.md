# Système National de Gestion de l'Éducation (SNGE) - Backend (API)

## Vue d'Ensemble
Le module Backend du Système National de Gestion de l'Éducation (SNGE) est une API REST robuste et sécurisée propulsant l'ensemble de la plateforme. Développé avec Laravel 12, il gère la logique métier complexe, l'accès à la base de données, la sécurité et la génération de rapports pour l'ensemble du système éducatif du Burundi.

## Fonctionnalités Principales
- **API RESTful Sécurisée** : Points d'accès pour tous les modules du système (Écoles, Élèves, Professeurs, Parents, etc.).
- **Gestion des Permissions (RBAC)** : Système granulaire de rôles (Administrateur, Agent Provincial, Directeur, Enseignant, Inspecteur) géré via Spatie Permission.
- **Génération de Documents** : Création automatique de bulletins et rapports en PDF (via DOMPDF) et exports complexes Excel (via Maatwebsite Excel).
- **Authentification & Sécurité** : Authentification stateless par tokens via Laravel Sanctum.
- **Documentation API** : Génération automatique de la documentation OpenAPI via Scramble.
- **Traçabilité** : Journalisation de toutes les activités critiques via Spatie Activitylog.
- **Traitement Asynchrone** : Utilisation des Jobs/Queues pour les tâches lourdes (calculs de moyennes, imports massifs).

## Technologies & Stack Technique
- **Framework** : Laravel 12
- **Langage** : PHP 8.4
- **Base de Données** : MySQL / PostgreSQL (via Eloquent ORM)
- **Authentification** : Laravel Sanctum
- **Gestion des Rôles** : Spatie Laravel-Permission
- **Génération PDF** : barryvdh/laravel-dompdf
- **Génération Excel** : maatwebsite/excel
- **Documentation API** : dedoc/scramble
- **Logs d'Activité** : spatie/laravel-activitylog
- **Tests** : Pest PHP v3

## Prérequis
- PHP 8.4+
- Composer
- Base de données (MySQL/PostgreSQL)
- Serveur Redis (pour les files d'attente et gestion du cache, optionnel mais recommandé)

## Installation et Lancement

1. Installer les dépendances PHP :
   ```bash
   composer install
   ```

2. Configurer les variables d'environnement :
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *N'oubliez pas de configurer les accès à votre base de données dans le fichier `.env`.*

3. Exécuter les migrations et seeders :
   ```bash
   php artisan migrate --seed
   ```

4. Lancer le serveur de développement :
   ```bash
   php artisan serve
   ```

## Tests
Le projet utilise Pest pour les tests automatisés (Feature et Unit tests).
Pour lancer la suite de tests :
```bash
php artisan test
```

## Architecture & Conventions
- Suivi strict des principes et de la nouvelle structure minimaliste de Laravel 12 (configuration via `bootstrap/app.php`).
- Utilisation des `FormRequests` pour la validation stricte des données entrantes.
- Utilisation des `API Resources` d'Eloquent pour le formatage propre et standardisé des réponses JSON.
- Code strictement formaté avec `Laravel Pint`.
