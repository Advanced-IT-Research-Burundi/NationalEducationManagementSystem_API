<?php

namespace App\Exports;

use App\Models\Colline;
use App\Models\School;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class EleveTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new EleveImportSheet(),
            new EleveListesSheet(),
            new EleveInstructionsSheet(),
        ];
    }
}

// ============================================================
// SHEET 1 : Template de saisie principal
// ============================================================
class EleveImportSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    public function title(): string
    {
        return 'Import_Eleves';
    }

    public function array(): array
    {
        return [
            // Row 1: Title (merged via AfterSheet event)
            ['📋 TEMPLATE D\'IMPORT DES ÉLÈVES - Système de Gestion Scolaire'],

            // Row 2: Legend
            ['🔴 Obligatoire  |  🟢 Clé étrangère → écrire le NOM (l\'ID sera trouvé auto)  |  🔵 Optionnel  |  ⚠️ Voir onglet LISTES'],

            // Row 3: Column headers
            [
                'matricule *', 'nom *', 'prenom *', 'sexe *', 'date_naissance *', 'lieu_naissance *',
                'nationalite', 'colline_origine 🟢', 'adresse', 'nom_pere', 'nom_mere',
                'nom_tuteur', 'contact_tuteur', 'est_orphelin', 'a_handicap', 'type_handicap',
                'ecole_origine 🟢', 'school_destination 🟢',
            ],

            // Row 4: Type indicators
            [
                '⬤ OBLIGATOIRE', '⬤ OBLIGATOIRE', '⬤ OBLIGATOIRE', '⬤ OBLIGATOIRE', '⬤ OBLIGATOIRE', '⬤ OBLIGATOIRE',
                '○ OPTIONNEL', '◆ NOM → ID AUTO', '○ OPTIONNEL', '○ OPTIONNEL', '○ OPTIONNEL',
                '○ OPTIONNEL', '○ OPTIONNEL', '○ OPTIONNEL', '○ OPTIONNEL', '○ OPTIONNEL',
                '◆ NOM → ID AUTO', '◆ NOM → ID AUTO',
            ],

            // Row 5: Examples
            [
                'EL-2024-001', 'NDAYISHIMIYE', 'Jean Pierre', 'M', '2010-05-15', 'Gitega',
                'Burundaise', 'Kiganda', 'Quartier Rohero', 'NDAYISHIMIYE Emmanuel', 'NIYONKURU Marie',
                'HAKIZIMANA Paul', '+257 79 123 456', '0', '0', 'Visuel',
                'Lycée Kiganda', 'École Rohero',
            ],

            // Row 6: Notes
            [
                'Unique, libre', 'Nom famille', 'Prénom(s)', 'M ou F', 'YYYY-MM-DD', 'Ville/Commune',
                'Défaut: Burundaise', 'Nom colline exacte', 'Adresse complète', 'Nom complet', 'Nom complet',
                'Si diff. parents', 'Téléphone', '0=Non, 1=Oui', '0=Non, 1=Oui', 'Si handicap=1',
                'Nom école exacte', 'Nom école exacte',
            ],
        ];
        // Rows 7-106 : laissées vides pour la saisie (gérées via AfterSheet)
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 20, 'C' => 22, 'D' => 10, 'E' => 18, 'F' => 20,
            'G' => 18, 'H' => 22, 'I' => 22, 'J' => 25, 'K' => 25,
            'L' => 25, 'M' => 20, 'N' => 14, 'O' => 14, 'P' => 18,
            'Q' => 25, 'R' => 25,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return []; // Styles appliqués via AfterSheet pour plus de contrôle
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'R';

                // ── Row 1: Title ────────────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 13, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                // ── Row 2: Legend ────────────────────────────────────────────
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font'      => ['italic' => true, 'color' => ['argb' => 'FF595959'], 'size' => 9, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // ── Row 3: Headers ───────────────────────────────────────────
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(28);

                // ── Row 4: Type indicators (coloured per type) ───────────────
                $requiredCols = ['A', 'B', 'C', 'D', 'E', 'F'];
                $fkCols       = ['H', 'Q', 'R'];
                $optionalCols = ['G', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];

                foreach ($requiredCols as $col) {
                    $sheet->getStyle("{$col}4")->applyFromArray([
                        'font'  => ['bold' => true, 'color' => ['argb' => 'FFC00000'], 'size' => 8, 'name' => 'Arial'],
                        'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4D6']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                foreach ($fkCols as $col) {
                    $sheet->getStyle("{$col}4")->applyFromArray([
                        'font'  => ['bold' => true, 'color' => ['argb' => 'FF375623'], 'size' => 8, 'name' => 'Arial'],
                        'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                foreach ($optionalCols as $col) {
                    $sheet->getStyle("{$col}4")->applyFromArray([
                        'font'  => ['color' => ['argb' => 'FF2E75B6'], 'size' => 8, 'name' => 'Arial'],
                        'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEBF3FB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                ]);

                // ── Row 5: Examples ──────────────────────────────────────────
                $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
                    'font'      => ['italic' => true, 'color' => ['argb' => 'FF7F7F7F'], 'size' => 9, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                ]);

                // ── Row 6: Notes ─────────────────────────────────────────────
                $sheet->getStyle("A6:{$lastCol}6")->applyFromArray([
                    'font'      => ['color' => ['argb' => 'FF595959'], 'size' => 8, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(32);

                // ── Data rows 7-106: light green bg for FK cols ──────────────
                foreach (['H', 'Q', 'R'] as $col) {
                    $sheet->getStyle("{$col}7:{$col}106")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FFF0']],
                    ]);
                }
                // Borders for all data rows
                $sheet->getStyle("A7:{$lastCol}106")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]],
                    'font'    => ['size' => 10, 'name' => 'Arial'],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // ── Freeze panes ─────────────────────────────────────────────
                $sheet->freezePane('A7');

                // ── Data Validation: sexe ────────────────────────────────────
                $dvSexe = new DataValidation();
                $dvSexe->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(false)
                    ->setShowDropDown(false)
                    ->setFormula1('"M,F"')
                    ->setError('Utiliser M (Masculin) ou F (Féminin)')
                    ->setErrorTitle('Valeur invalide')
                    ->setPrompt('Entrer M ou F');
                for ($row = 7; $row <= 106; $row++) {
                    $sheet->setDataValidation("D{$row}", clone $dvSexe);
                }

                // ── Data Validation: booléens ────────────────────────────────
                $dvBool = new DataValidation();
                $dvBool->setType(DataValidation::TYPE_LIST)
                    ->setAllowBlank(true)
                    ->setShowDropDown(false)
                    ->setFormula1('"0,1"')
                    ->setPrompt('0 = Non, 1 = Oui');
                for ($row = 7; $row <= 106; $row++) {
                    $sheet->setDataValidation("N{$row}", clone $dvBool);
                    $sheet->setDataValidation("O{$row}", clone $dvBool);
                }
            },
        ];
    }
}

// ============================================================
// SHEET 2 : Listes de référence (données dynamiques depuis la BD)
// ============================================================
class EleveListesSheet implements FromArray, WithTitle, WithStyles, WithEvents
{
    private array $collines;
    private array $ecoles;

    public function __construct()
    {
        // Charger depuis la BD pour guider l'utilisateur
        $this->collines = Colline::select('id', 'name')->orderBy('name')->limit(200)->get()->toArray();
        $this->ecoles   = School::select('id', 'name')->orderBy('name')->limit(200)->get()->toArray();
    }

    public function title(): string
    {
        return 'LISTES';
    }

    public function array(): array
    {
        $rows = [];

        // Section header
        $rows[] = ['📖 LISTES DE RÉFÉRENCE - Valeurs acceptées'];
        $rows[] = [''];

        // Sexe
        $rows[] = ['SEXE', 'Description'];
        $rows[] = ['M', 'Masculin (Garçon)'];
        $rows[] = ['F', 'Féminin (Fille)'];
        $rows[] = [''];

        // Statut global
        $rows[] = ['STATUT_GLOBAL', 'Description'];
        $rows[] = ['actif',      'Élève actuellement actif'];
        $rows[] = ['inactif',    'Élève inactif temporairement'];
        $rows[] = ['transfere',  'Élève transféré vers une autre école'];
        $rows[] = ['abandonne',  'Élève ayant abandonné'];
        $rows[] = ['decede',     'Élève décédé'];
        $rows[] = [''];

        // Collines
        $rows[] = ['🟢 COLLINES DISPONIBLES (colline_origine)', 'ID (info)', 'Province/Commune'];
        foreach ($this->collines as $colline) {
            $rows[] = [$colline['name'], $colline['id'], ''];
        }
        if (empty($this->collines)) {
            $rows[] = ['⚠️ Aucune colline en base - contacter l\'administrateur', '', ''];
        }
        $rows[] = [''];

        // Écoles
        $rows[] = ['🟢 ÉCOLES DISPONIBLES (ecole_origine / school_destination)', 'ID (info)'];
        foreach ($this->ecoles as $ecole) {
            $rows[] = [$ecole['name'], $ecole['id']];
        }
        if (empty($this->ecoles)) {
            $rows[] = ['⚠️ Aucune école en base - contacter l\'administrateur', ''];
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

                $sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->mergeCells('A1:D1');

                $sheet->getColumnDimension('A')->setWidth(40);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(25);
            },
        ];
    }
}

// ============================================================
// SHEET 3 : Instructions
// ============================================================
class EleveInstructionsSheet implements FromArray, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'INSTRUCTIONS';
    }

    public function array(): array
    {
        return [
            ['📌 INSTRUCTIONS D\'UTILISATION', ''],
            ['', ''],
            ['ÉTAPE 1', 'Remplir les données à partir de la ligne 7 de l\'onglet Import_Eleves'],
            ['ÉTAPE 2', 'Colonnes en vert 🟢 (colline_origine, ecole_origine, school_destination) :'],
            ['', '   → Écrire le NOM exact (pas l\'ID). Exemple : "Kiganda" au lieu de "42"'],
            ['', '   → La casse n\'est pas importante (recherche insensible aux majuscules)'],
            ['', '   → Les noms disponibles sont listés dans l\'onglet LISTES'],
            ['ÉTAPE 3', 'Pour "sexe" : M ou F uniquement (voir onglet LISTES)'],
            ['ÉTAPE 4', 'Pour les dates : format YYYY-MM-DD (exemple : 2010-05-15)'],
            ['ÉTAPE 5', 'est_orphelin et a_handicap : 0 = Non, 1 = Oui'],
            ['ÉTAPE 6', 'Sauvegarder en .xlsx ou .csv puis uploader via l\'interface'],
            ['', ''],
            ['⚠️ NE PAS', 'Modifier les entêtes de colonnes (lignes 3 à 6)'],
            ['⚠️ NE PAS', 'Supprimer les onglets LISTES et INSTRUCTIONS'],
            ['⚠️ NE PAS', 'Laisser le champ matricule vide ou en doublon'],
            ['✅ INFO', 'Les erreurs sont retournées ligne par ligne après validation'],
            ['✅ INFO', 'L\'import est transactionnel : tout réussit ou tout est annulé'],
            ['✅ INFO', 'Maximum 500 élèves par fichier pour éviter les timeouts'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(85);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 13],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
