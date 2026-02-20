# ESTIME - Développeur Infrastructure & Modules Secondaires

## Rôle dans le projet

Responsable des **modules secondaires backend**, de l'**infrastructure technique**, de la **documentation API** et du **déploiement**. ESTIME complète les fonctionnalités manquantes et prépare le projet pour la mise en production.

---

## Tâches Assignées

### 1. Module Infrastructure - Implémentation Complète

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Implémenter le module Infrastructure pour gérer les bâtiments scolaires, équipements et maintenance. Actuellement seuls les controllers existent sans logique. |
| Livrable | Module fonctionnel avec inventaire, suivi maintenance, alertes |

**Sous-tâches :**
- [ ] Créer les migrations : `batiments`, `salles`, `equipements`, `maintenances`, `inventaires`
- [ ] Créer les modèles : `Batiment`, `Salle`, `Equipement`, `Maintenance`, `Inventaire`
- [ ] Implémenter `BatimentController` : CRUD, état, superficie, capacité
- [ ] Implémenter `SalleController` : type (classe, labo, bureau), capacité, état
- [ ] Implémenter `EquipementController` : inventaire, état, localisation
- [ ] Implémenter `MaintenanceController` : demandes, interventions, suivi
- [ ] Créer les Form Requests et Policies
- [ ] Ajouter les statistiques infrastructure par école/zone

**Modèle de données suggéré :**
```
Batiment: id, school_id, nom, type, annee_construction, superficie,
          nombre_etages, etat (bon, moyen, mauvais, dangereux)
Salle: id, batiment_id, numero, type (classe, laboratoire, bureau, sanitaire),
       capacite, superficie, etat
Equipement: id, salle_id, type, marque, modele, numero_serie,
            date_acquisition, etat, valeur
Maintenance: id, equipement_id/batiment_id, type (preventive, corrective),
             description, date_demande, date_intervention, cout, statut
```

---

### 2. Module HR - Relations et Policies

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Compléter le module RH avec les relations manquantes, les policies d'autorisation et les workflows de gestion du personnel. |
| Livrable | Module RH fonctionnel avec gestion carrière, présences, congés |

**Sous-tâches :**
- [ ] Créer les migrations : `carrieres`, `presences`, `conges`, `evaluations`
- [ ] Créer les modèles avec relations vers Enseignant et User
- [ ] Implémenter `CarriereController` : historique postes, promotions, mutations
- [ ] Implémenter `PresenceController` : pointage, absences, justificatifs
- [ ] Implémenter `CongeController` : demandes, approbation, solde
- [ ] Implémenter `EvaluationController` : évaluations annuelles, objectifs
- [ ] Créer les Policies : qui peut approuver les congés, voir les évaluations
- [ ] Ajouter les rapports RH : taux présence, congés par période

**Modèle de données suggéré :**
```
Carriere: id, enseignant_id, poste, school_id, date_debut, date_fin,
          motif_fin (mutation, promotion, retraite, demission)
Presence: id, enseignant_id, date, heure_arrivee, heure_depart,
          statut (present, absent_justifie, absent_non_justifie)
Conge: id, enseignant_id, type (annuel, maladie, maternite),
       date_debut, date_fin, statut (demande, approuve, refuse), motif
Evaluation: id, enseignant_id, annee, evaluateur_id, note,
            points_forts, points_ameliorer, objectifs
```

---

### 3. Module Système - Administration

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Implémenter les fonctionnalités système : helpdesk, gestion des utilisateurs avancée, paramètres système, sauvegardes. |
| Livrable | Interface d'administration système complète |

**Sous-tâches :**
- [ ] Créer les migrations : `tickets_support`, `parametres_systeme`, `sauvegardes`
- [ ] Implémenter `HelpdeskController` : création tickets, assignation, résolution
- [ ] Implémenter `ParametresController` : configuration application
- [ ] Implémenter `BackupController` : déclenchement, liste, restauration
- [ ] Ajouter la gestion avancée utilisateurs : reset password, désactivation
- [ ] Créer les logs système consultables
- [ ] Ajouter les notifications système (maintenance planifiée)

---

### 4. Module Partenaires

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Implémenter le module Partenaires pour gérer les PTF (Partenaires Techniques et Financiers), ONG, et partenaires de recherche. |
| Livrable | Module de gestion des partenariats fonctionnel |

**Sous-tâches :**
- [ ] Créer les migrations : `partenaires`, `projets_partenariat`, `financements`
- [ ] Créer les modèles : `Partenaire`, `ProjetPartenariat`, `Financement`
- [ ] Implémenter `PartenaireController` : CRUD, type (PTF, ONG, université)
- [ ] Implémenter `ProjetController` : projets en cours, écoles bénéficiaires
- [ ] Implémenter `FinancementController` : suivi budgets, décaissements
- [ ] Ajouter les rapports : financements par partenaire, par zone

---

### 5. Pagination Globale

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Implémenter la pagination sur tous les endpoints de liste pour éviter les problèmes de performance avec de grands volumes de données. |
| Livrable | Tous les endpoints de liste retournent des données paginées |

**Sous-tâches :**
- [ ] Auditer tous les contrôleurs qui retournent des listes
- [ ] Remplacer `->get()` par `->paginate()`
- [ ] Standardiser les paramètres : `?page=1&per_page=20`
- [ ] Ajouter les métadonnées de pagination dans les réponses
- [ ] Documenter le format de réponse paginée
- [ ] Tester avec de grands volumes de données

**Format de réponse standard :**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 195
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

### 6. Fonctionnalités d'Export

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Implémenter l'export des données en CSV et Excel pour les rapports et analyses. |
| Livrable | Endpoints d'export fonctionnels pour les entités principales |

**Sous-tâches :**
- [ ] Installer et configurer `maatwebsite/excel`
- [ ] Créer les classes Export : `ElevesExport`, `EcolesExport`, `InscriptionsExport`
- [ ] Implémenter les endpoints : `GET /api/eleves/export`, etc.
- [ ] Ajouter les filtres d'export (période, zone, statut)
- [ ] Gérer les exports asynchrones pour les gros volumes (jobs)
- [ ] Ajouter l'export des statistiques/rapports
- [ ] Limiter les exports selon les permissions utilisateur

---

### 7. Documentation API (OpenAPI/Swagger)

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Créer une documentation API interactive avec Swagger/OpenAPI pour faciliter l'intégration et les tests. |
| Livrable | Documentation API accessible à `/api/documentation` |

**Sous-tâches :**
- [ ] Installer `darkaonline/l5-swagger`
- [ ] Ajouter les annotations OpenAPI aux contrôleurs
- [ ] Documenter tous les endpoints avec paramètres et réponses
- [ ] Ajouter les exemples de requêtes/réponses
- [ ] Documenter les codes d'erreur
- [ ] Configurer l'authentification dans Swagger UI
- [ ] Générer et publier la documentation

---

### 8. Guide de Déploiement

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Rédiger un guide complet de déploiement pour les environnements de staging et production. |
| Livrable | Fichier `DEPLOYMENT.md` avec instructions détaillées |

**Sous-tâches :**
- [ ] Documenter les prérequis serveur (PHP, MySQL, Redis, etc.)
- [ ] Écrire les étapes d'installation pas à pas
- [ ] Configurer les variables d'environnement (.env.example complet)
- [ ] Documenter la configuration Nginx/Apache
- [ ] Ajouter les scripts de déploiement (deploy.sh)
- [ ] Documenter la procédure de backup/restore
- [ ] Ajouter les commandes de maintenance (artisan)
- [ ] Documenter le monitoring et les logs

---

### 9. Encryption des Données Sensibles

| Priorité | **BASSE** |
|----------|----------|
| Description | Implémenter le chiffrement des données sensibles (photos, informations handicap, données personnelles). |
| Livrable | Données sensibles chiffrées en base de données |

**Sous-tâches :**
- [ ] Identifier les champs sensibles dans chaque modèle
- [ ] Utiliser les casts `encrypted` de Laravel
- [ ] Migrer les données existantes (si applicable)
- [ ] Tester le chiffrement/déchiffrement
- [ ] Documenter les champs chiffrés

---

## Fichiers Principaux à Créer/Modifier

```
API_NEMS/
├── database/migrations/
│   ├── create_batiments_table.php (à créer)
│   ├── create_salles_table.php (à créer)
│   ├── create_equipements_table.php (à créer)
│   ├── create_maintenances_table.php (à créer)
│   ├── create_carrieres_table.php (à créer)
│   ├── create_presences_table.php (à créer)
│   ├── create_conges_table.php (à créer)
│   ├── create_partenaires_table.php (à créer)
│   └── create_tickets_support_table.php (à créer)
├── app/Models/
│   ├── Batiment.php, Salle.php, Equipement.php (à créer)
│   ├── Carriere.php, Presence.php, Conge.php (à créer)
│   └── Partenaire.php, ProjetPartenariat.php (à créer)
├── app/Http/Controllers/Api/
│   ├── Infrastructure/ (à compléter)
│   ├── HR/ (à compléter)
│   ├── System/ (à compléter)
│   └── Partners/ (à compléter)
├── app/Exports/
│   ├── ElevesExport.php (à créer)
│   ├── EcolesExport.php (à créer)
│   └── InscriptionsExport.php (à créer)
├── docs/
│   ├── DEPLOYMENT.md (à créer)
│   └── API.md (à créer ou générer)
└── storage/api-docs/
    └── api-docs.json (généré par Swagger)
```

---

## Dépendances à Installer

```bash
# Export Excel
composer require maatwebsite/excel

# Documentation API
composer require darkaonline/l5-swagger

# Publier les configs
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

---

## Commandes Utiles

```bash
# Créer une migration
php artisan make:migration create_batiments_table --no-interaction

# Créer un Export
php artisan make:export ElevesExport --model=Eleve

# Générer la doc Swagger
php artisan l5-swagger:generate

# Tester un export
php artisan tinker
>>> Excel::download(new ElevesExport, 'eleves.xlsx');

# Vérifier les routes définies
php artisan route:list --path=api
```

---

## Critères d'Acceptation

- [ ] Module Infrastructure fonctionnel avec CRUD bâtiments, salles, équipements
- [ ] Module HR fonctionnel avec carrières, présences, congés
- [ ] Module Système avec helpdesk et paramètres
- [ ] Module Partenaires avec gestion projets et financements
- [ ] Tous les endpoints de liste utilisent la pagination
- [ ] Export CSV/Excel fonctionnel pour élèves, écoles, inscriptions
- [ ] Documentation API Swagger accessible et à jour
- [ ] Guide de déploiement complet et testé
- [ ] Données sensibles chiffrées en base
- [ ] Tests écrits pour chaque nouveau module (coordination avec RUBEN)
