<?php

namespace App\Domain\ServicesEleves\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ParcTransportExport implements WithMultipleSheets
{
    public function __construct(
        private ?int $anneeId = null,
    ) {}

    public function sheets(): array
    {
        return [
            new Sheets\ParcSyntheseSheet($this->anneeId),
            new Sheets\ParcVoituresSheet($this->anneeId),
            new Sheets\ParcChauffeursSheet($this->anneeId),
            new Sheets\ParcZonesSheet($this->anneeId),
            new Sheets\ParcAbonnementsSheet($this->anneeId),
        ];
    }
}