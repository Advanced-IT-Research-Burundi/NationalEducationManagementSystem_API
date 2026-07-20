<?php

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Trimestre;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createEvaluationRulesContext(): array
{
    $annee = AnneeScolaire::create([
        'code' => '2026-2027',
        'libelle' => 'Année scolaire 2026-2027',
        'date_debut' => '2026-01-01',
        'date_fin' => '2026-12-31',
        'est_active' => true,
    ]);

    AcademicYearService::setCurrent($annee->id);

    $trimestre = Trimestre::create([
        'annee_scolaire_id' => $annee->id,
        'nom' => '2e Trimestre',
        'date_debut' => '2026-06-01',
        'date_fin' => '2026-08-31',
        'actif' => true,
        'verrouille' => false,
    ]);

    $niveau = Niveau::withoutGlobalScopes()->create([
        'nom' => '7ème Année Fondamentale',
        'code' => '7F-EVAL-RULE',
        'ordre' => 10,
        'type_id' => null,
        'cycle_id' => null,
        'description' => null,
        'actif' => true,
    ]);

    $classe = Classe::withoutGlobalScopes()->create([
        'nom' => '7ème A',
        'code' => '7A-EVAL-RULE',
        'niveau_id' => $niveau->id,
        'school_id' => null,
        'annee_scolaire_id' => $annee->id,
        'section_id' => null,
        'local' => null,
        'capacite' => null,
        'salle' => null,
        'statut' => Classe::STATUS_ACTIVE,
    ]);

    $matiere = Matiere::create([
        'nom' => 'Mathématiques',
        'code' => 'MAT-EVAL-RULE',
        'actif' => true,
        'ponderation_tj' => 40,
        'ponderation_competence' => 20,
        'ponderation_examen' => 20,
        'credit_heures' => 2,
    ]);

    $user = User::factory()->create([
        'is_super_admin' => true,
    ]);

    return compact('annee', 'trimestre', 'niveau', 'classe', 'matiere', 'user');
}

function evaluationRulesPayload(array $context, string $typeEvaluation): array
{
    return [
        'classe_id' => $context['classe']->id,
        'cours_id' => $context['matiere']->id,
        'type_evaluation' => $typeEvaluation,
        'date_passation' => '2026-07-18',
        'note_maximale' => $typeEvaluation === 'Examen'
            ? 20
            : ($typeEvaluation === 'Compétence' ? 20 : 10),
    ];
}

it('rejects a second evaluation of the same non-TJ type in the same trimester', function (): void {
    $context = createEvaluationRulesContext();

    $this->actingAs($context['user'], 'sanctum')
        ->postJson('/api/academic/evaluations', evaluationRulesPayload($context, 'Examen'))
        ->assertCreated();

    $this->actingAs($context['user'], 'sanctum')
        ->postJson('/api/academic/evaluations', evaluationRulesPayload($context, 'Examen'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type_evaluation']);
});

it('allows multiple TJ evaluations in the same trimester', function (): void {
    $context = createEvaluationRulesContext();

    $first = $this->actingAs($context['user'], 'sanctum')
        ->postJson('/api/academic/evaluations', evaluationRulesPayload($context, 'TJ'));

    $first->assertCreated();

    $second = $this->actingAs($context['user'], 'sanctum')
        ->postJson('/api/academic/evaluations', evaluationRulesPayload($context, 'TJ'));

    $second->assertCreated();

    expect(\App\Models\Evaluation::query()->count())->toBe(2);
});
