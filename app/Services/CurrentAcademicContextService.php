<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationAcademique;
use App\Models\Trimestre;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CurrentAcademicContextService
{
    public function getContext(): array
    {
        $annee = $this->resolveAnneeScolaire();
        $trimestre = $this->resolveTrimestre($annee);

        return [
            'annee_scolaire' => $annee,
            'trimestre' => $trimestre,
            'is_locked' => $trimestre?->verrouille ?? false,
            'source' => $this->resolveSource($annee, $trimestre),
        ];
    }

    public function resolveAnneeScolaire(): ?AnneeScolaire
    {
        $requestYearId = Context::get('annee_scolaire_id');
        if ($requestYearId !== null) {
            return AnneeScolaire::withoutGlobalScopes()->find((int) $requestYearId);
        }

        $config = ConfigurationAcademique::current();
        if ($config->current_annee_scolaire_id) {
            return AnneeScolaire::withoutGlobalScopes()->find($config->current_annee_scolaire_id);
        }

        $yearId = AcademicYearService::currentId();

        return $yearId ? AnneeScolaire::withoutGlobalScopes()->find($yearId) : null;
    }

    public function requireCurrentTrimestre(): Trimestre
    {
        $trimestre = $this->getContext()['trimestre'] ?? null;

        if (! $trimestre) {
            $annee = $this->resolveAnneeScolaire();
            $hasCalendar = $annee && Trimestre::query()
                ->where('annee_scolaire_id', $annee->id)
                ->whereNotNull('date_debut')
                ->whereNotNull('date_fin')
                ->exists();

            throw new UnprocessableEntityHttpException(
                $hasCalendar
                    ? 'Aucun trimestre ne couvre la date du jour pour cette année scolaire. Ajustez les dates dans Paramètres → Trimestres ou le calendrier.'
                    : 'Aucun trimestre actif ou consultable n\'est configuré pour cette année scolaire.'
            );
        }

        return $trimestre;
    }

    public function ensureCurrentTrimestreNotLocked(?Trimestre $trimestre = null): Trimestre
    {
        $resolved = $trimestre ?: $this->requireCurrentTrimestre();

        if ($resolved->verrouille) {
            throw new AccessDeniedHttpException(
                'Modification interdite : le trimestre courant est verrouillé.'
            );
        }

        return $resolved;
    }

    public function ensureEntityBelongsToCurrentTrimestre(Model $entity, string $column = 'trimestre_id'): Trimestre
    {
        $current = $this->requireCurrentTrimestre();
        $entityTrimestreId = (int) ($entity->{$column} ?? 0);

        if ($entityTrimestreId !== (int) $current->id) {
            throw new AccessDeniedHttpException(
                'Modification interdite : cette donnée n\'appartient pas au trimestre courant.'
            );
        }

        return $current;
    }

    public function syncCurrentTrimestre(): ?Trimestre
    {
        $annee = $this->resolveAnneeScolaire();

        return $this->resolveTrimestre($annee, true);
    }

    public function setCurrentAcademicYear(?int $anneeScolaireId): ConfigurationAcademique
    {
        $config = ConfigurationAcademique::current();
        $config->current_annee_scolaire_id = $anneeScolaireId;
        $config->current_trimestre_id = null;
        $config->save();

        if ($anneeScolaireId) {
            Context::add('annee_scolaire_id', $anneeScolaireId);
        }

        AcademicYearService::clearCache();

        return $config->fresh(['currentAnneeScolaire', 'currentTrimestre']);
    }

    protected function resolveTrimestre(?AnneeScolaire $annee, bool $sync = true): ?Trimestre
    {
        if (! $annee) {
            return null;
        }

        $config = ConfigurationAcademique::current()->loadMissing('currentTrimestre');
        $today = now()->toDateString();
        $resolved = null;
        $hasExplicitYearContext = Context::get('annee_scolaire_id') !== null;

        if (
            $config->current_trimestre_id &&
            $config->currentTrimestre &&
            (int) $config->currentTrimestre->annee_scolaire_id === (int) $annee->id &&
            $this->coversDate($config->currentTrimestre, $today)
        ) {
            $resolved = $config->currentTrimestre;
        }

        if (! $resolved) {
            $resolved = Trimestre::query()
                ->where('annee_scolaire_id', $annee->id)
                ->forDate($today)
                ->ordered()
                ->first();
        }

        $yearHasCalendar = Trimestre::query()
            ->where('annee_scolaire_id', $annee->id)
            ->whereNotNull('date_debut')
            ->whereNotNull('date_fin')
            ->exists();

        if (! $resolved && ! $yearHasCalendar) {
            $resolved = Trimestre::query()
                ->where('annee_scolaire_id', $annee->id)
                ->where('actif', true)
                ->ordered()
                ->first();
        }

        if (! $resolved && ! $yearHasCalendar) {
            $resolved = Trimestre::query()
                ->where('annee_scolaire_id', $annee->id)
                ->whereNotNull('date_fin')
                ->orderByDesc('date_fin')
                ->first();
        }

        if (! $resolved && ! $yearHasCalendar) {
            $resolved = Trimestre::query()
                ->where('annee_scolaire_id', $annee->id)
                ->ordered()
                ->first();
        }

        if ($resolved && $sync && ! $hasExplicitYearContext) {
            Trimestre::query()
                ->where('annee_scolaire_id', $annee->id)
                ->where('id', '!=', $resolved->id)
                ->update(['actif' => false]);

            $shouldBeActive = $this->coversDate($resolved, $today);
            if ((bool) $resolved->actif !== $shouldBeActive) {
                $resolved->forceFill(['actif' => $shouldBeActive])->save();
            }

            if (
                (int) ($config->current_annee_scolaire_id ?? 0) !== (int) $annee->id ||
                (int) ($config->current_trimestre_id ?? 0) !== (int) $resolved->id
            ) {
                $config->forceFill([
                    'current_annee_scolaire_id' => $annee->id,
                    'current_trimestre_id' => $resolved->id,
                ])->save();
            }
        }

        if ($resolved) {
            Context::add('trimestre_id', (int) $resolved->id);
        }

        return $resolved;
    }

    protected function coversDate(Trimestre $trimestre, string $date): bool
    {
        if (! $trimestre->date_debut || ! $trimestre->date_fin) {
            return false;
        }

        return $trimestre->date_debut->toDateString() <= $date
            && $trimestre->date_fin->toDateString() >= $date;
    }

    protected function resolveSource(?AnneeScolaire $annee, ?Trimestre $trimestre): ?string
    {
        if (! $annee || ! $trimestre) {
            return null;
        }

        $today = now()->toDateString();
        $config = ConfigurationAcademique::current();

        if (
            (int) ($config->current_trimestre_id ?? 0) === (int) $trimestre->id
            && (int) ($config->current_annee_scolaire_id ?? 0) === (int) $annee->id
            && $this->coversDate($trimestre, $today)
        ) {
            return 'config';
        }

        if ($this->coversDate($trimestre, $today)) {
            return 'date_resolution';
        }

        return 'fallback_resolution';
    }
}
