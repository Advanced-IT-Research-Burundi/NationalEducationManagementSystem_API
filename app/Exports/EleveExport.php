<?php

namespace App\Exports;

use App\Models\Eleve;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EleveExport implements FromQuery, WithTitle, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly ?int    $schoolId = null,
        private readonly ?string $statut   = null,
        private readonly ?int    $niveauId = null,
    ) {}

    // ── Données ──────────────────────────────────────────────────────────────

    public function query(): Builder
    {
        return Eleve::query()
            ->with([
                'collineOrigine',   // FK → nom colline
                'ecoleOrigine',     // FK → nom école d'origine
                'ecole',            // FK → nom école actuelle
                'niveau',           // FK → nom niveau
            ])
            ->when($this->schoolId, fn ($q) => $q->where('school_id', $this->schoolId))
            ->when($this->statut,   fn ($q) => $q->where('statut_global', $this->statut))
            ->when($this->niveauId, fn ($q) => $q->where('niveau_id', $this->niveauId))
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    // ── En-têtes colonnes ────────────────────────────────────────────────────

    public function headings(): array
    {
        return [
            'matricule',
            'nom',
            'prenom',
            'sexe',
            'date_naissance',
            'lieu_naissance',
            'nationalite',
            'colline_origine',       // NOM (pas ID)
            'adresse',
            'nom_pere',
            'nom_mere',
            'nom_tuteur',
            'contact_tuteur',
            'est_orphelin',
            'a_handicap',
            'type_handicap',
            'ecole_origine',         // NOM (pas ID)
            'school_destination',    // NOM (pas ID)
            'niveau',                // NOM (pas ID)
            'statut_global',
        ];
    }

    // ── Mapping : modèle → ligne Excel ───────────────────────────────────────

    public function map($eleve): array
    {
        return [
            $eleve->matricule,
            $eleve->nom,
            $eleve->prenom,
            $eleve->sexe,
            $eleve->date_naissance?->format('Y-m-d'),
            $eleve->lieu_naissance,
            $eleve->nationalite,
            $eleve->collineOrigine?->nom ?? '',     // ← NOM au lieu de l'ID
            $eleve->adresse,
            $eleve->nom_pere,
            $eleve->nom_mere,
            $eleve->nom_tuteur,
            $eleve->contact_tuteur,
            $eleve->est_orphelin ? '1' : '0',
            $eleve->a_handicap   ? '1' : '0',
            $eleve->type_handicap,
            $eleve->ecoleOrigine?->nom ?? '',       // ← NOM au lieu de l'ID
            $eleve->ecole?->nom ?? '',              // ← NOM au lieu de l'ID
            $eleve->niveau?->nom ?? '',             // ← NOM au lieu de l'ID
            $eleve->statut_global,
        ];
    }

    // ── Styles ───────────────────────────────────────────────────────────────

    public function title(): string
    {
        return 'Eleves';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 20, 'C' => 22, 'D' => 8,  'E' => 16, 'F' => 20,
            'G' => 18, 'H' => 22, 'I' => 22, 'J' => 25, 'K' => 25,
            'L' => 25, 'M' => 20, 'N' => 14, 'O' => 14, 'P' => 18,
            'Q' => 25, 'R' => 25, 'S' => 20, 'T' => 16,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastRow  = $sheet->getHighestRow();
                $lastCol  = 'T';

                // FK columns (in green to show "name, not ID")
                foreach (['H', 'Q', 'R', 'S'] as $col) {
                    if ($lastRow > 1) {
                        $sheet->getStyle("{$col}2:{$col}{$lastRow}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FFF0']],
                        ]);
                    }
                }

                // Borders
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFBFBFBF'],
                    ]],
                ]);

                // Row height header
                $sheet->getRowDimension(1)->setRowHeight(24);

                // Freeze header
                $sheet->freezePane('A2');

                // Alternate row colors for readability
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F5']],
                        ]);
                    }
                }
            },
        ];
    }
}
