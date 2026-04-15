<?php

namespace App\Imports;

use App\Models\Colline;
use App\Models\Eleve;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class EleveImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    WithChunkReading,
    WithBatchInserts
{
    /**
     * Ligne de démarrage des données (on saute les lignes 1-6 du template)
     * WithHeadingRow utilise la ligne définie dans headingRow().
     */
    public function headingRow(): int
    {
        return 3; // La ligne 3 du template contient les vrais en-têtes
    }

    // ── Caches FK pour éviter N+1 ────────────────────────────────────────────

    /** @var array<string, int|null> */
    private array $collineCache = [];

    /** @var array<string, int|null> */
    private array $ecoleCache = [];

    // ── Méthodes de résolution FK ────────────────────────────────────────────

    /**
     * Résoudre le nom d'une colline en ID.
     * La recherche est insensible à la casse et aux espaces superflus.
     */
    private function resolveCollineId(?string $nom): ?int
    {
        if (empty($nom)) {
            return null;
        }
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->collineCache)) {
            $colline = Colline::whereRaw('LOWER(TRIM(nom)) = ?', [$key])->first();
            $this->collineCache[$key] = $colline?->id;
        }
        return $this->collineCache[$key];
    }

    /**
     * Résoudre le nom d'une école en ID.
     */
    private function resolveSchoolId(?string $nom): ?int
    {
        if (empty($nom)) {
            return null;
        }
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->ecoleCache)) {
            $ecole = School::whereRaw('LOWER(TRIM(nom)) = ?', [$key])->first();
            $this->ecoleCache[$key] = $ecole?->id;
        }
        return $this->ecoleCache[$key];
    }

    // ── Traitement principal ─────────────────────────────────────────────────

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Nettoyer les clés (le template a des émojis et étoiles dans les headers)
            $data = $this->normalizeRow($row->toArray());

            // Résolution des clés étrangères (nom → ID)
            $collineOrigineId     = $this->resolveCollineId($data['colline_origine'] ?? null);
            $ecoleOrigineId       = $this->resolveSchoolId($data['ecole_origine'] ?? null);
            $schoolDestinationId  = $this->resolveSchoolId($data['school_destination'] ?? null);

            Eleve::updateOrCreate(
                ['matricule' => trim($data['matricule'])],
                [
                    'nom'               => trim($data['nom']),
                    'prenom'            => trim($data['prenom']),
                    'sexe'              => strtoupper(trim($data['sexe'])),
                    'date_naissance'    => $data['date_naissance'],
                    'lieu_naissance'    => $data['lieu_naissance'] ?? null,
                    'nationalite'       => $data['nationalite'] ?? 'Burundaise',
                    'colline_origine_id'=> $collineOrigineId,
                    'adresse'           => $data['adresse'] ?? null,
                    'nom_pere'          => $data['nom_pere'] ?? null,
                    'nom_mere'          => $data['nom_mere'] ?? null,
                    'nom_tuteur'        => $data['nom_tuteur'] ?? null,
                    'contact_tuteur'    => $data['contact_tuteur'] ?? null,
                    'est_orphelin'      => (bool)($data['est_orphelin'] ?? false),
                    'a_handicap'        => (bool)($data['a_handicap'] ?? false),
                    'type_handicap'     => $data['type_handicap'] ?? null,
                    'ecole_origine_id'  => $ecoleOrigineId,
                    'school_id'         => $schoolDestinationId,
                    'statut_global'     => 'actif',
                    'created_by'        => Auth::id(),
                ]
            );
        }
    }

    // ── Normalisation des clés de la ligne ───────────────────────────────────

    /**
     * Le template contient des headers avec émojis/étoiles (ex: "matricule *", "colline_origine 🟢").
     * Cette méthode nettoie les clés pour obtenir des noms de colonnes simples.
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            // Supprimer les émojis, *, 🟢, ○, ⬤ et espaces superflus de la clé
            $cleanKey = preg_replace('/[^\w]/u', '_', $key);       // tout non-alphanum → _
            $cleanKey = preg_replace('/_+/', '_', $cleanKey);       // doublons de _
            $cleanKey = trim($cleanKey, '_');
            $cleanKey = mb_strtolower($cleanKey);
            $normalized[$cleanKey] = $value;
        }
        return $normalized;
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function rules(): array
    {
        return [
            '*.matricule_'     => ['required', 'string', 'max:20'],
            '*.nom_'           => ['required', 'string', 'max:100'],
            '*.prenom_'        => ['required', 'string', 'max:100'],
            '*.sexe_'          => ['required', 'in:M,F,m,f'],
            '*.date_naissance_'=> ['required', 'date_format:Y-m-d'],
            '*.lieu_naissance_'=> ['required', 'string', 'max:150'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.matricule_.required'      => 'Le matricule est obligatoire (ligne :attribute)',
            '*.nom_.required'            => 'Le nom est obligatoire (ligne :attribute)',
            '*.prenom_.required'         => 'Le prénom est obligatoire (ligne :attribute)',
            '*.sexe_.in'                 => 'Le sexe doit être M ou F (ligne :attribute)',
            '*.date_naissance_.date_format' => 'La date doit être au format YYYY-MM-DD (ligne :attribute)',
        ];
    }

    // ── Performance ──────────────────────────────────────────────────────────

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 100;
    }
}
