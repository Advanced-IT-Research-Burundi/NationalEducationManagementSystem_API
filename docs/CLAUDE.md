# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**NEMS (National Education Management System)** is a national-scale education management platform for a Ministry of Education in Burundi. The project consists of two main components:

- **API_NEMS**: Laravel 12 REST API backend
- **UI_NEMS**: Vue.js 3 SPA frontend

## Technology Stack

**Backend (API_NEMS):**

- PHP 8.4, Laravel 12
- Laravel Sanctum 4 (authentication)
- Spatie Laravel Permission 6 (RBAC)
- Pest 4 (testing), Laravel Pint (formatting)
- MySQL/PostgreSQL

**Frontend (UI_NEMS):**

- Vue.js 3.5, Vue Router 4, Vuex 4
- Radix Vue (UI components)
- Tailwind CSS 4, Vite 6
- Axios, Vue i18n

## Common Commands

### API (Backend)

```bash
cd API_NEMS

# Full dev environment (server, queue, logs, vite)
composer run dev

# Initial setup
composer run setup

# Run tests
composer run test
php artisan test --compact
php artisan test --compact --filter=testName
php artisan test --compact tests/Feature/ExampleTest.php

# Code formatting (run before committing)
vendor/bin/pint --dirty

# Create files
php artisan make:model ModelName --no-interaction
php artisan make:controller Api/ControllerName --no-interaction
php artisan make:test --pest TestName
```

### UI (Frontend)

```bash
cd UI_NEMS

# Development server
npm run dev

# Production build
npm run build
```

## Architecture

### Administrative Hierarchy (Core Domain)

```
PAYS (Country)
└── MINISTÈRE (Ministry)
└── PROVINCE
    └── COMMUNE
        └── ZONE
            ├── COLLINE (Hill)
            └── ÉCOLE (School)
```

All geographic data cascades down this hierarchy. When creating schools, only `colline_id` is required; the system auto-populates `zone_id`, `commune_id`, `province_id`, `pays_id`.

### Role-Based Access Control

Users have `admin_level` (PAYS, MINISTRY, PROVINCE, COMMUNE, ZONE, SCHOOL) and `admin_entity_id` for hierarchical data scoping. The `HasDataScope` trait applies automatic query filtering based on user's administrative scope.

**Roles:** Admin National, Admin Ministry, Director Provincial, Communal Officer, Zone Supervisor, School Director, Teacher, Administrative Staff

**Permissions:** view_data, create_data, update_data, delete_data, validate_data, export_data, manage_users, manage_schools

### School Workflow State Machine

```
BROUILLON (Draft)
    ↓ submit()
EN_ATTENTE_VALIDATION (Pending)
    ↓ validate()
ACTIVE
    ↓ deactivate()
INACTIVE
```

Workflow actions require hierarchical authorization (e.g., Provincial Director can validate schools in their province).

### Key Patterns

**Backend:**

- Controllers use Form Requests for validation (check `app/Http/Requests/`)
- Models define scopes, accessors, and helper methods (e.g., `canSubmit()`, `canValidate()`)
- Policies handle authorization (`app/Policies/`)
- Global scopes via `HasDataScope` trait filter data by user hierarchy
- API routes prefixed with `/api`, authentication routes at `/api/auth/*`

**Frontend:**

- Vuex store modules for state management (`src/store/`)
- API service layer with Axios (`src/services/`, `src/config/api.js`)
- Radix Vue-based reusable components (`src/app/components/ui/`)

## API Routes

**Authentication:**

- `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`

**School Management:**

- CRUD: `GET|POST /api/schools`, `GET|PUT|DELETE /api/schools/{id}`
- Workflow: `POST /api/schools/{id}/submit`, `/validate`, `/deactivate`

**Geographic Hierarchy:**

- `GET|POST /api/pays`, `/ministeres`, `/provinces`, `/communes`, `/zones`, `/collines`

## Code Style Rules

The API_NEMS directory includes Laravel Boost guidelines (`.cursor/rules/laravel-boost.mdc`). Key rules:

- Use PHP 8 constructor property promotion
- Always use explicit return types and type hints
- Prefer PHPDoc blocks over inline comments
- Use Eloquent relationships over raw queries; avoid `DB::`, prefer `Model::query()`
- Create Form Request classes for validation (not inline)
- Run `vendor/bin/pint --dirty` before finalizing changes
- Use Pest for testing with `RefreshDatabase` trait
- Tailwind CSS v4 uses CSS-first configuration (`@theme` directive), not `tailwind.config.js`

## Additional Resources

use docs/Mondu



docs/MODULE_INSCRIPTIONS_ELEVES.md
