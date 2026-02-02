# 🧪 Guide de Tests - Module École NEMS

## Prérequis

1. **Démarrer le serveur Laravel:**
```bash
cd API_NEMS
php artisan serve
```

2. **Créer un utilisateur Admin National** (si nécessaire):
```bash
php artisan tinker
```
```php
$user = User::create([
    'name' => 'Admin National',
    'email' => 'admin@nems.bi',
    'password' => bcrypt('password'),
    'admin_level' => 'PAYS',
    'admin_entity_id' => 1,
]);
$user->assignRole('Admin National');
$user->givePermissionTo(['view_data', 'create_data', 'update_data', 'delete_data', 'validate_data', 'manage_schools']);
```

---

## Tests Manuels avec cURL

### 1. Authentification

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@nems.bi",
    "password": "password"
  }'
```

Copiez le `token` de la réponse et utilisez-le dans les commandes suivantes.

---

### 2. Créer une École

```bash
curl -X POST http://localhost:8000/api/schools \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "École Primaire de Gitega",
    "code_ecole": "EPG001",
    "type_ecole": "PUBLIQUE",
    "niveau": "FONDAMENTAL",
    "colline_id": 1,
    "latitude": -3.427222,
    "longitude": 29.925278
  }'
```

**Résultat attendu:** Statut HTTP 201, école créée avec `statut: "BROUILLON"`

---

### 3. Lister les Écoles avec Filtres

```bash
# Toutes les écoles
curl http://localhost:8000/api/schools \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filtrer par statut
curl "http://localhost:8000/api/schools?statut=BROUILLON" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Recherche textuelle
curl "http://localhost:8000/api/schools?search=Gitega" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 4. Soumettre pour Validation

```bash
curl -X POST http://localhost:8000/api/schools/1/submit \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Résultat attendu:** `statut` passe de `BROUILLON` à `EN_ATTENTE_VALIDATION`

---

### 5. Valider l'École (Activer)

```bash
curl -X POST http://localhost:8000/api/schools/1/validate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "notes": "École validée après inspection"
  }'
```

**Résultat attendu:** 
- `statut` passe à `ACTIVE`
- `validated_by` et `validated_at` sont renseignés

---

### 6. Désactiver l'École

```bash
curl -X POST http://localhost:8000/api/schools/1/deactivate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "reason": "Fermeture temporaire pour travaux"
  }'
```

**Résultat attendu:** `statut` passe à `INACTIVE`

---

## Tests d'Erreurs

### ❌ Soumission sans champs requis

Créer une école sans `code_ecole`, puis tenter de la soumettre:

```bash
curl -X POST http://localhost:8000/api/schools/2/submit \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Attendu:** HTTP 422 - "Tous les champs obligatoires doivent être remplis"

---

### ❌ Validation sans géolocalisation

Créer une école sans `latitude`/`longitude`, la soumettre, puis tenter de la valider:

```bash
curl -X POST http://localhost:8000/api/schools/3/validate \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Attendu:** HTTP 422 - "La géolocalisation est obligatoire"

---

### ❌ Modification d'une école active par non-admin

```bash
curl -X PUT http://localhost:8000/api/schools/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer NON_ADMIN_TOKEN" \
  -d '{
    "name": "Tentative modification"
  }'
```

**Attendu:** HTTP 403 Forbidden

---

## Vérification du Data Scope

### Test avec Directeur Provincial

1. Créer un utilisateur Directeur Provincial:
```php
$user = User::create([
    'name' => 'Directeur Gitega',
    'email' => 'dir.gitega@nems.bi',
    'password' => bcrypt('password'),
    'admin_level' => 'PROVINCE',
    'admin_entity_id' => 1, // ID de la province Gitega
]);
$user->assignRole('Directeur Provincial');
$user->givePermissionTo(['view_data', 'update_data', 'validate_data']);
```

2. Se connecter avec ce compte et lister les écoles:
```bash
curl http://localhost:8000/api/schools \
  -H "Authorization: Bearer PROVINCIAL_TOKEN"
```

**Attendu:** Seulement les écoles de la province Gitega (`province_id = 1`)

3. Tenter d'accéder à une école d'une autre province:
```bash
curl http://localhost:8000/api/schools/999 \
  -H "Authorization: Bearer PROVINCIAL_TOKEN"
```

**Attendu:** HTTP 404 (filtrée par AdminScope)

---

## Collection Postman / Thunder Client

Importez le fichier `tests/api/school-tests.json` dans votre client REST favori pour avoir tous les tests prêts à l'emploi.

---

## Checklist de Vérification

- [ ] ✅ Création d'école avec auto-localisation depuis colline
- [ ] ✅ Statut initial = BROUILLON
- [ ] ✅ Soumission: BROUILLON → EN_ATTENTE_VALIDATION
- [ ] ✅ Validation: EN_ATTENTE → ACTIVE (avec géolocalisation)
- [ ] ✅ Désactivation: ACTIVE → INACTIVE (avec raison)
- [ ] ✅ Filtrage par statut, type, niveau
- [ ] ✅ Recherche par nom/code
- [ ] ✅ Data scope: utilisateur ne voit que ses écoles
- [ ] ✅ Validation hiérarchique: seul admin approprié peut valider
- [ ] ✅ Erreur si validation sans GPS
- [ ] ✅ Erreur si soumission sans champs requis
- [ ] ✅ Restriction édition école active

---

## Commandes Utiles

```bash
# Voir toutes les routes école
php artisan route:list --path=schools

# Nettoyer le cache
php artisan cache:clear && php artisan config:clear

# Voir les logs
tail -f storage/logs/laravel.log

# Vérifier la base de données
php artisan tinker
>>> School::count()
>>> School::with('colline')->first()
```
