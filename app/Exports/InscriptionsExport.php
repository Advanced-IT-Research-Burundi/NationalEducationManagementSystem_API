<?php

namespace App\Exports;

use App\Models\InscriptionEleve;
use Maatwebsite\Excel\Concerns\FromCollection;

class InscriptionsExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return InscriptionEleve::all();
    }
}
