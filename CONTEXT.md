# Create the Markdown file for download

content = """# NEMS_LARAVEL_CONTEXT

## 1. Project Identity

- **Project Name**: National Education Management System
- **Acronym**: NEMS
- **Organization**: Advanced IT and Research Burundi
- **Environment**: Test / Staging

**Purpose**:
NEMS is a national-scale education management platform designed for a Ministry of Education. It centralizes administrative, academic, geographic, and statistical data across the entire education system.

---

## 2. Technology Stack

- **Backend**: Laravel 12 (API only)
- **Authentication**: Laravel Sanctum (SPA mode)
- **Authorization (RBAC)**: spatie/laravel-permission
- **Database**: PostgreSQL or MySQL
- **API Style**: RESTful, JSON only
- **Frontend**: Vue.js 3 (SPA)
- **Maps**: Leaflet + OpenStreetMap
- **Deployment**: Linux (Apache or Nginx)

---

## 3. Administrative Hierarchy (Core Rule)

COUNTRY
└── MINISTRY
└── PROVINCE
└── COMMUNE
└── ZONE

└── COLLINE
└── SCHOOL

yaml
Always show details

Copy code

---

## 4. User Model (Extended)

- admin_level: COUNTRY | MINISTRY | PROVINCE | COMMUNE | ZONE | SCHOOL
- admin_entity_id: BIGINT

---

## 5. Roles

Admin National
Admin Ministry
Director Provincial
Communal Officer
Zone Supervisor
School Director
Teacher
Administrative Staff

---

## 6. Permissions

view_data
create_data
update_data
delete_data
validate_data
export_data
manage_users
manage_schools

---

## 7. Data Scope

- Admin National: full access
- Others: restricted by admin_entity_id
- Implemented using Laravel Global Scopes

---

## 8. School Module

Includes geolocation, validation workflow, and lifecycle status.

---

## 9. API Standards

- /api/v1
- JSON responses only
- Sanctum protected routes

---

## Context Usage

Use in Anti-Gravity AI:

Use context: NEMS_LARAVEL_CONTEXT
"""

file_path = "/mnt/data/NEMS_LARAVEL_CONTEXT.md"
with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

file_path
