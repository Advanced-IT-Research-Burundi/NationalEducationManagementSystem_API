# KANTORE - Développeur Modules Métier Backend

## Rôle dans le projet

Responsable de l'**implémentation des modules métier** du système : Pédagogie, Examens, Statistiques. KANTORE transforme les controllers stub existants en fonctionnalités complètes avec logique métier, et intègre le système d'audit.

---

## Tâches Assignées

### 1. Module Pédagogie - Implémentation Complète

| Priorité    | **HAUTE**                                                                                                                                                          |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Description | Implémenter le module Pédagogie qui gère les inspections scolaires, les standards de qualité et les formations. Les controllers existent mais sans logique métier. |
| Livrable    | Module fonctionnel avec CRUD complet, workflows de validation, rapports d'inspection                                                                               |

**Sous-tâches :**
- [ ] Créer les migrations pour les tables : `inspections`, `standards_qualite`, `formations`, `participants_formation`
- [ ] Créer les modèles avec relations : `Inspection`, `StandardQualite`, `Formation`
- [ ] Implémenter `InspectionController` : planification, exécution, rapport, validation
- [ ] Implémenter `StandardQualiteController` : définition, évaluation, scoring
- [ ] Implémenter `FormationController` : création, inscription, suivi, certification
- [ ] Créer les Form Requests de validation
- [ ] Créer les Policies d'autorisation (qui peut inspecter quoi)
- [ ] Ajouter les routes API dans `routes/api/pedagogie.php`

**Modèle de données suggéré :**
```
Inspection: id, school_id, inspecteur_id, date_prevue, date_realisation,
            type (reguliere, inopinee, thematique), statut, rapport, note_globale
StandardQualite: id, code, libelle, description, criteres (JSON), poids
Formation: id, titre, description, date_debut, date_fin, formateur_id,
           lieu, capacite, statut (planifiee, en_cours, terminee)
```

---

### 2. Module Examens - Implémentation Complète

| Priorité    | **HAUTE**                                                                                                                                                |
| ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Description | Implémenter le module Examens pour la planification des examens nationaux, la gestion des centres d'examen, la saisie des résultats et la certification. |
| Livrable    | Module fonctionnel avec gestion complète du cycle d'examen                                                                                               |

**Sous-tâches :**
- [ ] Créer les migrations : `examens`, `sessions_examen`, `centres_examen`, `inscriptions_examen`, `resultats`, `certificats`
- [ ] Créer les modèles : `Examen`, `SessionExamen`, `CentreExamen`, `InscriptionExamen`, `Resultat`, `Certificat`
- [ ] Implémenter `ExamenController` : création, planification, publication
- [ ] Implémenter `SessionExamenController` : ouverture, gestion, clôture
- [ ] Implémenter `CentreExamenController` : affectation écoles, capacité, supervision
- [ ] Implémenter `InscriptionExamenController` : inscription élèves, validation, convocations
- [ ] Implémenter `ResultatController` : saisie notes, calcul moyennes, délibération
- [ ] Implémenter `CertificatController` : génération, impression, vérification authenticité
- [ ] Créer les Form Requests et Policies

**Modèle de données suggéré :**
```
Examen: id, code, libelle, niveau_id, annee_scolaire_id, type (national, provincial)
SessionExamen: id, examen_id, date_debut, date_fin, statut
CentreExamen: id, school_id, session_id, capacite, responsable_id
InscriptionExamen: id, eleve_id, session_id, centre_id, numero_anonymat, statut
Resultat: id, inscription_examen_id, matiere, note, mention, deliberation
Certificat: id, resultat_id, numero_unique, date_emission, qr_code
```

---

### 3. Module Statistiques - Logique de Calcul

| Priorité    | **HAUTE**                                                                                                                                           |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Description | Implémenter la logique de calcul des statistiques et KPIs du tableau de bord. Les endpoints existent mais retournent des données vides ou factices. |
| Livrable    | Statistiques réelles calculées dynamiquement avec mise en cache                                                                                     |

**Sous-tâches :**
- [ ] Implémenter les statistiques globales : nombre écoles, élèves, enseignants par niveau admin
- [ ] Implémenter les statistiques d'inscription : taux inscription, répartition par genre, par niveau
- [ ] Implémenter les statistiques de performance : taux réussite, moyennes, évolution
- [ ] Implémenter les KPIs nationaux : ratio élève/enseignant, taux scolarisation
- [ ] Ajouter le cache Redis/File pour les calculs lourds
- [ ] Créer les endpoints de dashboard par niveau (national, provincial, communal, école)
- [ ] Implémenter les filtres par période, par zone géographique

**Statistiques à calculer :**
```php
// Exemple de KPIs
- total_ecoles, total_eleves, total_enseignants
- repartition_genre (garcons/filles par niveau)
- taux_inscription (inscrits/population_scolaire)
- ratio_eleve_enseignant
- evolution_effectifs (comparaison années)
- taux_reussite_examens
- repartition_geographique
```

---

### 4. Intégration Audit Logging (Spatie Activity Log)

| Priorité    | **MOYENNE**                                                                                                                                                                 |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Description | Intégrer complètement spatie/laravel-activitylog pour tracer toutes les actions importantes sur les entités critiques. Le package est installé mais pas activement utilisé. |
| Livrable    | Logging automatique sur tous les modèles critiques, interface de consultation des logs                                                                                      |

**Sous-tâches :**
- [ ] Ajouter le trait `LogsActivity` aux modèles : School, Eleve, InscriptionEleve, User
- [ ] Configurer les attributs à logger par modèle
- [ ] Créer les événements personnalisés pour les workflows (soumission, validation, rejet)
- [ ] Implémenter `AuditController` pour consulter les logs
- [ ] Ajouter les filtres : par utilisateur, par entité, par période, par action
- [ ] Créer une vue frontend pour l'historique d'activité (coordination avec BRICE)

**Configuration exemple :**
```php
// Dans le modèle
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

protected static $logAttributes = ['*'];
protected static $logOnlyDirty = true;
protected static $logName = 'eleves';
```

---

### 5. Module Collecte de Données (Data Collection)

| Priorité    | **MOYENNE**                                                                                |
| ----------- | ------------------------------------------------------------------------------------------ |
| Description | Implémenter le système de collecte de données pour les enquêtes et recensements scolaires. |
| Livrable    | Système de formulaires dynamiques avec soumission et validation hiérarchique               |

**Sous-tâches :**
- [ ] Créer les migrations : `formulaires_collecte`, `campagnes_collecte`, `reponses_collecte`
- [ ] Créer les modèles avec workflow de validation
- [ ] Implémenter la création de formulaires dynamiques (champs JSON)
- [ ] Implémenter la soumission par les écoles
- [ ] Implémenter la validation hiérarchique (Zone → Commune → Province)
- [ ] Ajouter les rapports de taux de réponse
- [ ] Implémenter l'export des données collectées

---

### 6. Optimisation des Requêtes (Eager Loading)

| Priorité    | **BASSE**                                                                                                      |
| ----------- | -------------------------------------------------------------------------------------------------------------- |
| Description | Identifier et corriger les problèmes N+1 dans les contrôleurs existants en ajoutant l'eager loading approprié. |
| Livrable    | Requêtes optimisées avec eager loading, temps de réponse améliorés                                             |

**Sous-tâches :**
- [ ] Auditer les contrôleurs pour identifier les N+1 queries
- [ ] Ajouter `with()` pour les relations fréquemment utilisées
- [ ] Utiliser `withCount()` pour les statistiques de comptage
- [ ] Ajouter des index de base de données si nécessaire
- [ ] Mesurer et documenter les améliorations de performance

---

## Fichiers Principaux à Créer/Modifier

```
API_NEMS/
├── database/migrations/
│   ├── create_inspections_table.php (à créer)
│   ├── create_standards_qualite_table.php (à créer)
│   ├── create_formations_table.php (à créer)
│   ├── create_examens_table.php (à créer)
│   ├── create_sessions_examen_table.php (à créer)
│   ├── create_centres_examen_table.php (à créer)
│   ├── create_resultats_table.php (à créer)
│   └── create_certificats_table.php (à créer)
├── app/Models/
│   ├── Inspection.php (à créer)
│   ├── StandardQualite.php (à créer)
│   ├── Formation.php (à créer)
│   ├── Examen.php (à créer)
│   ├── SessionExamen.php (à créer)
│   ├── CentreExamen.php (à créer)
│   ├── Resultat.php (à créer)
│   └── Certificat.php (à créer)
├── app/Http/Controllers/Api/
│   ├── Pedagogie/ (à compléter)
│   ├── Exams/ (à compléter)
│   └── Statistics/ (à compléter)
├── app/Services/
│   └── StatisticsService.php (à créer)
└── routes/api/
    ├── pedagogie.php (à compléter)
    ├── exams.php (à compléter)
    └── statistics.php (à compléter)
```

---

## Commandes Utiles

```bash
# Créer une migration
php artisan make:migration create_inspections_table --no-interaction

# Créer un modèle avec migration et controller
php artisan make:model Inspection -mc --no-interaction

# Exécuter les migrations
php artisan migrate

# Créer un service
php artisan make:class Services/StatisticsService

# Vérifier les N+1 queries en dev
# Ajouter dans .env: TELESCOPE_ENABLED=true
```

---

## Critères d'Acceptation

- [ ] Module Pédagogie fonctionnel avec inspections, standards et formations
- [ ] Module Examens fonctionnel avec cycle complet inscription → résultats → certification
- [ ] Dashboard statistiques affichant des données réelles et calculées
- [ ] Audit logging actif sur tous les modèles critiques
- [ ] Interface de consultation des logs d'audit
- [ ] Aucun problème N+1 détecté dans les endpoints principaux
- [ ] Tests écrits pour chaque nouveau module (coordination avec RUBEN)
