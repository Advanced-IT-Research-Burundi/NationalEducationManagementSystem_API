<?php

namespace App\Imports;

use App\Models\Eleve;
use App\Models\Colline;
use App\Models\School;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Auth;

class EleveImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Extract ID from the format "ID | Label"
        $collineId = $this->extractId($row['colline_origine']);
        $ecoleOrigineId = $this->extractId($row['ecole_origine']);

        return new Eleve([
            'matricule'        => $row['matricule'],
            'nom'              => $row['nom'],
            'prenom'           => $row['prenom'],
            'sexe'             => $row['sexe'],
            'date_naissance'   => $this->transformDate($row['date_naissance']),
            'lieu_naissance'   => $row['lieu_naissance'],
            'nationalite'      => $row['nationalite'] ?? 'Burundaise',
            'colline_origine_id' => $collineId,
            'province_origine_id' => $collineId ? Colline::find($collineId)?->province_id : null,
            'commune_origine_id'  => $collineId ? Colline::find($collineId)?->commune_id : null,
            'zone_origine_id'     => $collineId ? Colline::find($collineId)?->zone_id : null,
            'adresse'          => $row['adresse'],
            'nom_pere'         => $row['nom_pere'],
            'nom_mere'         => $row['nom_mere'],
            'contact_tuteur'   => $row['contact_tuteur'],
            'nom_tuteur'       => $row['nom_tuteur'],
            'est_orphelin'     => $this->transformBoolean($row['est_orphelin']),
            'a_handicap'       => $this->transformBoolean($row['a_handicap']),
            'type_handicap'    => $row['type_handicap'],
            'ecole_origine_id' => $ecoleOrigineId,
            'school_id'        => Auth::user()->school_id, // Default to current user's school
            'created_by'       => Auth::id(),
            'statut_global'    => 'actif',
        ]);
    }

    private function extractId($value)
    {
        if (empty($value)) return null;
        if (is_numeric($value)) return (int)$value;
        
        $parts = explode('|', $value);
        return is_numeric(trim($parts[0])) ? (int)trim($parts[0]) : null;
    }

    private function transformDate($value)
    {
        if (empty($value)) return null;
        
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value);
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    private function transformBoolean($value)
    {
        if (empty($value)) return false;
        $val = strtolower(trim($value));
        return in_array($val, ['oui', 'yes', '1', 'true', 'vrai']);
    }

    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string', 'max:20', 'unique:eleves,matricule'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', 'in:M,F,m,f'],
            'date_naissance' => ['required'],
        ];
    }
}
