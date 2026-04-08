<?php

namespace App\Exports;

use App\Models\Evaluation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NotesTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        protected Collection $eleves,
        protected Evaluation $evaluation,
    ) {}

    public function headings(): array
    {
        return [
            'ID Élève',
            'Matricule',
            'Nom',
            'Note (/' . $this->evaluation->note_maximale . ')',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $index = 1;
        foreach ($this->eleves as $eleve) {
            $rows[] = [
                $eleve->id,
                $eleve->matricule,
                $eleve->prenom . ' ' . $eleve->nom,
                '', // Empty for the teacher to fill
            ];
            $index++;
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Notes';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
