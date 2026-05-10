## Plan: Auto-generated passwords, reset workflow, and Parent role

TL;DR: Add admin-created parent accounts with auto-generated passwords and a Parent role, implement a many-to-many `parent_eleve` pivot between students and parent users, and add a Student detail tab that shows linked parents with contact and address details.

**Steps**

1. Backend updates (Nems_Api)
   - Update `app/Http/Requests/StoreUserRequest.php` so password is optional on user creation.
   - Create a migration to add `must_change_password` boolean to `users`.
   - Create a migration for the `parent_eleve` pivot table linking `users` and `eleves`.
   - Add parent relationships:
     - `App\Models\User::children()` -> BelongsToMany(Eleve::class, 'parent_eleve', 'parent_id', 'eleve_id')
     - `App\Models\Eleve::parents()` -> BelongsToMany(User::class, 'parent_eleve', 'eleve_id', 'parent_id')
   - Add `Role::PARENT` constant to `app/Models/Role.php` and seed the Parent role in `database/seeders/RolesAndPermissionsSeeder.php`.
   - Update `app/Http/Controllers/Api/Core/UserController.php::store()`:
     - generate a random password when none is provided,
     - hash it and save it,
     - assign `must_change_password = true`,
     - assign the Parent role when creating parent users,
     - send a welcome email with login or reset guidance using `WelcomeMail`.
   - Keep the existing admin password-reset route intact, but ensure it clears `must_change_password` when an admin resets a user's password.
   - Prefer exposing parents via the existing student show endpoint rather than adding dedicated parent APIs first; load `parents` when returning `EleveResource`.
   - Update `EleveResource` to include a `parents` array when the relation is loaded.
   - Add or update API endpoints if needed for parent-to-student linking (e.g. `POST /academic/eleves/{eleve}/parents` and `DELETE /academic/eleves/{eleve}/parents/{parent}`) only if UI assignment is required.
   - Add public forgot/reset password endpoints in `routes/modules/core.php` and `AuthController`:
     - `POST /auth/password/forgot`
     - `POST /auth/password/reset`
   - Implement `AuthController::forgotPassword()` to generate a reset token and send `ResetPasswordMail`.
   - Implement `AuthController::resetPassword()` to verify token/email, update password, and clear `must_change_password`.
   - Return `must_change_password` in login responses so the frontend can enforce first-time password reset.

2. Email templates and mailables
   - Update `app/Mail/WelcomeMail.php` to accept the created user and generated password, and render `emails.welcome`.
   - Create `resources/views/emails/welcome.blade.php` with a welcome message and next steps.
   - Create `resources/views/emails/reset-password.blade.php` for the forgot-password flow.
   - Keep `app/Mail/ResetPasswordMail.php` wired to the reset email template.

3. Frontend updates (UI)
   - Update `UI/src/app/components/pages/StudentShowPage.vue`:
     - add a dedicated `Parents` tab or section,
     - display linked parents in a table with phone, email, address, relation, and useful mapping fields,
     - preserve the existing parent/tutor card but make the structured linked-parent list the primary source.
   - Ensure `StudentShowPage.vue` loads `eleve.parents` from the API response.
   - If parent assignment is needed in the future, add parent linking controls on the student edit page or a parent management modal.
   - Add or update `UI/src/services/EleveService.js` to support parent payloads if needed (e.g. `addParent`, `removeParent`).
   - Keep the password-reset UI additions from the previous plan for the auth flow.

4. Tests and verification
   - Add backend feature tests for:
     - creating a parent user without a password,
     - the Parent role and `parent_eleve` relationship,
     - the student show endpoint returning linked parent records,
     - forgot password request and reset password flow,
     - `must_change_password` behavior.
   - Run targeted tests in `Nems_Api` only.
   - Manual verification:
     - create a parent user with blank password and confirm the welcome email,
     - verify a student show page includes a Parents table after linking parents,
     - confirm parent rows include phone, email, address, and mapping-friendly data,
     - verify forgot/reset password works for parent users.

**Relevant files**
- `Nems_Api/app/Http/Requests/StoreUserRequest.php`
- `Nems_Api/app/Http/Controllers/Api/Core/UserController.php`
- `Nems_Api/app/Http/Controllers/Api/Core/AuthController.php`
- `Nems_Api/app/Models/User.php`
- `Nems_Api/app/Models/Eleve.php`
- `Nems_Api/app/Models/Role.php`
- `Nems_Api/app/Http/Resources/EleveResource.php`
- `Nems_Api/database/seeders/RolesAndPermissionsSeeder.php`
- `Nems_Api/database/migrations/*_create_parent_eleve_table.php`
- `Nems_Api/resources/views/emails/welcome.blade.php`
- `Nems_Api/resources/views/emails/reset-password.blade.php`
- `UI/src/app/components/pages/StudentShowPage.vue`
- `UI/src/app/components/pages/LoginPage.vue`
- `UI/src/app/components/pages/ResetPasswordPage.vue`
- `UI/src/app/components/settings/SettingsUsers.vue`
- `UI/src/router/basepage.js`
- `UI/src/services/EleveService.js`

**Decisions / open questions**
- Parents are represented as regular user accounts with a Parent role and linked via `parent_eleve`.
- If you want a separate parent management page later, I can add it after the student detail tab is working.

**Scope**
- Adds Parent role support, parent-child pivot linking, and a Student detail Parents tab with rich contact/address details.
- Also keeps the auth reset flow and auto-generated password behavior from the previous plan.
