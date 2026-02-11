# RUBEN - Développeur Backend Core & Tests

## Rôle dans le projet

Responsable principal du **noyau backend**, de la **qualité du code** et de la **couverture de tests**. RUBEN s'assure que le socle technique est solide, que les tests garantissent la stabilité du système et que le workflow d'inscription fonctionne parfaitement.

---

## Tâches Assignées

### 1. Suite de Tests Complète

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Développer une suite de tests exhaustive pour couvrir les fonctionnalités critiques du système. Actuellement seulement 4 tests stub existent - objectif minimum : 80+ tests. |
| Livrable | Tests Pest dans `tests/Feature/` et `tests/Unit/` couvrant : authentification, autorisation, CRUD schools, CRUD élèves, workflow inscriptions, validation des données |

**Sous-tâches :**
- [ ] Tests d'authentification (login, logout, me, tokens)
- [ ] Tests d'autorisation (rôles, permissions, policies)
- [ ] Tests CRUD Schools (création, lecture, mise à jour, suppression, workflow)
- [ ] Tests CRUD Élèves (matricule unique, soft delete, recherche)
- [ ] Tests Inscriptions (création, soumission, validation, rejet)
- [ ] Tests Campagnes (ouverture, fermeture, contraintes)
- [ ] Tests Classes et Affectations
- [ ] Tests de la hiérarchie géographique (cascade des données)
- [ ] Tests du trait HasDataScope (filtrage par niveau admin)

---

### 2. Workflow Inscriptions - Implémentation Complète

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Finaliser l'implémentation du workflow d'inscription élèves avec les endpoints de soumission, validation et rejet. Les routes sont déclarées mais la logique métier est incomplète. |
| Livrable | Endpoints fonctionnels : `POST /api/inscriptions/{id}/soumettre`, `POST /api/inscriptions/{id}/valider`, `POST /api/inscriptions/{id}/rejeter` avec toutes les règles métier (RI01-RI06) |

**Sous-tâches :**
- [ ] Implémenter `soumettre()` - transition brouillon → soumis
- [ ] Implémenter `valider()` - transition soumis → validé avec création affectation
- [ ] Implémenter `rejeter()` - transition soumis → rejeté avec motif obligatoire
- [ ] Valider les règles métier : unicité inscription/élève/année, campagne active obligatoire
- [ ] Gérer les notifications (événements Laravel)
- [ ] Ajouter les champs d'audit (soumis_par, valide_par, rejete_par avec timestamps)

---

### 3. Consolidation des Modèles Dupliqués

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Le projet contient des modèles en double : `School.php` / `Ecole.php` et `Inscription.php` / `InscriptionEleve.php`. Consolider en gardant un seul modèle par entité. |
| Livrable | Un seul modèle par entité, migrations de données si nécessaire, mise à jour de tous les contrôleurs et relations |

**Sous-tâches :**
- [ ] Analyser les différences entre School et Ecole
- [ ] Choisir le modèle principal et migrer les fonctionnalités
- [ ] Mettre à jour toutes les références (contrôleurs, routes, policies)
- [ ] Répéter pour Inscription/InscriptionEleve
- [ ] Supprimer les modèles obsolètes
- [ ] Mettre à jour les tests

---

### 4. Standardisation des Réponses d'Erreur API

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Créer un format standardisé pour toutes les réponses d'erreur de l'API. Actuellement les erreurs ne suivent pas un format uniforme. |
| Livrable | Classe `ApiResponse` ou trait pour formater les réponses, Handler d'exceptions personnalisé, documentation du format |

**Sous-tâches :**
- [ ] Créer un trait ou helper pour les réponses API standardisées
- [ ] Configurer le Handler d'exceptions pour les erreurs de validation
- [ ] Définir les codes d'erreur métier (ex: ERR_INSCRIPTION_DUPLICATE)
- [ ] Documenter le format de réponse standard
- [ ] Appliquer à tous les contrôleurs existants

---

### 5. Gestion des Campagnes d'Inscription

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Implémenter la logique complète de gestion des campagnes : ouverture, fermeture, contraintes de dates, statuts automatiques. |
| Livrable | Endpoints campagnes fonctionnels, logique de statut automatique, validation des contraintes de dates |

**Sous-tâches :**
- [ ] Implémenter `ouvrir()` - passer une campagne en statut OUVERTE
- [ ] Implémenter `fermer()` - passer une campagne en statut FERMEE
- [ ] Ajouter validation : une seule campagne active par type/année/école
- [ ] Gérer les dates de début/fin automatiquement
- [ ] Empêcher les inscriptions sur campagne fermée

---

### 6. Workflow Mouvements Élèves

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Finaliser le workflow des mouvements élèves (transferts, abandons, décès) avec les validations et autorisations appropriées. |
| Livrable | Endpoints mouvements fonctionnels avec workflow de validation (RM01-RM04) |

**Sous-tâches :**
- [ ] Implémenter la création de demande de mouvement
- [ ] Implémenter la validation hiérarchique
- [ ] Gérer les transferts inter-écoles avec école destination
- [ ] Mettre à jour le statut élève automatiquement après validation
- [ ] Ajouter les contraintes : pas de mouvement si inscription en cours

---

## Fichiers Principaux à Modifier

```
API_NEMS/
├── tests/
│   ├── Feature/
│   │   ├── AuthenticationTest.php (à créer)
│   │   ├── SchoolTest.php (à créer)
│   │   ├── EleveTest.php (à créer)
│   │   ├── InscriptionTest.php (à créer)
│   │   └── CampagneTest.php (à créer)
│   └── Unit/
│       ├── HasDataScopeTest.php (à créer)
│       └── ModelsTest.php (à créer)
├── app/
│   ├── Models/
│   │   ├── InscriptionEleve.php (à compléter)
│   │   ├── CampagneInscription.php (à compléter)
│   │   └── MouvementEleve.php (à compléter)
│   └── Http/Controllers/Api/
│       ├── InscriptionEleveController.php (workflow)
│       └── CampagneInscriptionController.php (statuts)
```

---

## Commandes Utiles

```bash
# Lancer les tests
composer run test
php artisan test --compact

# Tester un fichier spécifique
php artisan test --compact tests/Feature/InscriptionTest.php

# Créer un nouveau test
php artisan make:test --pest FeatureName

# Vérifier la couverture
php artisan test --coverage

# Formatter le code avant commit
vendor/bin/pint --dirty
```

---

## Critères d'Acceptation

- [ ] Minimum 80 tests passants couvrant les fonctionnalités critiques
- [ ] Workflow inscription complet avec les 3 transitions (soumettre, valider, rejeter)
- [ ] Plus aucun modèle en double dans le projet
- [ ] Toutes les réponses d'erreur API suivent le format standardisé
- [ ] Tests automatisés pour chaque nouvelle fonctionnalité
- [ ] Code formaté avec Pint avant chaque commit
