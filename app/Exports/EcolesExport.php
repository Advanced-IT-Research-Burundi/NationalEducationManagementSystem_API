<?php

namespace App\Exports;

use App\Models\Ecole;
use Maatwebsite\Excel\Concerns\FromCollection;

class EcolesExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return School::all();
    }
}
