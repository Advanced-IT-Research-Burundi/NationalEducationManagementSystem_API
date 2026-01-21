# Module École - NEMS
## Implémentation complète avec workflow de validation hiérarchique

---

## ✅ RÉSUMÉ DES MODIFICATIONS

### 📦 Fichiers Modifiés (Backend)

1. **`app/Models/School.php`** - Modèle École amélioré
   - Constantes de workflow (STATUS_BROUILLON, STATUS_EN_ATTENTE_VALIDATION, etc.)
   - Méthodes helper: `canSubmit()`, `canValidate()`, `canDeactivate()`
   - Scopes de filtrage: `draft()`, `pending()`, `active()`, `byType()`, `search()`
   - Accesseur `statut_label` pour affichage formaté

2. **`app/Policies/SchoolPolicy.php`** - Authorisation renforcée
   - Méthode `submit()` - autorisation pour soumission
   - Méthode `validate()` - autorisation hiérarchique pour validation
   - Méthode `deactivate()` - autorisation pour désactivation
   - Restriction d'édition des écoles actives

3. **`app/Http/Controllers/Api/SchoolController.php`** - Controller amélioré
   - `index()` - filtrage avancé (search, statut, type, niveau, hiérarchie)
   - `submit()` - workflow BROUILLON → EN_ATTENTE_VALIDATION
   - `validate()` - workflow EN_ATTENTE → ACTIVE (avec géolocalisation)
   - `deactivate()` - workflow ACTIVE → INACTIVE
   - Fix: utilisation des constantes de statut

4. **`routes/api.php`** - Routes workflow
   - `POST /api/schools/{id}/submit`
   - `POST /api/schools/{id}/validate`
   - `POST /api/schools/{id}/deactivate`

### 📄 Fichiers Créés

**FormRequests de Workflow:**
- `app/Http/Requests/SubmitSchoolRequest.php`
- `app/Http/Requests/ValidateSchoolRequest.php`
- `app/Http/Requests/DeactivateSchoolRequest.php`

**Documentation:**
- `TESTING.md` - Guide de tests manuel avec exemples cURL
- `tests/api/school-tests.json` - Collection Postman/Thunder Client

---

## 🎯 FONCTIONNALITÉS IMPLÉMENTÉES

### 1. Workflow de Validation
```
BROUILLON → EN_ATTENTE_VALIDATION → ACTIVE → INACTIVE
```

| Action | Endpoint | Transition |
|--------|----------|------------|
| Créer | `POST /api/schools` | → BROUILLON |
| Soumettre | `POST /api/schools/{id}/submit` | BROUILLON → EN_ATTENTE |
| Valider | `POST /api/schools/{id}/validate` | EN_ATTENTE → ACTIVE |
| Désactiver | `POST /api/schools/{id}/deactivate` | ACTIVE → INACTIVE |

### 2. Auto-localisation
Lors de la création, la hiérarchie administrative est **automatiquement remplie** depuis la Colline:
- `colline_id` (requis) → auto-remplit `zone_id`, `commune_id`, `province_id`, `pays_id`

### 3. Filtrage Avancé
```
GET /api/schools?statut=ACTIVE&type_ecole=PUBLIQUE&niveau=FONDAMENTAL&search=Gitega
```

Paramètres disponibles:
- `search` - cherche dans nom et code
- `statut` - BROUILLON, EN_ATTENTE_VALIDATION, ACTIVE, INACTIVE
- `type_ecole` - PUBLIQUE, PRIVEE, ECC, AUTRE
- `niveau` - FONDAMENTAL, POST_FONDAMENTAL, SECONDAIRE, SUPERIEUR
- `province_id`, `commune_id`, `zone_id` - filtrage hiérarchique
- `per_page` - pagination (défaut: 15)

### 4. Authorisation Hiérarchique

**Admin National:**
- Peut tout faire sur toutes les écoles

**Directeur Provincial:**
- Peut valider les écoles de **sa province** uniquement
- Peut désactiver les écoles de sa province

**Agent Communal:**
- Peut valider les écoles de **sa commune** uniquement

**Directeur d'École:**
- Voit uniquement **son école**

### 5. Sécurité

✅ **Policy-based authorization** - toutes les actions vérifiées  
✅ **Data Scope** - filtre automatique par AdminScope  
✅ **Validation métier** - géolocalisation obligatoire pour ACTIVE  
✅ **Audit trail** - created_by, validated_by, validated_at  
✅ **Soft deletes** - récupération possible  

---

## 🧪 COMMENT TESTER

### Option 1: Postman / Thunder Client
```bash
# Importer la collection
API_NEMS/tests/api/school-tests.json
```

### Option 2: cURL (voir TESTING.md)
```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@nems.bi", "password": "password"}'

# 2. Créer école
curl -X POST http://localhost:8000/api/schools \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "École Test",
    "code_ecole": "ET001",
    "type_ecole": "PUBLIQUE",
    "niveau": "FONDAMENTAL",
    "colline_id": 1,
    "latitude": -3.427,
    "longitude": 29.925
  }'

# 3. Soumettre
curl -X POST http://localhost:8000/api/schools/1/submit \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Valider
curl -X POST http://localhost:8000/api/schools/1/validate \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Option 3: Documentation complète
Voir: **`walkthrough.md`** pour guide détaillé avec tous les scénarios de test

---

## 📊 ENDPOINTS DISPONIBLES

| Méthode | URI | Description |
|---------|-----|-------------|
| GET | `/api/schools` | Liste avec filtres |
| POST | `/api/schools` | Créer (BROUILLON) |
| GET | `/api/schools/{id}` | Détails |
| PUT | `/api/schools/{id}` | Modifier |
| DELETE | `/api/schools/{id}` | Supprimer (soft) |
| POST | `/api/schools/{id}/submit` | Soumettre pour validation |
| POST | `/api/schools/{id}/validate` | Valider et activer |
| POST | `/api/schools/{id}/deactivate` | Désactiver |

---

## 🔐 PERMISSIONS REQUISES

| Action | Permission |
|--------|-----------|
| Lister | `view_data` |
| Créer | `create_data` ou `manage_schools` |
| Modifier | `update_data` ou `manage_schools` |
| Supprimer | `delete_data` ou `manage_schools` + Admin National |
| Soumettre | `update_data` |
| Valider | `validate_data` + scope hiérarchique |
| Désactiver | Admin National ou Directeur Provincial |

---

## 🚀 PROCHAINES ÉTAPES RECOMMANDÉES

1. **Tests automatisés** - Feature tests Laravel
2. **Frontend Vue.js** - Composants de gestion des écoles
3. **Notifications** - Alertes lors de soumission/validation
4. **Carte interactive** - Affichage des écoles sur Leaflet
5. **Export** - Export Excel/PDF avec filtres
6. **Logs** - Activity log des changements de statut
7. **Dashboard** - Statistiques par statut/province/type

---

## 📚 DOCUMENTATION

- **`walkthrough.md`** - Documentation complète avec exemples
- **`TESTING.md`** - Guide de tests manuel
- **`implementation_plan.md`** - Plan technique détaillé

---

**Développé pour NEMS - Advanced IT and Research Burundi**  
**Date:** 21 janvier 2026  
**Version:** 1.0.0
