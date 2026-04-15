<?php

namespace Database\Seeders;

use App\Models\Pays;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    protected const GUARD = 'api';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissionDefinitions() as $permission) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => self::GUARD,
                ],
                [
                    'description' => $permission['description'] ?? null,
                    'group_name' => $permission['group_name'] ?? null,
                    'is_system' => $permission['is_system'] ?? true,
                    'sort_order' => $permission['sort_order'] ?? 0,
                ]
            );
        }

        $allPermissionNames = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        foreach ($this->roleDefinitions() as $roleDefinition) {
            $role = Role::query()->updateOrCreate(
                [
                    'name' => $roleDefinition['name'],
                    'guard_name' => self::GUARD,
                ],
                [
                    'description' => $roleDefinition['description'],
                    'is_system' => $roleDefinition['is_system'] ?? true,
                    'sort_order' => $roleDefinition['sort_order'] ?? 0,
                ]
            );

            $permissions = $roleDefinition['permissions'] === ['*']
                ? $allPermissionNames
                : $roleDefinition['permissions'];

            $role->syncPermissions($permissions);
        }

        $this->seedSuperAdminUser($allPermissionNames);
        $this->seedBootstrapAdmin();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Core permission catalogue.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function permissionDefinitions(): array
    {
        $definitions = [];
        $sortOrder = 10;

        $entities = [
            'user',
            'role',
            'permission',
            'pays',
            'ministere',
            'province',
            'commune',
            'zone',
            'colline',
            'school',
            'student',
            'teacher',
            'report',
            'setting',
            'classe',
            'eleve',
            'enseignant',
            'annee_scolaire',
            'trimestre',
            'matiere',
            'note',
            'bulletin',
            'inscription',
            'paiement',
            'audit',
            'section',
            'batiment',
            'salle',
            'equipement',
            'maintenance',
            'inspection',
            'formation',
            'standard_qualite',
            'campagne_inscription',
            'presence',
            'conge',
            'carriere',
            'evaluation',
            'affectation_enseignant',
            'affectation_eleve',
            'mouvement_eleve',
            'niveau',
            'cycle_scolaire',
            'type_scolaire',
        ];

        $actions = [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $definitions[] = [
                    'name' => "{$action}_{$entity}",
                    'description' => $this->describeGeneratedPermission($action, $entity),
                    'group_name' => $entity,
                    'is_system' => true,
                    'sort_order' => $sortOrder++,
                ];
            }
        }

        $specialPermissions = [
            ['name' => 'view_data', 'description' => 'Consulter les données selon le périmètre hiérarchique.', 'group_name' => 'core_access'],
            ['name' => 'create_data', 'description' => 'Créer des données génériques lorsque la policy du module le permet.', 'group_name' => 'core_access'],
            ['name' => 'update_data', 'description' => 'Modifier des données génériques lorsque la policy du module le permet.', 'group_name' => 'core_access'],
            ['name' => 'delete_data', 'description' => 'Supprimer des données génériques lorsque la policy du module le permet.', 'group_name' => 'core_access'],
            ['name' => 'validate_data', 'description' => 'Valider les workflows métier nécessitant une approbation.', 'group_name' => 'core_access'],
            ['name' => 'export_data', 'description' => 'Exporter des données ou rapports.', 'group_name' => 'core_access'],
            ['name' => 'import_data', 'description' => 'Importer des données dans le système.', 'group_name' => 'core_access'],
            ['name' => 'access_dashboard', 'description' => 'Accéder aux tableaux de bord.', 'group_name' => 'navigation'],
            ['name' => 'access_settings', 'description' => 'Accéder aux pages de paramétrage.', 'group_name' => 'navigation'],
            ['name' => 'manage_users', 'description' => 'Gérer les comptes utilisateurs.', 'group_name' => 'administration'],
            ['name' => 'manage_roles', 'description' => 'Créer et modifier les rôles applicatifs.', 'group_name' => 'administration'],
            ['name' => 'manage_permissions', 'description' => 'Consulter et gérer les permissions applicatives.', 'group_name' => 'administration'],
            ['name' => 'manage_schools', 'description' => 'Piloter le cycle de vie des écoles.', 'group_name' => 'administration'],
            ['name' => 'manage_system_config', 'description' => 'Modifier la configuration sensible du système.', 'group_name' => 'administration'],
            ['name' => 'view_statistics', 'description' => 'Consulter les statistiques et indicateurs.', 'group_name' => 'reporting'],
            ['name' => 'view_reports', 'description' => 'Consulter les rapports.', 'group_name' => 'reporting'],
            ['name' => 'generate_reports', 'description' => 'Générer des rapports.', 'group_name' => 'reporting'],
            ['name' => 'view_audit_logs', 'description' => 'Consulter les journaux d’audit.', 'group_name' => 'security'],
            ['name' => 'manage_academic_year', 'description' => 'Gérer les années scolaires.', 'group_name' => 'academic'],
            ['name' => 'manage_classes', 'description' => 'Gérer les classes.', 'group_name' => 'academic'],
            ['name' => 'manage_students', 'description' => 'Gérer les élèves et leurs inscriptions.', 'group_name' => 'academic'],
            ['name' => 'manage_teachers', 'description' => 'Gérer les enseignants et affectations.', 'group_name' => 'academic'],
            ['name' => 'manage_grades', 'description' => 'Gérer les évaluations, notes et bulletins.', 'group_name' => 'academic'],
            ['name' => 'print_bulletins', 'description' => 'Imprimer les bulletins.', 'group_name' => 'academic'],
            ['name' => 'submit_school', 'description' => 'Soumettre une école pour validation.', 'group_name' => 'school_workflow'],
            ['name' => 'validate_school', 'description' => 'Valider une école.', 'group_name' => 'school_workflow'],
            ['name' => 'deactivate_school', 'description' => 'Désactiver une école.', 'group_name' => 'school_workflow'],
            ['name' => 'campagnes.view', 'description' => 'Consulter les campagnes d’inscription.', 'group_name' => 'campagnes'],
            ['name' => 'campagnes.create', 'description' => 'Créer des campagnes d’inscription.', 'group_name' => 'campagnes'],
            ['name' => 'campagnes.update', 'description' => 'Modifier des campagnes d’inscription.', 'group_name' => 'campagnes'],
            ['name' => 'campagnes.delete', 'description' => 'Supprimer des campagnes d’inscription.', 'group_name' => 'campagnes'],
            ['name' => 'campagnes.open', 'description' => 'Ouvrir des campagnes d’inscription.', 'group_name' => 'campagnes'],
            ['name' => 'campagnes.close', 'description' => 'Clôturer des campagnes d’inscription.', 'group_name' => 'campagnes'],
        ];

        foreach ($specialPermissions as $permission) {
            $definitions[] = $permission + [
                'is_system' => true,
                'sort_order' => $sortOrder++,
            ];
        }

        return $definitions;
    }

    /**
     * Seeded system role catalogue.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function roleDefinitions(): array
    {
        return [
            [
                'name' => Role::SUPER_ADMIN,
                'description' => 'Compte système réservé, avec tous les rôles et toutes les permissions.',
                'sort_order' => 1000,
                'permissions' => ['*'],
            ],
            [
                'name' => Role::ADMIN_NATIONAL,
                'description' => 'Pilotage complet du système à l’échelle nationale.',
                'sort_order' => 900,
                'permissions' => ['*'],
            ],
            [
                'name' => Role::ADMIN_MINISTERE,
                'description' => 'Administration au niveau ministériel avec supervision étendue.',
                'sort_order' => 800,
                'permissions' => [
                    'view_data', 'create_data', 'update_data', 'validate_data', 'export_data',
                    'access_dashboard', 'access_settings',
                    'manage_users', 'manage_schools',
                    'view_statistics', 'view_reports', 'generate_reports',
                    'view_user', 'view_any_user', 'create_user', 'update_user',
                    'view_pays', 'view_any_pays',
                    'view_ministere', 'view_any_ministere', 'update_ministere',
                    'view_province', 'view_any_province', 'create_province', 'update_province', 'delete_province',
                    'view_commune', 'view_any_commune', 'create_commune', 'update_commune', 'delete_commune',
                    'view_zone', 'view_any_zone', 'create_zone', 'update_zone', 'delete_zone',
                    'view_colline', 'view_any_colline', 'create_colline', 'update_colline', 'delete_colline',
                    'view_school', 'view_any_school', 'create_school', 'update_school', 'delete_school',
                    'submit_school', 'validate_school', 'deactivate_school',
                    'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
                    'view_report', 'view_any_report', 'create_report',
                ],
            ],
            [
                'name' => Role::DIRECTEUR_PROVINCIAL,
                'description' => 'Supervision et validation des activités éducatives de la province.',
                'sort_order' => 700,
                'permissions' => [
                    'view_data', 'create_data', 'update_data', 'validate_data', 'export_data',
                    'access_dashboard', 'access_settings',
                    'manage_users', 'manage_schools',
                    'view_statistics', 'view_reports',
                    'view_user', 'view_any_user', 'create_user', 'update_user',
                    'view_province', 'view_any_province',
                    'view_commune', 'view_any_commune', 'create_commune', 'update_commune',
                    'view_zone', 'view_any_zone', 'create_zone', 'update_zone',
                    'view_colline', 'view_any_colline', 'create_colline', 'update_colline',
                    'view_school', 'view_any_school', 'create_school', 'update_school',
                    'submit_school', 'validate_school',
                    'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
                    'view_report', 'view_any_report', 'create_report',
                ],
            ],
            [
                'name' => Role::AGENT_COMMUNAL,
                'description' => 'Gestion opérationnelle et collecte des données au niveau communal.',
                'sort_order' => 600,
                'permissions' => [
                    'view_data', 'create_data', 'update_data', 'export_data',
                    'access_dashboard',
                    'view_statistics', 'view_reports',
                    'view_user', 'view_any_user',
                    'view_commune', 'view_any_commune',
                    'view_zone', 'view_any_zone', 'create_zone', 'update_zone',
                    'view_colline', 'view_any_colline', 'create_colline', 'update_colline',
                    'view_school', 'view_any_school', 'create_school', 'update_school',
                    'submit_school',
                    'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
                    'view_report', 'view_any_report',
                ],
            ],
            [
                'name' => Role::SUPERVISEUR_ZONE,
                'description' => 'Supervision de proximité des écoles de la zone.',
                'sort_order' => 500,
                'permissions' => [
                    'view_data', 'create_data', 'update_data',
                    'access_dashboard',
                    'view_statistics',
                    'view_user', 'view_any_user',
                    'view_zone', 'view_any_zone',
                    'view_colline', 'view_any_colline', 'create_colline', 'update_colline',
                    'view_school', 'view_any_school', 'update_school',
                    'submit_school',
                    'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
                    'view_report', 'view_any_report',
                ],
            ],
            [
                'name' => Role::DIRECTEUR_ECOLE,
                'description' => 'Gestion complète d’un établissement scolaire.',
                'sort_order' => 400,
                'permissions' => [
                    'view_data', 'access_dashboard',
                    'manage_users', 'manage_schools', 'manage_classes', 'manage_students', 'manage_teachers', 'manage_grades',
                    'view_statistics', 'view_reports', 'submit_school', 'print_bulletins',
                    'view_user', 'view_any_user', 'create_user', 'update_user',
                    'view_school', 'update_school',
                    'view_student', 'view_any_student', 'create_student', 'update_student', 'delete_student',
                    'view_teacher', 'view_any_teacher', 'create_teacher', 'update_teacher',
                    'view_classe', 'view_any_classe', 'create_classe', 'update_classe', 'delete_classe',
                    'view_eleve', 'view_any_eleve', 'create_eleve', 'update_eleve', 'delete_eleve',
                    'view_enseignant', 'view_any_enseignant', 'create_enseignant', 'update_enseignant',
                    'view_inscription', 'view_any_inscription', 'create_inscription', 'update_inscription',
                    'view_note', 'view_any_note', 'create_note', 'update_note',
                    'view_bulletin', 'view_any_bulletin', 'create_bulletin',
                    'view_paiement', 'view_any_paiement', 'create_paiement', 'update_paiement',
                    'view_report', 'view_any_report', 'create_report',
                ],
            ],
            [
                'name' => Role::ENSEIGNANT,
                'description' => 'Consultation pédagogique, saisie des notes et suivi des classes.',
                'sort_order' => 300,
                'permissions' => [
                    'view_data', 'access_dashboard', 'manage_grades',
                    'view_school',
                    'view_student', 'view_any_student',
                    'view_classe', 'view_any_classe',
                    'view_eleve', 'view_any_eleve',
                    'view_matiere', 'view_any_matiere',
                    'view_note', 'view_any_note', 'create_note', 'update_note',
                    'view_bulletin', 'view_any_bulletin',
                ],
            ],
            [
                'name' => Role::PERSONNEL_ADMINISTRATIF,
                'description' => 'Support administratif pour les inscriptions, paiements et registres scolaires.',
                'sort_order' => 200,
                'permissions' => [
                    'view_data', 'access_dashboard', 'manage_students',
                    'view_school',
                    'view_student', 'view_any_student', 'create_student', 'update_student',
                    'view_eleve', 'view_any_eleve', 'create_eleve', 'update_eleve',
                    'view_classe', 'view_any_classe',
                    'view_inscription', 'view_any_inscription', 'create_inscription', 'update_inscription',
                    'view_paiement', 'view_any_paiement', 'create_paiement', 'update_paiement',
                    'view_bulletin', 'view_any_bulletin', 'print_bulletins',
                ],
            ],
        ];
    }

    /**
     * Seed the reserved super administrator account.
     */
    protected function seedSuperAdminUser(array $allPermissionNames): void
    {
        $pays = Pays::query()->first();
        $email = env('SUPADMIN_EMAIL', 'supadmin@nems.bi');
        $password = env('SUPADMIN_PASSWORD', 'ChangeMe123!');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'supAdmin (sudo)',
                'password' => Hash::make($password),
                'statut' => 'actif',
                'is_super_admin' => true,
                'admin_level' => 'PAYS',
                'admin_entity_id' => $pays?->id,
                'pays_id' => $pays?->id,
            ]
        );

        $user->forceFill([
            'name' => 'supAdmin (sudo)',
            'statut' => 'actif',
            'is_super_admin' => true,
            'admin_level' => 'PAYS',
            'admin_entity_id' => $pays?->id,
            'pays_id' => $pays?->id,
        ])->save();

        $user->syncRoles(Role::query()->pluck('name')->all());
        $user->syncPermissions($allPermissionNames);
    }

    /**
     * Keep the bootstrap developer account operational after reseeding.
     */
    protected function seedBootstrapAdmin(): void
    {
        $bootstrapUser = User::query()
            ->where('is_super_admin', false)
            ->oldest('id')
            ->first();

        if (! $bootstrapUser) {
            return;
        }

        if (! $bootstrapUser->roles()->exists()) {
            $bootstrapUser->assignRole(Role::ADMIN_NATIONAL);
        }
    }

    protected function describeGeneratedPermission(string $action, string $entity): string
    {
        $entityLabel = Str::of($entity)
            ->replace('_', ' ')
            ->headline()
            ->toString();

        return match ($action) {
            'view' => "Consulter un enregistrement {$entityLabel}.",
            'view_any' => "Consulter la liste des {$entityLabel}.",
            'create' => "Créer un enregistrement {$entityLabel}.",
            'update' => "Modifier un enregistrement {$entityLabel}.",
            'delete' => "Supprimer un enregistrement {$entityLabel}.",
            'restore' => "Restaurer un enregistrement {$entityLabel}.",
            'force_delete' => "Supprimer définitivement un enregistrement {$entityLabel}.",
            default => "Autorisation système pour {$entityLabel}.",
        };
    }
}
