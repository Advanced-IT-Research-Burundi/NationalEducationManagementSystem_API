<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Note;
use App\Services\AcademicYearService;
use App\Services\CurrentAcademicContextService;
use App\Models\Role;
use App\Traits\ResolvesAnneeScolaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class NoteController extends Controller
{
    use \App\Traits\ResolvesAnneeScolaire;

    public function __construct(
        private readonly CurrentAcademicContextService $academicContextService
    ) {}
    use ResolvesAnneeScolaire;

    /**
     * Consultation des notes avec filtres en cascade.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Note::class);

        $user = $request->user();
        $isParent = $user->hasRole(Role::PARENT);

        $query = Note::with([
            'eleve:id,nom,prenom,matricule',
            'evaluation' => function ($q) {
                $q->select('id', 'classe_id', 'cours_id', 'trimestre', 'trimestre_id', 'type_evaluation', 'note_maximale', 'annee_scolaire_id', 'date_passation');
            },
            'evaluation.classe:id,nom,code',
            'evaluation.cours:id,nom,code',
            'evaluation.anneeScolaire:id,libelle,code',
            'evaluation.trimestreModel',
        ]);

        if ($isParent) {
            $linkedIds = $user->linkedParentEleveIds();

            if ($linkedIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('eleve_id', $linkedIds);
            }

            if ($request->filled('eleve_id')) {
                $eleveId = $request->integer('eleve_id');
                abort_unless($user->isLinkedParentOfEleve($eleveId), 403);
            }
        }

        // Parents: scope by linked élève only (inscription classe/school may differ from evaluation).
        if (! $isParent) {
            if ($request->filled('classe_id')) {
                $query->whereHas('evaluation', function ($q) use ($request) {
                    $q->where('classe_id', $request->integer('classe_id'));
                });
            }

            if ($request->filled('school_id')) {
                $query->whereHas('evaluation.classe', function ($q) use ($request) {
                    $q->where('school_id', $request->integer('school_id'));
                });
            } elseif (auth()->check() && auth()->user()->school_id) {
                $query->whereHas('evaluation.classe', function ($q) {
                    $q->where('school_id', auth()->user()->school_id);
                });
            }
        }

        // Filter by cours (through evaluation)
        if ($request->filled('cours_id')) {
            $query->whereHas('evaluation', function ($q) use ($request) {
                $q->where('cours_id', $request->integer('cours_id'));
            });
        }

        if ($isParent) {
            $anneeScolaireId = $this->resolveActiveAnneeScolaireIdForParent();
            if ($anneeScolaireId) {
                $query->whereHas('evaluation', function ($q) use ($anneeScolaireId) {
                    $q->where('annee_scolaire_id', $anneeScolaireId);
                });
            }
            $this->applyParentNotesPeriodFilter($query, $request);
        } else {
            $anneeScolaireId = $this->resolveAnneeScolaireId($request);
            if ($anneeScolaireId) {
                $query->whereHas('evaluation', function ($q) use ($anneeScolaireId) {
                    $q->where('annee_scolaire_id', $anneeScolaireId);
                });
            }
            $currentTrimestre = $this->academicContextService->requireCurrentTrimestre();
            $query->whereHas('evaluation', function ($q) use ($currentTrimestre) {
                $q->matchingTrimestre($currentTrimestre);
            });
        }

        // Filter by section (through evaluation.cours)
        if ($request->filled('section_id')) {
            if (Schema::hasColumn('matieres', 'section_id')) {
                $query->whereHas('evaluation.cours', function ($q) use ($request) {
                    $q->where('section_id', $request->integer('section_id'));
                });
            }
        }

        // Filter by eleve
        if ($request->filled('eleve_id')) {
            $query->where('eleve_id', $request->integer('eleve_id'));
        }

        $notes = $query->latest()->paginate($request->get('per_page', 50));

        return response()->json($notes);
    }

    /**
     * Parents: année active uniquement — scope=all (tous trimestres) ou trimestre courant.
     */
    protected function applyParentNotesPeriodFilter($query, Request $request): void
    {
        if ($request->string('scope')->toString() === 'all') {
            return;
        }

        try {
            $currentTrimestre = $this->academicContextService->requireCurrentTrimestre();
            $query->whereHas('evaluation', function ($q) use ($currentTrimestre) {
                $q->matchingTrimestre($currentTrimestre);
            });
        } catch (UnprocessableEntityHttpException) {
            // Aucun trimestre courant configuré : toutes les notes de l'année active.
        }
    }

    protected function resolveActiveAnneeScolaireIdForParent(): ?int
    {
        $id = AnneeScolaire::withoutGlobalScopes()->active()->value('id')
            ?? AcademicYearService::currentId();

        if ($id) {
            AcademicYearService::setCurrent($id);
            Context::add('annee_scolaire_id', $id);
        }

        return $id;
    }
}
