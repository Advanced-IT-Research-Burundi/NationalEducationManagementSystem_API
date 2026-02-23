<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Batiment;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Examen;
use App\Models\Province;
use App\Models\Salle;
use App\Models\School;
use App\Models\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MatriculeService
{
    /**
     * Génère un matricule unique pour le modèle donné.
     */
    public function generate(Model $model): string
    {
        $class = get_class($model);
        
        return match ($class) {
            School::class => $this->generateSchoolMatricule($model),
            Eleve::class => $this->generateEleveMatricule($model),
            Enseignant::class => $this->generateEnseignantMatricule($model),
            Classe::class => $this->generateClasseMatricule($model),
            Examen::class => $this->generateExamenMatricule($model),
            Salle::class => $this->generateSalleMatricule($model),
            Batiment::class => $this->generateBatimentMatricule($model),
            Section::class => $this->generateSectionMatricule($model),
            default => throw new \InvalidArgumentException("Le modèle {$class} n'est pas pris en charge par le MatriculeService."),
        };
    }

    private function generateSchoolMatricule(School $school): string
    {
        $province = Province::find($school->province_id);
        $prefix = $province ? strtoupper(substr($province->name, 0, 3)) : 'NAT';
        
        $last = School::where('province_id', $school->province_id)
            ->orderBy('code_ecole', 'desc')
            ->first();

        $sequence = $this->extractSequence($last?->code_ecole, 4);
        
        return "ECO-{$prefix}-" . Str::padLeft($sequence + 1, 4, '0');
    }

    private function generateEleveMatricule(Model $eleve): string
    {
        $anneeFull = AnneeScolaire::current()?->code ?? date('Y');
        $annee = substr($anneeFull, -4); // Prend les 4 derniers caractères (ex: 2026)
        $schoolId = $eleve->school_id;
        $school = School::find($schoolId);
        // On prend les 5 derniers caractères du code école et on nettoie
        $schoolCode = $school ? substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($school->code_ecole)), -5) : '00000';

        $last = \App\Models\Eleve::where('school_id', $schoolId)
            ->orderBy('matricule', 'desc')
            ->first();

        $sequence = $this->extractSequence($last?->matricule, 5);

        return "ELV-{$annee}-{$schoolCode}-" . Str::padLeft($sequence + 1, 5, '0');
    }

 private function generateEnseignantMatricule(): string
{
    $annee = date('Y');

    $last = Enseignant::where('matricule', 'like', "ENS-{$annee}-%")
        ->orderBy('matricule', 'desc')
        ->first();

    $lastNumber = 0;

    if ($last) {
        $parts = explode('-', $last->matricule);
        $lastNumber = (int) end($parts);
    }

    $nextNumber = $lastNumber + 1;

    return "ENS-{$annee}-" . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
}
    private function generateClasseMatricule(Model $classe): string
    {
        $annee = AnneeScolaire::current()?->code ?? date('Y');
        $niveau = $classe->niveau?->code ?? 'NIV';
        
        $last = \App\Models\Classe::where('school_id', $classe->school_id)
            ->where('annee_scolaire_id', $classe->annee_scolaire_id)
            ->orderBy('code', 'desc')
            ->first();

        $sequence = $this->extractSequence($last?->code, 3);

        return "CLS-{$annee}-" . strtoupper($niveau) . "-" . Str::padLeft($sequence + 1, 3, '0');
    }

    private function generateExamenMatricule(Model $examen): string
    {
        $annee = AnneeScolaire::current()?->code ?? date('Y');
        
        $last = \App\Models\Examen::where('annee_scolaire_id', $examen->annee_scolaire_id)
            ->orderBy('code', 'desc')
            ->first();

        $sequence = $this->extractSequence($last?->code, 3);

        return "EXA-{$annee}-" . Str::padLeft($sequence + 1, 3, '0');
    }

    private function generateSalleMatricule(Model $salle): string
    {
        $type = strtoupper(substr($salle->type ?? 'SAL', 0, 3));
        
        $last = \App\Models\Salle::where('batiment_id', $salle->batiment_id)
            ->orderBy('numero', 'desc')
            ->first();

        $sequence = $this->extractSequence($last?->numero, 3);

        return "SAL-{$type}-" . Str::padLeft($sequence + 1, 3, '0');
    }

    private function generateBatimentMatricule(Model $batiment): string
    {
        $type = strtoupper(substr($batiment->type ?? 'BAT', 0, 3));
        
        $last = \App\Models\Batiment::where('school_id', $batiment->school_id)
            ->orderBy('nom', 'desc')
            ->first();

        $sequence = $this->extractSequence($last?->nom, 3);

        return "BAT-{$type}-" . Str::padLeft($sequence + 1, 3, '0');
    }


    private function generateSectionMatricule(Model $section): string
{
    $last = Section::orderBy('code', 'desc')
        ->first();

    $sequence = $this->extractSequence($last?->code, 3);

    return "SEC-" . Str::padLeft($sequence + 1, 3, '0');
}

    /**
     * Extrait la séquence numérique à la fin d'un matricule.
     */
    private function extractSequence(?string $lastMatricule, int $length): int
    {
        if (!$lastMatricule) {
            return 0;
        }

        $parts = explode('-', $lastMatricule);
        $lastPart = end($parts);
        
        if (is_numeric($lastPart)) {
            return (int) $lastPart;
        }

        // Si le format a changé ou n'est pas standard, on essaie de trouver les derniers chiffres
        if (preg_match('/(\d+)$/', $lastMatricule, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
