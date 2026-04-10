<?php

namespace App\Exports;

use App\Models\Colline;
use App\Models\School;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;

class EleveTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new EleveMainSheet(),
            new EleveReferenceSheet(),
        ];
    }
}

class EleveMainSheet implements WithTitle, WithHeadings, ShouldAutoSize, WithEvents
{
    public function title(): string
    {
        return 'Import Students';
    }

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
            'colline_origine',
            'adresse',
            'nom_pere',
            'nom_mere',
            'contact_tuteur',
            'nom_tuteur',
            'est_orphelin',
            'a_handicap',
            'type_handicap',
            'ecole_origine'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                for ($i = 2; $i <= 500; $i++) {
                    // Gender Dropdown (M/F)
                    $genderVal = $sheet->getCell("D$i")->getDataValidation();
                    $genderVal->setType(DataValidation::TYPE_LIST);
                    $genderVal->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $genderVal->setAllowBlank(false);
                    $genderVal->setShowInputMessage(true);
                    $genderVal->setShowErrorMessage(true);
                    $genderVal->setShowDropDown(true);
                    $genderVal->setFormula1('"M,F"');

                    // Colline Dropdown
                    $collineVal = $sheet->getCell("H$i")->getDataValidation();
                    $collineVal->setType(DataValidation::TYPE_LIST);
                    $collineVal->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $collineVal->setAllowBlank(false);
                    $collineVal->setShowInputMessage(true);
                    $collineVal->setShowErrorMessage(true);
                    $collineVal->setShowDropDown(true);
                    $collineVal->setFormula1('=collines');

                    // Yes/No Dropdowns
                    $yesNoVal = '"Oui,Non"';
                    $sheet->getCell("N$i")->getDataValidation()->setType(DataValidation::TYPE_LIST)->setFormula1($yesNoVal);
                    $sheet->getCell("O$i")->getDataValidation()->setType(DataValidation::TYPE_LIST)->setFormula1($yesNoVal);

                    // School Dropdown
                    $schoolVal = $sheet->getCell("Q$i")->getDataValidation();
                    $schoolVal->setType(DataValidation::TYPE_LIST);
                    $schoolVal->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $schoolVal->setAllowBlank(false);
                    $schoolVal->setShowInputMessage(true);
                    $schoolVal->setShowErrorMessage(true);
                    $schoolVal->setShowDropDown(true);
                    $schoolVal->setFormula1('=schools');
                }
            },
        ];
    }
}

class EleveReferenceSheet implements FromCollection, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'References';
    }

    public function collection()
    {
        return collect([['']]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Fill Collines in column A
                $collines = Colline::with(['zone', 'commune', 'province'])->get()->map(function($c) {
                    return "{$c->id} | {$c->name} - {$c->zone?->name} - {$c->commune?->name} - {$c->province?->name}";
                })->toArray();
                
                if (empty($collines)) {
                    $collines = ['Aucune colline disponible'];
                }

                foreach ($collines as $index => $label) {
                    $sheet->setCellValue('A' . ($index + 1), $label);
                }
                
                // Set Named Range for Collines
                $event->sheet->getParent()->addNamedRange(
                    new NamedRange('collines', $event->sheet->getDelegate(), 'A1:A' . max(1, count($collines)))
                );

                // Fill Schools in column B
                $schools = School::select('id', 'name')->get()->map(function($s) {
                    return "{$s->id} | {$s->name}";
                })->toArray();
                
                if (empty($schools)) {
                    $schools = ['Aucun établissement disponible'];
                }

                foreach ($schools as $index => $label) {
                    $sheet->setCellValue('B' . ($index + 1), $label);
                }
                
                // Set Named Range for Schools
                $event->sheet->getParent()->addNamedRange(
                    new NamedRange('schools', $event->sheet->getDelegate(), 'B1:B' . max(1, count($schools)))
                );

                // Hide this sheet
                $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
            },
        ];
    }
}
