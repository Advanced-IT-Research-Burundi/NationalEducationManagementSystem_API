<?php

namespace App\Exports;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Niveau;
use App\Models\Province;
use App\Models\School;
use App\Models\Zone;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EleveTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $references = new EleveReferencesSheet();

        return [
            new EleveImportSheet($references),
            $references,
            new EleveInstructionsSheet(),
        ];
    }
}

class EleveImportSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    private const FIRST_DATA_ROW = 7;
    private const LAST_DATA_ROW = 506;

    public function __construct(private readonly EleveReferencesSheet $references)
    {
    }

    public function title(): string
    {
        return 'Import_Eleves';
    }

    public function array(): array
    {
        return [
            ['TEMPLATE D\'IMPORT DES ELEVES'],
            ['Saisir les eleves uniquement de la ligne 7 a la ligne 506. Les lignes 1 a 6 sont ignorees. L import s arrete apres 4 lignes consecutives sans matricule.'],
            [
                'matricule *',
                'nom *',
                'prenom *',
                'sexe *',
                'date_naissance *',
                'lieu_naissance *',
                'nationalite',
                'school_destination *',
                'niveau_scolaire *',
                'province_origine',
                'commune_origine',
                'zone_origine',
                'colline_origine',
                'adresse',
                'nom_pere',
                'nom_mere',
                'nom_tuteur',
                'contact_tuteur',
                'est_orphelin',
                'a_handicap',
                'type_handicap',
            ],
            [
                'OBLIGATOIRE',
                'OBLIGATOIRE',
                'OBLIGATOIRE',
                'OBLIGATOIRE',
                'OBLIGATOIRE',
                'OBLIGATOIRE',
                'OPTIONNEL',
                'NOM -> schools.id',
                'NOM/CODE -> niveaux_scolaires.id',
                'NOM -> provinces.id',
                'NOM -> communes.id',
                'NOM -> zones.id',
                'NOM -> collines.id',
                'OPTIONNEL',
                'OPTIONNEL',
                'OPTIONNEL',
                'OPTIONNEL',
                'OPTIONNEL',
                '0/1',
                '0/1',
                'SI HANDICAP=1',
            ],
            [
                'EL-2026-001',
                'NDAYISHIMIYE',
                'Jean Pierre',
                'M',
                '2010-05-15',
                'Gitega',
                'Burundaise',
                $this->references->firstSchoolName(),
                $this->references->firstNiveauName(),
                $this->references->firstProvinceName(),
                $this->references->firstCommuneName(),
                $this->references->firstZoneName(),
                $this->references->firstCollineName(),
                'Quartier Rohero',
                'NDAYISHIMIYE Emmanuel',
                'NIYONKURU Marie',
                'HAKIZIMANA Paul',
                '+257 79 123 456',
                '0',
                '0',
                '',
            ],
            [
                'Unique, max 20',
                'Nom de famille',
                'Prenom(s)',
                'M ou F',
                'YYYY-MM-DD',
                'Ville ou commune',
                'Defaut: Burundaise',
                'Nom exact de l\'ecole',
                'Nom ou code exact du niveau',
                'Nom exact',
                'Nom exact',
                'Nom exact',
                'Nom exact',
                'Adresse complete',
                'Nom complet',
                'Nom complet',
                'Si different des parents',
                'Telephone',
                '0=Non, 1=Oui',
                '0=Non, 1=Oui',
                'Obligatoire si a_handicap=1',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 20,
            'C' => 22,
            'D' => 10,
            'E' => 18,
            'F' => 20,
            'G' => 18,
            'H' => 32,
            'I' => 24,
            'J' => 24,
            'K' => 24,
            'L' => 24,
            'M' => 24,
            'N' => 28,
            'O' => 26,
            'P' => 26,
            'Q' => 26,
            'R' => 20,
            'S' => 14,
            'T' => 14,
            'U' => 20,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'U';

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 14, 'name' => 'Arial'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['argb' => 'FF595959'], 'size' => 9, 'name' => 'Arial'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(24);

                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10, 'name' => 'Arial'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(34);

                $requiredCols = ['A', 'B', 'C', 'D', 'E', 'F', 'H', 'I'];
                $foreignCols = ['H', 'I', 'J', 'K', 'L', 'M'];
                $optionalCols = ['G', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'];

                foreach ($requiredCols as $col) {
                    $sheet->getStyle("{$col}4")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFC00000'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4D6']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    ]);
                }

                foreach ($foreignCols as $col) {
                    $sheet->getStyle("{$col}4")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF375623'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    ]);
                }

                foreach ($optionalCols as $col) {
                    $sheet->getStyle("{$col}4")->applyFromArray([
                        'font' => ['color' => ['argb' => 'FF2E75B6'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEBF3FB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    ]);
                }

                $sheet->getStyle("A4:{$lastCol}6")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['argb' => 'FF7F7F7F'], 'size' => 9, 'name' => 'Arial'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $sheet->getStyle("A6:{$lastCol}6")->applyFromArray([
                    'font' => ['color' => ['argb' => 'FF595959'], 'size' => 8, 'name' => 'Arial'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(34);

                $sheet->getStyle('H7:M506')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FFF0']],
                ]);
                $sheet->getStyle("A7:{$lastCol}506")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                    'font' => ['size' => 10, 'name' => 'Arial'],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A3:{$lastCol}3");

                $this->applyListValidation($sheet, 'D', '"M,F"', false, 'Utiliser M ou F.');
                $this->applyListValidation($sheet, 'H', $this->references->validationFormula('schools'), false, 'Choisir un nom depuis REFERENCES.');
                $this->applyListValidation($sheet, 'I', $this->references->validationFormula('niveaux'), false, 'Choisir un nom ou code depuis REFERENCES.');
                $this->applyListValidation($sheet, 'J', $this->references->validationFormula('provinces'), true, 'Choisir un nom depuis REFERENCES.');
                $this->applyListValidation($sheet, 'K', $this->references->validationFormula('communes'), true, 'Choisir un nom depuis REFERENCES.');
                $this->applyListValidation($sheet, 'L', $this->references->validationFormula('zones'), true, 'Choisir un nom depuis REFERENCES.');
                $this->applyListValidation($sheet, 'M', $this->references->validationFormula('collines'), true, 'Choisir un nom depuis REFERENCES.');
                $this->applyListValidation($sheet, 'S', '"0,1"', true, '0 = Non, 1 = Oui.');
                $this->applyListValidation($sheet, 'T', '"0,1"', true, '0 = Non, 1 = Oui.');
            },
        ];
    }

    private function applyListValidation(Worksheet $sheet, string $column, string $formula, bool $allowBlank, string $prompt): void
    {
        if ($formula === '') {
            return;
        }

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank($allowBlank)
            ->setShowDropDown(false)
            ->setFormula1($formula)
            ->setErrorTitle('Valeur invalide')
            ->setError('La valeur saisie n existe pas dans la liste autorisee.')
            ->setPrompt($prompt);

        for ($row = self::FIRST_DATA_ROW; $row <= self::LAST_DATA_ROW; $row++) {
            $sheet->setDataValidation("{$column}{$row}", clone $validation);
        }
    }
}

class EleveReferencesSheet implements FromArray, WithTitle, WithStyles, WithEvents
{
    private array $schools;
    private array $niveaux;
    private array $provinces;
    private array $communes;
    private array $zones;
    private array $collines;

    public function __construct()
    {
        $this->schools = School::withoutGlobalScopes()
            ->select('id', 'name', 'code_ecole')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->niveaux = Niveau::select('id', 'nom', 'code')
            ->orderBy('ordre')
            ->orderBy('nom')
            ->get()
            ->toArray();

        $this->provinces = Province::select('id', 'name')->orderBy('name')->get()->toArray();
        $this->communes = Commune::with('province:id,name')->select('id', 'name', 'province_id')->orderBy('name')->get()->toArray();
        $this->zones = Zone::with('commune:id,name')->select('id', 'name', 'commune_id')->orderBy('name')->get()->toArray();
        $this->collines = Colline::with('zone:id,name')->select('id', 'name', 'zone_id')->orderBy('name')->get()->toArray();
    }

    public function title(): string
    {
        return 'REFERENCES';
    }

    public function array(): array
    {
        $rows = [[
            'SEXE',
            'BOOLEEN',
            'STATUT_GLOBAL',
            'schools.name',
            'niveaux_scolaires.nom',
            'provinces.name',
            'communes.name',
            'zones.name',
            'collines.name',
            'schools.code_ecole',
            'niveaux_scolaires.code',
            'commune.province',
            'zone.commune',
            'colline.zone',
        ]];

        $max = max(
            5,
            count($this->schools),
            count($this->niveaux),
            count($this->provinces),
            count($this->communes),
            count($this->zones),
            count($this->collines)
        );

        $sexes = ['M', 'F'];
        $booleans = ['0', '1'];
        $statuts = ['actif', 'inactif', 'transfere', 'abandonne', 'decede'];

        for ($i = 0; $i < $max; $i++) {
            $rows[] = [
                $sexes[$i] ?? '',
                $booleans[$i] ?? '',
                $statuts[$i] ?? '',
                $this->schools[$i]['name'] ?? '',
                $this->niveaux[$i]['nom'] ?? '',
                $this->provinces[$i]['name'] ?? '',
                $this->communes[$i]['name'] ?? '',
                $this->zones[$i]['name'] ?? '',
                $this->collines[$i]['name'] ?? '',
                $this->schools[$i]['code_ecole'] ?? '',
                $this->niveaux[$i]['code'] ?? '',
                $this->communes[$i]['province']['name'] ?? '',
                $this->zones[$i]['commune']['name'] ?? '',
                $this->collines[$i]['zone']['name'] ?? '',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF375623']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:N1');

                foreach (range('A', 'N') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(in_array($column, ['D', 'E', 'F', 'G', 'H', 'I'], true) ? 28 : 18);
                }
            },
        ];
    }

    public function validationFormula(string $list): string
    {
        $map = [
            'schools' => ['D', count($this->schools)],
            'niveaux' => ['E', count($this->niveaux)],
            'provinces' => ['F', count($this->provinces)],
            'communes' => ['G', count($this->communes)],
            'zones' => ['H', count($this->zones)],
            'collines' => ['I', count($this->collines)],
        ];

        if (! isset($map[$list]) || $map[$list][1] < 1) {
            return '';
        }

        [$column, $count] = $map[$list];
        $lastRow = $count + 1;

        return "REFERENCES!\${$column}\$2:\${$column}\${$lastRow}";
    }

    public function firstSchoolName(): string
    {
        return $this->schools[0]['name'] ?? 'Nom exact de l ecole';
    }

    public function firstNiveauName(): string
    {
        return $this->niveaux[0]['nom'] ?? 'Nom exact du niveau';
    }

    public function firstProvinceName(): string
    {
        return $this->provinces[0]['name'] ?? '';
    }

    public function firstCommuneName(): string
    {
        return $this->communes[0]['name'] ?? '';
    }

    public function firstZoneName(): string
    {
        return $this->zones[0]['name'] ?? '';
    }

    public function firstCollineName(): string
    {
        return $this->collines[0]['name'] ?? '';
    }
}

class EleveInstructionsSheet implements FromArray, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'INSTRUCTIONS';
    }

    public function array(): array
    {
        return [
            ['INSTRUCTIONS D\'UTILISATION', ''],
            ['', ''],
            ['ETAPE 1', 'Remplir les donnees uniquement de la ligne 7 a la ligne 506 de l onglet Import_Eleves.'],
            ['ETAPE 2', 'Pour school_destination, niveau_scolaire, province_origine, commune_origine, zone_origine et colline_origine, ecrire le NOM exact depuis REFERENCES.'],
            ['ETAPE 3', 'Ne pas saisir les ID des tables etrangeres dans Import_Eleves. Les ID sont affiches uniquement pour controle administratif.'],
            ['ETAPE 4', 'Pour niveau_scolaire, le backend accepte le nom ou le code exact du niveau.'],
            ['ETAPE 5', 'Pour les dates, utiliser YYYY-MM-DD. Les formats JJ/MM/AAAA et JJ-MM-AAAA sont aussi normalises a l import.'],
            ['ETAPE 6', 'est_orphelin et a_handicap: 0 = Non, 1 = Oui. Si a_handicap = 1, renseigner type_handicap.'],
            ['ETAPE 7', 'Sauvegarder en .xlsx puis uploader via l interface.'],
            ['', ''],
            ['VALIDATION', 'L import refuse les noms de reference inconnus ou ambigus et indique la ligne concernee.'],
            ['VALIDATION', 'L import refuse les doublons de matricule dans le meme fichier.'],
            ['VALIDATION', 'Si une ecole a une liste de niveaux configuree, le niveau importe doit appartenir a cette ecole.'],
            ['IMPORTANT', 'Ne pas modifier les entetes des colonnes de la ligne 3.'],
            ['IMPORTANT', 'Le fichier est traite en bloc: si une ligne est invalide, aucune insertion n est effectuee.'],
            ['LIMITE', 'Le modele prepare 500 lignes de saisie, de la ligne 7 a la ligne 506. La lecture s arrete apres 4 lignes consecutives sans matricule.'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(95);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 13],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
