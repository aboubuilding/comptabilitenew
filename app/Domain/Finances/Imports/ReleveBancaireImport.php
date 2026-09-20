<?php

namespace App\Domain\Finances\Imports;

use App\Domain\Finances\DTO\LigneReleveData;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ReleveBancaireImport implements ToCollection, WithHeadingRow
{
    /** @var LigneReleveData[] */
    public array $lignes = [];

    /** @var int Nombre de lignes ignorées (malformées). */
    public int $ignorees = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $ligne = LigneReleveData::fromArray($row->toArray());
            if ($ligne) {
                $this->lignes[] = $ligne;
            } else {
                $this->ignorees++;
            }
        }
    }
}