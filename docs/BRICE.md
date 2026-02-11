# BRICE - Développeur Frontend & UI/UX

## Rôle dans le projet

Responsable du **frontend Vue.js**, de l'**expérience utilisateur** et de l'**interface graphique**. BRICE s'assure que l'application est intuitive, responsive, accessible et que toutes les fonctionnalités backend sont correctement intégrées côté client.

---

## Tâches Assignées

### 1. Amélioration Page Statistiques (Dashboard)

| Priorité | **HAUTE** |
|----------|-----------|
| Description | La page StatisticsPage existe mais la logique des graphiques n'est pas définie. Intégrer les données réelles de l'API et créer des visualisations pertinentes. |
| Livrable | Dashboard complet avec graphiques interactifs, filtres, et données temps réel |

**Sous-tâches :**
- [ ] Intégrer une librairie de graphiques (Chart.js ou ApexCharts)
- [ ] Créer le composant `StatCard` pour les KPIs principaux
- [ ] Créer le graphique d'évolution des effectifs (ligne)
- [ ] Créer le graphique de répartition par genre (camembert)
- [ ] Créer le graphique de répartition géographique (barres)
- [ ] Ajouter les filtres : période, province, commune, niveau scolaire
- [ ] Connecter aux endpoints API `/api/statistics/*`
- [ ] Ajouter le chargement et états d'erreur
- [ ] Rendre le dashboard responsive (mobile/tablet)

---

### 2. Gestion des Erreurs Frontend

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Implémenter une gestion globale des erreurs API avec affichage utilisateur approprié (toasts, modales, messages inline). |
| Livrable | Système de gestion d'erreurs cohérent sur toute l'application |

**Sous-tâches :**
- [ ] Créer un intercepteur Axios global pour les erreurs
- [ ] Implémenter le composant `Toast` pour les notifications
- [ ] Gérer les erreurs 401 (redirection login)
- [ ] Gérer les erreurs 403 (accès refusé - message approprié)
- [ ] Gérer les erreurs 422 (validation - affichage inline)
- [ ] Gérer les erreurs 500 (erreur serveur - message générique)
- [ ] Gérer les erreurs réseau (offline mode)
- [ ] Ajouter un système de retry pour les requêtes échouées
- [ ] Logger les erreurs côté client (optionnel: Sentry)

**Exemple d'intercepteur :**
```javascript
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      store.dispatch('auth/logout');
      router.push('/login');
    }
    // ... autres cas
    return Promise.reject(error);
  }
);
```

---

### 3. Contrôle d'Accès UI (RBAC Frontend)

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Implémenter le rendu conditionnel des éléments UI basé sur les rôles et permissions de l'utilisateur connecté. |
| Livrable | Directive `v-can` et composant `CanAccess` pour le contrôle d'accès |

**Sous-tâches :**
- [ ] Créer la directive `v-can="permission"` pour affichage conditionnel
- [ ] Créer le composant `<CanAccess :permission="...">` wrapper
- [ ] Stocker les permissions utilisateur dans Vuex après login
- [ ] Masquer les menus/boutons selon les permissions
- [ ] Désactiver les actions non autorisées (boutons grisés)
- [ ] Gérer les niveaux administratifs (PAYS, PROVINCE, COMMUNE, ZONE, SCHOOL)
- [ ] Appliquer sur : sidebar, boutons d'action, formulaires
- [ ] Documenter l'utilisation des directives

**Exemple d'utilisation :**
```vue
<button v-can="'create_data'" @click="createSchool">
  Créer une école
</button>

<CanAccess permission="validate_data">
  <button @click="validateInscription">Valider</button>
</CanAccess>
```

---

### 4. Page Inscriptions - Améliorations

| Priorité | **HAUTE** |
|----------|-----------|
| Description | Compléter la page InscriptionCreatePage avec validation du statut de campagne, workflow complet et meilleure UX. |
| Livrable | Formulaire d'inscription complet avec toutes les validations métier |

**Sous-tâches :**
- [ ] Vérifier que la campagne est active avant d'autoriser l'inscription
- [ ] Ajouter la recherche d'élève existant (autocomplete)
- [ ] Implémenter le formulaire de création nouvel élève intégré
- [ ] Ajouter la sélection de classe avec places disponibles
- [ ] Afficher les informations de l'inscription précédente si réinscription
- [ ] Implémenter les boutons de workflow : Soumettre, Valider, Rejeter
- [ ] Ajouter le modal de rejet avec motif obligatoire
- [ ] Afficher l'historique de l'inscription (timeline)

---

### 5. Page Enseignants - CRUD Complet

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Compléter l'interface de gestion des enseignants avec toutes les opérations CRUD et les affectations. |
| Livrable | Interface enseignants complète avec liste, création, édition, affectation |

**Sous-tâches :**
- [ ] Créer le tableau des enseignants avec pagination
- [ ] Ajouter la recherche et les filtres (nom, matricule, statut)
- [ ] Créer le formulaire de création enseignant
- [ ] Créer le formulaire d'édition enseignant
- [ ] Implémenter la vue détail enseignant
- [ ] Ajouter l'affectation aux classes (modal ou page dédiée)
- [ ] Afficher l'historique des affectations
- [ ] Ajouter les actions : activer, désactiver, transférer

---

### 6. Dark Mode

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Implémenter le support du mode sombre avec Tailwind CSS 4 et permettre à l'utilisateur de basculer. |
| Livrable | Toggle dark mode fonctionnel avec persistence du choix |

**Sous-tâches :**
- [ ] Configurer Tailwind CSS 4 pour le dark mode (`@theme` directive)
- [ ] Créer le composant `ThemeToggle` pour basculer
- [ ] Définir les couleurs dark mode dans les variables CSS
- [ ] Appliquer les classes `dark:` sur tous les composants
- [ ] Persister le choix dans localStorage
- [ ] Détecter la préférence système (`prefers-color-scheme`)
- [ ] Tester tous les composants en mode sombre

---

### 7. Internationalisation (i18n)

| Priorité | **MOYENNE** |
|----------|-------------|
| Description | Compléter les fichiers de traduction pour le français et le kirundi. La structure i18n existe mais les traductions sont incomplètes. |
| Livrable | Application entièrement traduite en français et kirundi |

**Sous-tâches :**
- [ ] Auditer tous les textes hardcodés dans les composants
- [ ] Extraire les textes vers les fichiers de locale
- [ ] Compléter `locales/fr.json` avec toutes les traductions
- [ ] Créer/compléter `locales/rn.json` (Kirundi)
- [ ] Ajouter le sélecteur de langue dans le header
- [ ] Persister la langue choisie
- [ ] Traduire les messages d'erreur de validation
- [ ] Traduire les libellés des enums (statuts, types)

---

### 8. Améliorations UX Générales

| Priorité | **BASSE** |
|----------|----------|
| Description | Améliorer l'expérience utilisateur globale avec des détails de polish. |
| Livrable | Interface plus fluide et professionnelle |

**Sous-tâches :**
- [ ] Ajouter des états de chargement (skeletons) sur les listes
- [ ] Ajouter des animations de transition entre pages
- [ ] Améliorer les messages de confirmation (modales)
- [ ] Ajouter des tooltips explicatifs sur les actions
- [ ] Optimiser les formulaires (autofocus, tab order)
- [ ] Ajouter le support clavier (raccourcis)
- [ ] Vérifier l'accessibilité (ARIA labels)
- [ ] Tester sur différents navigateurs

---

## Fichiers Principaux à Modifier

```
UI_NEMS/
├── src/
│   ├── app/components/
│   │   ├── pages/
│   │   │   ├── StatisticsPage.vue (à améliorer)
│   │   │   ├── InscriptionCreatePage.vue (à améliorer)
│   │   │   └── TeachersPage.vue (à compléter)
│   │   ├── ui/
│   │   │   ├── Toast.vue (à créer)
│   │   │   ├── StatCard.vue (à créer)
│   │   │   ├── ThemeToggle.vue (à créer)
│   │   │   └── CanAccess.vue (à créer)
│   │   └── charts/
│   │       ├── LineChart.vue (à créer)
│   │       ├── PieChart.vue (à créer)
│   │       └── BarChart.vue (à créer)
│   ├── directives/
│   │   └── can.js (à créer)
│   ├── plugins/
│   │   └── errorHandler.js (à créer)
│   ├── store/modules/
│   │   └── auth.js (ajouter permissions)
│   ├── locales/
│   │   ├── fr.json (à compléter)
│   │   └── rn.json (à créer/compléter)
│   └── styles/
│       └── theme.css (dark mode variables)
```

---

## Dépendances à Installer

```bash
# Graphiques
npm install chart.js vue-chartjs
# ou
npm install apexcharts vue3-apexcharts

# Icons (si pas déjà installé)
npm install lucide-vue-next
```

---

## Commandes Utiles

```bash
# Lancer le serveur de développement
npm run dev

# Build production
npm run build

# Linter
npm run lint

# Vérifier les types (si TypeScript)
npm run type-check
```

---

## Critères d'Acceptation

- [ ] Dashboard statistiques avec minimum 4 graphiques fonctionnels
- [ ] Gestion d'erreurs globale avec toasts pour toutes les réponses API
- [ ] Directive `v-can` fonctionnelle et appliquée sur tous les éléments sensibles
- [ ] Page inscriptions avec workflow complet (créer, soumettre, valider, rejeter)
- [ ] Page enseignants avec CRUD complet
- [ ] Dark mode fonctionnel avec toggle et persistence
- [ ] Application traduite à 100% en français
- [ ] Traduction kirundi à 80% minimum
- [ ] Aucun texte hardcodé visible dans l'interface
- [ ] Application testée sur Chrome, Firefox, Safari
