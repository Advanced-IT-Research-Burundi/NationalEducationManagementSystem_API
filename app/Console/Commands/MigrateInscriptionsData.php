<?php

namespace App\Console\Commands;

use App\Models\AffectationClasse;
use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\NoteConduite;
use App\Models\SanctionEleve;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateInscriptionsData extends Command
{
    protected $signature = 'nems:migrate-inscriptions {--dry-run : Show what would be done without making changes}';

    protected $description = 'Migrate existing student data to the inscription-based architecture';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made.');
        }

        $this->migrateElevesToInscriptions($dryRun);
        $this->migratePivotToAffectations($dryRun);
        $this->linkNotesToInscriptions($dryRun);
        $this->linkConduiteToInscriptions($dryRun);
        $this->linkSanctionsToInscriptions($dryRun);

        $this->newLine();
        $this->info('Migration completed successfully.');

        return self::SUCCESS;
    }

    private function migrateElevesToInscriptions(bool $dryRun): void
    {
        $this->info('Step 1: Ensuring all active students have inscriptions...');

        $anneeScolaire = AnneeScolaire::where('est_active', true)->first();

        if (! $anneeScolaire) {
            $this->warn('No active school year found. Skipping student inscription migration.');

            return;
        }

        $eleves = Eleve::whereNotNull('school_id')
            ->whereNotNull('niveau_id')
            ->where('statut_global', 'actif')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($eleves as $eleve) {
            $exists = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $eleve->id)
                ->where('annee_scolaire_id', $anneeScolaire->id)
                ->where('school_id', $eleve->school_id)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                $sequence = Inscription::withoutGlobalScopes()->count() + 1;
                $year = $anneeScolaire->date_debut?->format('Y') ?? date('Y');

                Inscription::withoutGlobalScopes()->create([
                    'numero_inscription' => sprintf('MIG%s%06d', $year, $sequence),
                    'eleve_id' => $eleve->id,
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'school_id' => $eleve->school_id,
                    'niveau_demande_id' => $eleve->niveau_id,
                    'type_inscription' => 'reinscription',
                    'statut' => 'valide',
                    'statut_academique' => 'en_cours',
                    'date_inscription' => $anneeScolaire->date_debut ?? now()->toDateString(),
                    'date_soumission' => now(),
                    'date_validation' => now(),
                    'est_redoublant' => $eleve->est_redoublant ?? false,
                    'created_by' => 1,
                    'valide_par' => 1,
                ]);
            }

            $created++;
        }

        $this->info("  -> Created: {$created}, Skipped (already exist): {$skipped}");
    }

    private function migratePivotToAffectations(bool $dryRun): void
    {
        $this->info('Step 2: Syncing eleve_class pivot to AffectationClasse...');

        $pivotEntries = DB::table('eleve_class')
            ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($pivotEntries as $pivot) {
            $inscription = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $pivot->eleve_id)
                ->latest()
                ->first();

            if (! $inscription) {
                $skipped++;

                continue;
            }

            $affectationExists = AffectationClasse::where('inscription_id', $inscription->id)
                ->where('classe_id', $pivot->classe_id)
                ->exists();

            if ($affectationExists) {
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                AffectationClasse::create([
                    'inscription_id' => $inscription->id,
                    'classe_id' => $pivot->classe_id,
                    'date_affectation' => $pivot->date_inscription ?? now(),
                    'est_active' => true,
                    'affecte_par' => 1,
                ]);
            }

            $created++;
        }

        $this->info("  -> Created: {$created}, Skipped: {$skipped}");
    }

    private function linkNotesToInscriptions(bool $dryRun): void
    {
        $this->info('Step 3: Linking notes to inscriptions...');

        $notes = Note::whereNull('inscription_id')
            ->with('evaluation:id,annee_scolaire_id')
            ->get();

        $linked = 0;
        $notFound = 0;

        foreach ($notes as $note) {
            $anneeScolaireId = $note->evaluation?->annee_scolaire_id;

            if (! $anneeScolaireId) {
                $notFound++;

                continue;
            }

            $inscription = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $note->eleve_id)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->first();

            if (! $inscription) {
                $notFound++;

                continue;
            }

            if (! $dryRun) {
                $note->update(['inscription_id' => $inscription->id]);
            }

            $linked++;
        }

        $this->info("  -> Linked: {$linked}, Not found: {$notFound}");
    }

    private function linkConduiteToInscriptions(bool $dryRun): void
    {
        $this->info('Step 4: Linking note_conduites to inscriptions...');

        $records = NoteConduite::whereNull('inscription_id')->get();
        $linked = 0;
        $notFound = 0;

        foreach ($records as $record) {
            $inscription = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $record->eleve_id)
                ->where('annee_scolaire_id', $record->annee_scolaire_id)
                ->first();

            if (! $inscription) {
                $notFound++;

                continue;
            }

            if (! $dryRun) {
                $record->update(['inscription_id' => $inscription->id]);
            }

            $linked++;
        }

        $this->info("  -> Linked: {$linked}, Not found: {$notFound}");
    }

    private function linkSanctionsToInscriptions(bool $dryRun): void
    {
        $this->info('Step 5: Linking sanction_eleves to inscriptions...');

        $records = SanctionEleve::whereNull('inscription_id')->get();
        $linked = 0;
        $notFound = 0;

        foreach ($records as $record) {
            $inscription = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $record->eleve_id)
                ->where('annee_scolaire_id', $record->annee_scolaire_id)
                ->first();

            if (! $inscription) {
                $notFound++;

                continue;
            }

            if (! $dryRun) {
                $record->update(['inscription_id' => $inscription->id]);
            }

            $linked++;
        }

        $this->info("  -> Linked: {$linked}, Not found: {$notFound}");
    }
}
