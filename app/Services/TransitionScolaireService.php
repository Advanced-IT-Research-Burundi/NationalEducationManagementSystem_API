<?php

namespace App\Services;

use App\Enums\StatutAcademique;
use App\Enums\StatutMouvement;
use App\Enums\TypeMouvement;
use App\Models\AffectationClasse;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\MouvementEleve;
use App\Models\Niveau;
use App\Models\School;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransitionScolaireService
{
    /**
     * Promote a student to the next level in the next school year.
     */
    public function promouvoir(Eleve $eleve, AnneeScolaire $nouvelleAnnee): Inscription
    {
        return DB::transaction(function () use ($eleve, $nouvelleAnnee) {
            $inscriptionActuelle = $this->getInscriptionActuelle($eleve);

            if (! $inscriptionActuelle) {
                throw new \RuntimeException("L'élève {$eleve->nom_complet} n'a pas d'inscription active.");
            }

            $niveauActuel = $inscriptionActuelle->niveauDemande;

            if (! $niveauActuel) {
                throw new \RuntimeException("Le niveau actuel de l'élève est introuvable.");
            }

            $niveauSuperieur = Niveau::where('ordre', $niveauActuel->ordre + 1)
                ->where('cycle_id', $niveauActuel->cycle_id)
                ->first();

            if (! $niveauSuperieur) {
                // Try across cycles (end of cycle -> start of next)
                $niveauSuperieur = Niveau::where('ordre', $niveauActuel->ordre + 1)->first();
            }

            if (! $niveauSuperieur) {
                throw new \RuntimeException(
                    "Aucun niveau supérieur trouvé après {$niveauActuel->nom} (ordre {$niveauActuel->ordre})."
                );
            }

            $inscriptionActuelle->update(['statut_academique' => StatutAcademique::Admis]);

            $this->desactiverAffectation($inscriptionActuelle);

            $nouvelleInscription = $this->creerInscription(
                $eleve,
                $nouvelleAnnee,
                $inscriptionActuelle->school_id,
                $niveauSuperieur->id,
                'reinscription',
                false
            );

            $eleve->update([
                'niveau_id' => $niveauSuperieur->id,
                'est_redoublant' => false,
            ]);

            MouvementEleve::create([
                'eleve_id' => $eleve->id,
                'annee_scolaire_id' => $inscriptionActuelle->annee_scolaire_id,
                'type_mouvement' => TypeMouvement::Passage->value,
                'date_mouvement' => now()->toDateString(),
                'ecole_origine_id' => $inscriptionActuelle->school_id,
                'classe_origine_id' => $inscriptionActuelle->affectation?->classe_id,
                'inscription_origine_id' => $inscriptionActuelle->id,
                'inscription_destination_id' => $nouvelleInscription->id,
                'niveau_origine_id' => $niveauActuel->id,
                'niveau_destination_id' => $niveauSuperieur->id,
                'annee_scolaire_destination_id' => $nouvelleAnnee->id,
                'motif' => 'Passage au niveau supérieur',
                'statut' => StatutMouvement::Valide->value,
                'date_validation' => now(),
                'valide_par' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            return $nouvelleInscription;
        });
    }

    /**
     * Hold a student back (repeat the same level) in the next school year.
     */
    public function redoubler(Eleve $eleve, AnneeScolaire $nouvelleAnnee): Inscription
    {
        return DB::transaction(function () use ($eleve, $nouvelleAnnee) {
            $inscriptionActuelle = $this->getInscriptionActuelle($eleve);

            if (! $inscriptionActuelle) {
                throw new \RuntimeException("L'élève {$eleve->nom_complet} n'a pas d'inscription active.");
            }

            $niveauActuel = $inscriptionActuelle->niveauDemande;

            $inscriptionActuelle->update(['statut_academique' => StatutAcademique::Redouble]);

            $this->desactiverAffectation($inscriptionActuelle);

            $nouvelleInscription = $this->creerInscription(
                $eleve,
                $nouvelleAnnee,
                $inscriptionActuelle->school_id,
                $inscriptionActuelle->niveau_demande_id,
                'reinscription',
                true
            );

            $eleve->update(['est_redoublant' => true]);

            MouvementEleve::create([
                'eleve_id' => $eleve->id,
                'annee_scolaire_id' => $inscriptionActuelle->annee_scolaire_id,
                'type_mouvement' => TypeMouvement::Redoublement->value,
                'date_mouvement' => now()->toDateString(),
                'ecole_origine_id' => $inscriptionActuelle->school_id,
                'classe_origine_id' => $inscriptionActuelle->affectation?->classe_id,
                'inscription_origine_id' => $inscriptionActuelle->id,
                'inscription_destination_id' => $nouvelleInscription->id,
                'niveau_origine_id' => $niveauActuel?->id,
                'niveau_destination_id' => $niveauActuel?->id,
                'annee_scolaire_destination_id' => $nouvelleAnnee->id,
                'motif' => 'Redoublement',
                'statut' => StatutMouvement::Valide->value,
                'date_validation' => now(),
                'valide_par' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            return $nouvelleInscription;
        });
    }

    /**
     * Transfer a student to another school.
     */
    public function transferer(
        Eleve $eleve,
        School $nouvelEtablissement,
        ?Niveau $niveauCible,
        AnneeScolaire $annee
    ): Inscription {
        return DB::transaction(function () use ($eleve, $nouvelEtablissement, $niveauCible, $annee) {
            $inscriptionActuelle = $this->getInscriptionActuelle($eleve);

            if (! $inscriptionActuelle) {
                throw new \RuntimeException("L'élève {$eleve->nom_complet} n'a pas d'inscription active.");
            }

            $niveauId = $niveauCible?->id ?? $inscriptionActuelle->niveau_demande_id;

            $inscriptionActuelle->update(['statut_academique' => StatutAcademique::Transfere]);

            $this->desactiverAffectation($inscriptionActuelle);

            $nouvelleInscription = $this->creerInscription(
                $eleve,
                $annee,
                $nouvelEtablissement->id,
                $niveauId,
                'transfert_entrant',
                false
            );

            $eleve->update([
                'school_id' => $nouvelEtablissement->id,
                'statut_global' => Eleve::STATUT_ACTIF,
            ]);

            if ($niveauCible) {
                $eleve->update(['niveau_id' => $niveauCible->id]);
            }

            MouvementEleve::create([
                'eleve_id' => $eleve->id,
                'annee_scolaire_id' => $inscriptionActuelle->annee_scolaire_id,
                'type_mouvement' => TypeMouvement::TransfertSortant->value,
                'date_mouvement' => now()->toDateString(),
                'ecole_origine_id' => $inscriptionActuelle->school_id,
                'ecole_destination_id' => $nouvelEtablissement->id,
                'classe_origine_id' => $inscriptionActuelle->affectation?->classe_id,
                'inscription_origine_id' => $inscriptionActuelle->id,
                'inscription_destination_id' => $nouvelleInscription->id,
                'niveau_origine_id' => $inscriptionActuelle->niveau_demande_id,
                'niveau_destination_id' => $niveauId,
                'annee_scolaire_destination_id' => $annee->id,
                'motif' => 'Transfert vers ' . $nouvelEtablissement->name,
                'statut' => StatutMouvement::EnAttente->value,
                'created_by' => Auth::id(),
            ]);

            return $nouvelleInscription;
        });
    }

    /**
     * Generate a new school year by duplicating classes from the previous year.
     */
    public function genererNouvelleAnnee(AnneeScolaire $precedente, array $data): AnneeScolaire
    {
        return DB::transaction(function () use ($precedente, $data) {
            $nouvelleAnnee = AnneeScolaire::create([
                'code' => $data['code'],
                'libelle' => $data['libelle'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'est_active' => false,
            ]);

            $classesADupliquer = Classe::withoutGlobalScopes()
                ->where('annee_scolaire_id', $precedente->id)
                ->where('statut', Classe::STATUS_ACTIVE)
                ->get();

            foreach ($classesADupliquer as $classe) {
                Classe::create([
                    'nom' => $classe->nom,
                    'code' => $classe->code,
                    'niveau_id' => $classe->niveau_id,
                    'section_id' => $classe->section_id,
                    'school_id' => $classe->school_id,
                    'annee_scolaire_id' => $nouvelleAnnee->id,
                    'local' => $classe->local,
                    'salle' => $classe->salle,
                    'capacite' => $classe->capacite,
                    'effectif' => 0,
                    'statut' => Classe::STATUS_ACTIVE,
                    'created_by' => Auth::id(),
                ]);
            }

            return $nouvelleAnnee;
        });
    }

    /**
     * Get the current active inscription for a student.
     */
    private function getInscriptionActuelle(Eleve $eleve): ?Inscription
    {
        $activeYear = AnneeScolaire::current();

        if (! $activeYear) {
            return null;
        }

        return Inscription::withoutGlobalScopes()
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $activeYear->id)
            ->where('statut_academique', StatutAcademique::EnCours)
            ->latest()
            ->first();
    }

    private function desactiverAffectation(Inscription $inscription): void
    {
        AffectationClasse::where('inscription_id', $inscription->id)
            ->where('est_active', true)
            ->update([
                'est_active' => false,
                'date_fin' => now(),
            ]);
    }

    private function creerInscription(
        Eleve $eleve,
        AnneeScolaire $annee,
        int $schoolId,
        int $niveauId,
        string $typeInscription,
        bool $estRedoublant
    ): Inscription {
        $prefix = 'INS';
        $year = $annee->date_debut?->format('Y') ?? date('Y');
        $sequence = Inscription::withoutGlobalScopes()->count() + 1;
        $numero = sprintf('%s%s%06d', $prefix, $year, $sequence);

        return Inscription::withoutGlobalScopes()->create([
            'numero_inscription' => $numero,
            'eleve_id' => $eleve->id,
            'annee_scolaire_id' => $annee->id,
            'school_id' => $schoolId,
            'niveau_demande_id' => $niveauId,
            'type_inscription' => $typeInscription,
            'statut' => 'valide',
            'statut_academique' => StatutAcademique::EnCours->value,
            'date_inscription' => now()->toDateString(),
            'date_soumission' => now(),
            'date_validation' => now(),
            'est_redoublant' => $estRedoublant,
            'created_by' => Auth::id(),
            'valide_par' => Auth::id(),
        ]);
    }
}
