# NEMS - Organisation des Modules

Ce document présente l'organisation modulaire du système NEMS basée sur les rôles et permissions définis.

---

## Vue d'ensemble des Modules

```
NEMS
├── 1. Module Core (Noyau)
│   ├── Authentication & Authorization
│   ├── User Management
│   └── Geographic Hierarchy
│
├── 2. Module Schools (Établissements)
│   ├── School Management
│   ├── School Workflow
│   └── School Directory
│
├── 3. Module Pedagogy (Pédagogie & Inspection)
│   ├── Inspection Management
│   ├── Quality Standards
│   ├── Pedagogical Support
│   └── Teacher Training
│
├── 4. Module Exams (Examens & Certification)
│   ├── Exam Planning
│   ├── Exam Centers
│   ├── Results Management
│   └── Certification & Diplomas
│
├── 5. Module Statistics (Planification & Statistiques)
│   ├── Data Collection
│   ├── KPIs & Dashboards
│   ├── Strategic Planning
│   └── M&E (Monitoring & Evaluation)
│
├── 6. Module Infrastructure
│   ├── Building Management
│   ├── Equipment Inventory
│   └── Maintenance
│
├── 7. Module HR (Ressources Humaines)
│   ├── Teacher Profiles
│   ├── Assignments & Transfers
│   ├── Career Management
│   └── Attendance
│
├── 8. Module System (Support Technique)
│   ├── System Administration
│   ├── Helpdesk & Support
│   └── Training Materials
│
└── 9. Module Partners (Partenaires Externes)
    ├── Donor Access
    ├── NGO Observer Access
    ├── Research Access
    └── External Audit
```

---

## 1. Module Core (Noyau)

### Description
Module fondamental du système gérant l'authentification, les autorisations et la hiérarchie administrative.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Authentication | Login, logout, sessions, 2FA | `users`, `personal_access_tokens` |
| Authorization | RBAC avec Spatie Permission | `roles`, `permissions`, `model_has_roles` |
| Geographic Hierarchy | Gestion PAYS→ÉCOLE | `pays`, `ministeres`, `provinces`, `communes`, `zones`, `collines` |

### Rôles concernés
- Admin National
- Admin Ministry

### API Endpoints
```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/refresh

GET    /api/pays
GET    /api/ministeres
GET    /api/provinces
GET    /api/communes
GET    /api/zones
GET    /api/collines
```

### Permissions
- `manage_users`
- `view_data`
- `manage_system_config`

---

## 2. Module Schools (Établissements Scolaires)

### Description
Gestion complète des établissements scolaires avec workflow de validation.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| School Management | CRUD des écoles | `schools` |
| School Workflow | Machine à états (BROUILLON→ACTIVE) | `schools.statut` |
| School Directory | Annuaire des écoles | `schools`, `school_types` |

### Workflow
```
BROUILLON ──submit()──► EN_ATTENTE_VALIDATION ──validate()──► ACTIVE ──deactivate()──► INACTIVE
   │                            │                                │
   │ Directeur École            │ Directeur Provincial           │ Admin National/Provincial
```

### Rôles concernés
- School Director (création, soumission)
- Director Provincial (validation)
- Admin National (supervision)

### API Endpoints
```
GET    /api/schools
POST   /api/schools
GET    /api/schools/{id}
PUT    /api/schools/{id}
DELETE /api/schools/{id}

POST   /api/schools/{id}/submit
POST   /api/schools/{id}/validate
POST   /api/schools/{id}/deactivate
```

### Permissions
- `manage_schools`
- `validate_data`
- `view_data`

---

## 3. Module Pedagogy (Pédagogie & Inspection)

### Description
Gestion des inspections pédagogiques, standards de qualité et accompagnement des enseignants.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Inspection Management | Planification et rapports d'inspection | `inspections`, `inspection_reports` |
| Quality Standards | Définition des standards éducatifs | `quality_standards`, `evaluation_criteria` |
| Pedagogical Support | Accompagnement terrain | `pedagogical_sessions`, `pedagogical_notes` |
| Teacher Training | Formations continues | `training_sessions`, `training_materials` |

### Rôles concernés
- **A1. Inspecteur Général** (National) - Coordination nationale
- **A2. Inspecteur Provincial** (Province) - Inspections terrain
- **A3. Conseiller Pédagogique** (Commune) - Accompagnement
- **A4. Enseignant Principal** (École) - Coordination discipline
- **A5. Coordinateur Zone Pédagogique** (Zone) - Coordination inter-écoles

### API Endpoints
```
# Inspections
GET    /api/inspections
POST   /api/inspections
GET    /api/inspections/{id}
PUT    /api/inspections/{id}
POST   /api/inspections/{id}/validate

# Rapports d'inspection
GET    /api/inspection-reports
POST   /api/inspection-reports
GET    /api/inspection-reports/{id}

# Standards de qualité
GET    /api/quality-standards
POST   /api/quality-standards
PUT    /api/quality-standards/{id}

# Sessions de formation
GET    /api/training-sessions
POST   /api/training-sessions
GET    /api/training-sessions/{id}/participants
```

### Permissions
- `create_quality_standards`
- `create_inspection_reports`
- `validate_inspection_reports`
- `view_inspection_reports`
- `recommend_training`
- `create_training_sessions`
- `create_pedagogical_reports`
- `create_pedagogical_notes`
- `view_teacher_profiles`
- `view_curriculum_data`
- `coordinate_training_sessions`
- `create_zone_reports`

---

## 4. Module Exams (Examens & Certification)

### Description
Gestion complète du cycle des examens nationaux et de la certification.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Exam Planning | Calendrier et organisation | `exam_sessions`, `exam_calendar` |
| Exam Centers | Gestion des centres d'examen | `exam_centers`, `exam_center_assignments` |
| Results Management | Collecte et validation des résultats | `exam_results`, `exam_grades` |
| Certification | Diplômes et certificats | `certificates`, `diploma_registry` |

### Rôles concernés
- **B1. Directeur des Examens** (National) - Supervision nationale
- **B2. Coordinateur Provincial Examens** (Province) - Organisation provinciale
- **B3. Gestionnaire de Certification** (National) - Émission diplômes

### Sécurité renforcée
- Authentification 2FA obligatoire
- Journalisation complète des accès
- Accès aux sujets limité dans le temps
- Environnement isolé pendant les sessions

### API Endpoints
```
# Calendrier examens
GET    /api/exams/calendar
POST   /api/exams/calendar
PUT    /api/exams/calendar/{id}

# Sessions d'examen
GET    /api/exams/sessions
POST   /api/exams/sessions
GET    /api/exams/sessions/{id}

# Centres d'examen
GET    /api/exams/centers
POST   /api/exams/centers
PUT    /api/exams/centers/{id}
POST   /api/exams/centers/{id}/assign-schools

# Résultats
GET    /api/exams/results
POST   /api/exams/results
POST   /api/exams/results/{id}/validate

# Certification
GET    /api/certificates
POST   /api/certificates
GET    /api/certificates/{id}/verify
GET    /api/diploma-registry
```

### Permissions
- `manage_exam_calendar`
- `manage_exam_subjects`
- `validate_exam_results`
- `issue_certificates`
- `verify_certificates`
- `manage_diploma_registry`
- `manage_exam_centers`
- `submit_exam_results`
- `view_exam_results`

---

## 5. Module Statistics (Planification & Statistiques)

### Description
Collecte de données, indicateurs de performance, tableaux de bord et planification stratégique.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Data Collection | Recensement scolaire annuel | `data_collections`, `collection_campaigns` |
| KPIs & Dashboards | Indicateurs et tableaux de bord | `kpis`, `kpi_values`, `dashboards` |
| Strategic Planning | Plans stratégiques 5-10 ans | `strategic_plans`, `plan_objectives` |
| M&E | Suivi-évaluation | `me_reports`, `alerts`, `performance_indicators` |

### Rôles concernés
- **C1. Directeur de la Planification** (National) - Vision stratégique
- **C2. Statisticien National** (National) - Données et indicateurs
- **C3. M&E Officer** (Province) - Suivi provincial
- **C4. Collecteur de Données** (Commune) - Terrain

### Indicateurs clés (KPIs)
- Taux de scolarisation (brut/net)
- Taux de redoublement
- Taux d'achèvement
- Ratio élèves/enseignant
- Ratio élèves/classe
- Taux de réussite aux examens

### API Endpoints
```
# Collecte de données
GET    /api/data-collections
POST   /api/data-collections
GET    /api/data-collections/{id}
POST   /api/data-collections/{id}/validate

# Campagnes de collecte
GET    /api/collection-campaigns
POST   /api/collection-campaigns
PUT    /api/collection-campaigns/{id}

# KPIs
GET    /api/kpis
POST   /api/kpis
GET    /api/kpis/{id}/values
POST   /api/kpis/{id}/calculate

# Tableaux de bord
GET    /api/dashboards
POST   /api/dashboards
GET    /api/dashboards/{id}/data

# Plans stratégiques
GET    /api/strategic-plans
POST   /api/strategic-plans
GET    /api/strategic-plans/{id}

# Rapports statistiques
GET    /api/statistical-reports
POST   /api/statistical-reports/generate
GET    /api/statistical-reports/{id}/export

# Alertes
GET    /api/alerts
POST   /api/alerts
PUT    /api/alerts/{id}/acknowledge
```

### Permissions
- `create_strategic_plans`
- `manage_kpis`
- `generate_national_reports`
- `create_statistical_reports`
- `manage_data_quality`
- `create_dashboards`
- `send_alerts`
- `flag_data_issues`
- `export_data`

---

## 6. Module Infrastructure

### Description
Gestion du patrimoine immobilier scolaire, des équipements et de la maintenance.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Building Management | Cartographie et état des bâtiments | `buildings`, `building_assessments` |
| Equipment Inventory | Inventaire du matériel | `equipment`, `equipment_movements` |
| Maintenance | Interventions et réparations | `maintenance_requests`, `maintenance_reports` |

### Rôles concernés
- **D1. Directeur des Infrastructures** (National) - Planification nationale
- **D2. Gestionnaire Provincial Équipements** (Province) - Distribution
- **D3. Technicien de Maintenance** (Commune) - Interventions terrain

### API Endpoints
```
# Bâtiments
GET    /api/infrastructure/buildings
POST   /api/infrastructure/buildings
GET    /api/infrastructure/buildings/{id}
PUT    /api/infrastructure/buildings/{id}

# Évaluations
GET    /api/infrastructure/assessments
POST   /api/infrastructure/assessments
GET    /api/infrastructure/assessments/{id}

# Équipements
GET    /api/equipment
POST   /api/equipment
PUT    /api/equipment/{id}
GET    /api/equipment/inventory

# Mouvements d'équipements
GET    /api/equipment/movements
POST   /api/equipment/movements

# Demandes de maintenance
GET    /api/maintenance/requests
POST   /api/maintenance/requests
PUT    /api/maintenance/requests/{id}
POST   /api/maintenance/requests/{id}/assign

# Rapports de maintenance
GET    /api/maintenance/reports
POST   /api/maintenance/reports
```

### Permissions
- `manage_infrastructure`
- `approve_construction_projects`
- `manage_equipment`
- `create_maintenance_reports`
- `update_infrastructure_status`
- `create_equipment_requests`
- `approve_equipment_distribution`

---

## 7. Module HR (Ressources Humaines)

### Description
Gestion des enseignants : profils, affectations, carrières et présences.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Teacher Profiles | Dossiers des enseignants | `teachers`, `teacher_qualifications` |
| Assignments | Affectations aux écoles | `teacher_assignments`, `assignment_history` |
| Career Management | Grades et avancements | `career_grades`, `promotions` |
| Attendance | Présences et absences | `attendance_records`, `leave_requests` |

### Rôles concernés
- **G1. Gestionnaire RH National** (National) - Fichier national
- **G2. Gestionnaire RH Provincial** (Province) - Gestion locale

### API Endpoints
```
# Profils enseignants
GET    /api/teachers
POST   /api/teachers
GET    /api/teachers/{id}
PUT    /api/teachers/{id}

# Qualifications
GET    /api/teachers/{id}/qualifications
POST   /api/teachers/{id}/qualifications

# Affectations
GET    /api/teacher-assignments
POST   /api/teacher-assignments
PUT    /api/teacher-assignments/{id}
GET    /api/teachers/{id}/assignment-history

# Demandes de mutation
GET    /api/transfer-requests
POST   /api/transfer-requests
PUT    /api/transfer-requests/{id}/approve
PUT    /api/transfer-requests/{id}/reject

# Carrières
GET    /api/teachers/{id}/career
POST   /api/teachers/{id}/promotions

# Présences
GET    /api/attendance
POST   /api/attendance
GET    /api/attendance/summary

# Congés
GET    /api/leave-requests
POST   /api/leave-requests
PUT    /api/leave-requests/{id}/approve
```

### Permissions
- `manage_teacher_profiles`
- `manage_teacher_assignments`
- `manage_teacher_careers`
- `manage_attendance`
- `request_teacher_transfers`
- `view_teacher_profiles`

---

## 8. Module System (Support Technique EMIS)

### Description
Administration technique du système, support utilisateurs et formation.

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| System Administration | Configuration et maintenance | `system_configs`, `backups`, `api_keys` |
| Helpdesk | Tickets de support | `support_tickets`, `ticket_responses` |
| Training Materials | Documentation et guides | `training_materials`, `user_guides` |

### Rôles concernés
- **E1. Administrateur Système EMIS** (National) - Administration technique
- **E2. Formateur EMIS** (Province) - Formation utilisateurs
- **E3. Support Helpdesk** (National) - Support utilisateurs

### API Endpoints
```
# Configuration système
GET    /api/system/config
PUT    /api/system/config
GET    /api/system/health

# Sauvegardes
GET    /api/system/backups
POST   /api/system/backups
POST   /api/system/backups/{id}/restore

# Clés API
GET    /api/system/api-keys
POST   /api/system/api-keys
DELETE /api/system/api-keys/{id}

# Logs d'audit
GET    /api/system/audit-logs
GET    /api/system/audit-logs/{id}

# Tickets de support
GET    /api/support/tickets
POST   /api/support/tickets
GET    /api/support/tickets/{id}
PUT    /api/support/tickets/{id}
POST   /api/support/tickets/{id}/respond
PUT    /api/support/tickets/{id}/close

# Matériel de formation
GET    /api/training-materials
POST   /api/training-materials
GET    /api/training-materials/{id}
PUT    /api/training-materials/{id}

# Gestion utilisateurs (reset passwords)
POST   /api/users/{id}/reset-password
GET    /api/users/{id}/activity-logs
```

### Permissions
- `manage_system_config`
- `view_audit_logs`
- `manage_backups`
- `manage_api_keys`
- `create_training_materials`
- `manage_support_tickets`
- `reset_user_passwords`
- `view_user_activity_logs`
- `view_user_profiles`

---

## 9. Module Partners (Partenaires Externes)

### Description
Accès contrôlé pour les partenaires externes (PTF, ONG, chercheurs, auditeurs).

### Composants

| Composant | Description | Tables principales |
|-----------|-------------|-------------------|
| Donor Access | Accès PTF aux indicateurs projets | `partner_projects`, `project_indicators` |
| NGO Access | Accès ONG aux données publiques | `public_statistics` |
| Research Access | Accès chercheurs données anonymisées | `research_requests`, `anonymized_exports` |
| External Audit | Accès temporaire pour audits | `audit_missions`, `audit_access_logs` |

### Rôles concernés
- **F1. Partenaire PTF** (Externe) - Lecture seule projets
- **F2. Observateur ONG** (Externe) - Données publiques
- **F3. Chercheur** (Externe) - Données anonymisées
- **F4. Auditeur Externe** (Externe) - Accès audit temporaire

### Restrictions
- **LECTURE SEULE** pour tous les rôles externes
- Aucun accès aux données nominatives
- Accès temporaire et audité
- Validation Admin National obligatoire

### API Endpoints
```
# Indicateurs projets (PTF)
GET    /api/partners/projects
GET    /api/partners/projects/{id}/indicators
GET    /api/partners/projects/{id}/reports

# Statistiques publiques (ONG)
GET    /api/public/statistics
GET    /api/public/statistics/education-indicators
GET    /api/public/statistics/school-coverage

# Accès recherche (Chercheurs)
POST   /api/research/requests
GET    /api/research/requests/{id}
GET    /api/research/anonymized-data
POST   /api/research/export

# Accès audit (Auditeurs)
GET    /api/audit/data
GET    /api/audit/logs
GET    /api/audit/system-config
POST   /api/audit/reports
```

### Permissions
- `view_project_indicators`
- `view_public_statistics`
- `export_anonymized_data`
- `view_system_config` (audit only)

---

## Matrice Modules × Rôles

| Module | Admin Nat. | Admin Min. | Dir. Prov. | Off. Com. | Sup. Zone | Dir. École | Teacher | Staff |
|--------|:----------:|:----------:|:----------:|:---------:|:---------:|:----------:|:-------:|:-----:|
| Core | ✓✓ | ✓✓ | ✓ | ✓ | ✓ | ✓ | ○ | ○ |
| Schools | ✓✓ | ✓ | ✓✓ | ✓ | ✓ | ✓✓ | ○ | ○ |
| Pedagogy | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |
| Exams | ✓✓ | ✓ | ✓ | ○ | ○ | ○ | ○ | ○ |
| Statistics | ✓✓ | ✓✓ | ✓ | ✓ | ○ | ○ | ○ | ○ |
| Infrastructure | ✓✓ | ✓ | ✓ | ✓ | ○ | ✓ | ○ | ○ |
| HR | ✓✓ | ✓ | ✓ | ○ | ○ | ✓ | ○ | ○ |
| System | ✓✓ | ○ | ○ | ○ | ○ | ○ | ○ | ○ |
| Partners | ✓ | ○ | ○ | ○ | ○ | ○ | ○ | ○ |

**Légende:** ✓✓ = Accès complet | ✓ = Accès limité | ○ = Lecture seule ou pas d'accès

---

## Priorités d'Implémentation

### Phase 1 - Fondations (Sprint 1-3)
1. **Module Core** - Authentification, RBAC, hiérarchie géographique
2. **Module Schools** - Gestion écoles + workflow

### Phase 2 - Contrôle Qualité (Sprint 4-6)
3. **Module Pedagogy** - Inspections, accompagnement pédagogique
4. **Module Statistics** - Collecte données, indicateurs de base

### Phase 3 - Certification (Sprint 7-9)
5. **Module Exams** - Calendrier, centres, résultats, diplômes
6. **Module HR** - Profils enseignants, affectations

### Phase 4 - Support (Sprint 10-12)
7. **Module Infrastructure** - Bâtiments, équipements, maintenance
8. **Module System** - Administration, helpdesk

### Phase 5 - Ouverture (Sprint 13-14)
9. **Module Partners** - Accès partenaires externes

---

## Structure de Fichiers Proposée

```
API_NEMS/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Auth/
│   │   │   │   ├── Core/
│   │   │   │   ├── Schools/
│   │   │   │   ├── Pedagogy/
│   │   │   │   ├── Exams/
│   │   │   │   ├── Statistics/
│   │   │   │   ├── Infrastructure/
│   │   │   │   ├── HR/
│   │   │   │   ├── System/
│   │   │   │   └── Partners/
│   │   │   └── ...
│   │   └── Requests/
│   │       ├── Schools/
│   │       ├── Pedagogy/
│   │       ├── Exams/
│   │       └── ...
│   │
│   ├── Models/
│   │   ├── Core/
│   │   ├── Schools/
│   │   ├── Pedagogy/
│   │   ├── Exams/
│   │   ├── Statistics/
│   │   ├── Infrastructure/
│   │   ├── HR/
│   │   └── System/
│   │
│   ├── Policies/
│   │   ├── SchoolPolicy.php
│   │   ├── InspectionPolicy.php
│   │   ├── ExamPolicy.php
│   │   └── ...
│   │
│   └── Services/
│       ├── SchoolWorkflowService.php
│       ├── StatisticsService.php
│       ├── CertificationService.php
│       └── ...
│
├── database/
│   ├── migrations/
│   │   ├── 0001_create_core_tables.php
│   │   ├── 0002_create_schools_tables.php
│   │   ├── 0003_create_pedagogy_tables.php
│   │   └── ...
│   │
│   └── seeders/
│       ├── PermissionSeeder.php
│       ├── RoleSeeder.php
│       └── ...
│
└── routes/
    └── api.php
```

---

## Conclusion

Cette organisation modulaire permet:

1. **Séparation claire des responsabilités** - Chaque module a un périmètre défini
2. **Scalabilité** - Modules indépendants pouvant évoluer séparément
3. **Sécurité** - Permissions granulaires par module et rôle
4. **Maintenabilité** - Code organisé et facilement navigable
5. **Déploiement progressif** - Phases d'implémentation claires

---

*Document généré pour: NEMS (National Education Management System)*
*Basé sur: role.txt v2.0*
*Date: Janvier 2026*
