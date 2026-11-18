<?php

namespace App\Services\HR;

use App\Models\Departement;
use App\Models\Employe;
use App\Models\Enseignant;
use App\Models\PersonnelAdministratif;
use App\Models\Poste;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crée ou met à jour la fiche Employé (RH) d'un utilisateur à partir de son rôle :
 * le poste porte le nom du rôle, le département correspond au niveau administratif.
 */
class EmployeSyncService
{
    /**
     * Rôles synchronisés, du plus prioritaire au moins prioritaire
     * (un utilisateur à plusieurs rôles reçoit le poste du premier trouvé).
     *
     * @var array<string, array{departement: string, poste_code: string}>
     */
    public const ROLE_MAPPING = [
        Role::ADMIN_MINISTERE => ['departement' => 'MINISTERE', 'poste_code' => 'PST-ADMIN-MINISTERE'],
        Role::ADMIN_NATIONAL => ['departement' => 'MINISTERE', 'poste_code' => 'PST-ADMIN-NATIONAL'],
        Role::DIRECTEUR_PROVINCIAL => ['departement' => 'PROVINCE', 'poste_code' => 'PST-DIRECTEUR-PROVINCIAL'],
        Role::AGENT_COMMUNAL => ['departement' => 'COMMUNE', 'poste_code' => 'PST-AGENT-COMMUNAL'],
        Role::SUPERVISEUR_ZONE => ['departement' => 'ZONE', 'poste_code' => 'PST-SUPERVISEUR-ZONE'],
        Role::DIRECTEUR_ECOLE => ['departement' => 'ECOLE', 'poste_code' => 'PST-DIRECTEUR-ECOLE'],
        Role::PERSONNEL_ADMINISTRATIF => ['departement' => 'ECOLE', 'poste_code' => 'PST-PERSONNEL-ADMIN'],
        Role::ENSEIGNANT => ['departement' => 'ECOLE', 'poste_code' => 'PST-ENSEIGNANT'],
    ];

    /**
     * @var array<string, array{code: string, nom: string, service_code: string, service_nom: string}>
     */
    public const DEPARTEMENTS = [
        'MINISTERE' => ['code' => 'DEP-MINISTERE', 'nom' => 'Ministère', 'service_code' => 'SRV-MINISTERE', 'service_nom' => 'Administration centrale'],
        'PROVINCE' => ['code' => 'DEP-PROVINCE', 'nom' => 'Direction Provinciale', 'service_code' => 'SRV-PROVINCE', 'service_nom' => 'Direction Provinciale'],
        'COMMUNE' => ['code' => 'DEP-COMMUNE', 'nom' => 'Direction Communale', 'service_code' => 'SRV-COMMUNE', 'service_nom' => 'Direction Communale'],
        'ZONE' => ['code' => 'DEP-ZONE', 'nom' => 'Zone', 'service_code' => 'SRV-ZONE', 'service_nom' => 'Supervision de zone'],
        'ECOLE' => ['code' => 'DEP-ETABLISSEMENTS', 'nom' => 'Établissements', 'service_code' => 'SRV-ETABLISSEMENTS', 'service_nom' => 'Personnel des établissements'],
    ];

    /**
     * Synchronise la fiche Employé de l'utilisateur. Retourne null si aucun rôle n'est concerné.
     */
    public function syncUser(User $user): ?Employe
    {
        $roleName = $this->primaryRole($user);

        if ($roleName === null) {
            return null;
        }

        return DB::transaction(function () use ($user, $roleName): Employe {
            $poste = $this->resolvePoste($roleName);
            $employe = $this->findEmploye($user) ?? new Employe;

            [$nom, $prenom] = $this->splitName($user);
            $enseignant = Enseignant::query()->where('user_id', $user->id)->first();

            $employe->fill(array_filter([
                'user_id' => $user->id,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $this->availableEmail($user->email, $employe),
                'telephone' => $enseignant?->telephone ?: $employe->telephone,
                'date_naissance' => $enseignant?->date_naissance ?? $employe->date_naissance,
                'date_embauche' => $enseignant?->date_embauche ?? $employe->date_embauche,
                'poste_id' => $poste->id,
                'service_id' => $poste->service_id,
                'departement_id' => $poste->service->departement_id,
            ], fn ($value) => ! is_null($value)));

            $sourceMatricule = $this->sourceMatricule($user, $enseignant, $employe);
            if ($sourceMatricule) {
                $employe->matricule = $sourceMatricule;
            } elseif (! $employe->matricule) {
                $employe->matricule = $this->generateMatricule($nom, $prenom);
            }

            if (! $employe->exists) {
                $employe->statut = Employe::STATUT_ACTIF;
                $employe->temps_travail = 'PLEIN_TEMPS';
            }

            $employe->save();

            return $employe;
        });
    }

    public function primaryRole(User $user): ?string
    {
        $roleNames = $user->roles()->pluck('name')->all();

        foreach (array_keys(self::ROLE_MAPPING) as $roleName) {
            if (in_array($roleName, $roleNames, true)) {
                return $roleName;
            }
        }

        return null;
    }

    public function resolvePoste(string $roleName): Poste
    {
        $mapping = self::ROLE_MAPPING[$roleName];
        $structure = self::DEPARTEMENTS[$mapping['departement']];

        $departement = Departement::withTrashed()->firstOrCreate(
            ['code' => $structure['code']],
            ['nom' => $structure['nom'], 'statut' => 'ACTIF'],
        );
        if ($departement->trashed()) {
            $departement->restore();
        }

        $service = Service::query()->firstOrCreate(
            ['code' => $structure['service_code']],
            ['nom' => $structure['service_nom'], 'departement_id' => $departement->id, 'statut' => 'ACTIF'],
        );
        if ($service->departement_id === null) {
            $service->update(['departement_id' => $departement->id]);
        }

        $poste = Poste::withTrashed()->firstOrCreate(
            ['code' => $mapping['poste_code']],
            ['nom' => $roleName, 'service_id' => $service->id, 'statut' => 'ACTIF'],
        );
        if ($poste->trashed()) {
            $poste->restore();
        }
        if ($poste->service_id === null) {
            $poste->update(['service_id' => $service->id]);
        }

        return $poste->load('service');
    }

    protected function findEmploye(User $user): ?Employe
    {
        $employe = Employe::withTrashed()->where('user_id', $user->id)->first();

        if (! $employe && $user->email) {
            $employe = Employe::withTrashed()
                ->whereNull('user_id')
                ->where('email', $user->email)
                ->first();
        }

        if ($employe?->trashed()) {
            $employe->restore();
        }

        return $employe;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitName(User $user): array
    {
        if ($user->nom || $user->prenom) {
            return [(string) ($user->nom ?: $user->prenom), (string) ($user->prenom ?? '')];
        }

        $parts = preg_split('/\s+/', trim((string) $user->name), 2);

        return [$parts[0] ?: 'Utilisateur', $parts[1] ?? ''];
    }

    /**
     * Matricule officiel de la personne (enseignant ou personnel administratif), s'il n'est pas déjà pris.
     */
    protected function sourceMatricule(User $user, ?Enseignant $enseignant, Employe $employe): ?string
    {
        $matricule = $enseignant?->matricule
            ?: PersonnelAdministratif::query()->where('user_id', $user->id)->value('matricule');

        if (! $matricule) {
            return null;
        }

        $taken = Employe::withTrashed()
            ->where('matricule', $matricule)
            ->when($employe->exists, fn ($q) => $q->whereKeyNot($employe->id))
            ->exists();

        return $taken ? null : $matricule;
    }

    protected function availableEmail(?string $email, Employe $employe): ?string
    {
        if (! $email) {
            return null;
        }

        $taken = Employe::withTrashed()
            ->where('email', $email)
            ->when($employe->exists, fn ($q) => $q->whereKeyNot($employe->id))
            ->exists();

        return $taken ? null : $email;
    }

    protected function generateMatricule(string $nom, string $prenom): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($nom.$prenom)), 0, 6)) ?: 'EMP';

        do {
            $matricule = 'EMP-'.$base.'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Employe::withTrashed()->where('matricule', $matricule)->exists());

        return $matricule;
    }
}
