<?php

namespace App\Domain\Finances\Exports;

use App\Domain\Finances\Repositories\ImputationAnalytiqueRepository;
use App\Domain\Finances\Types\SensAnalytique;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RapportAnalytiqueExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * @param Collection $lignes     Résultat de RapportAnalytiqueService::tableauDeBord()
     * @param array      $filtres    ['annee_id' => ..., 'date_debut' => ..., 'date_fin' => ...]
     * @param array      $totaux     ['recettes' => ..., 'depenses' => ..., 'resultat' => ..., 'budget' => ...]
     */
    public function __construct(
        private Collection $lignes,
        private array $filtres = [],
        private array $totaux = [],
    ) {}

    public function sheets(): array
    {
        $sheets = [
            new RapportAnalytiqueSyntheseSheet($this->totaux, $this->filtres),
            new RapportAnalytiqueGlobalSheet($this->lignes),
        ];

        // Une feuille par centre, avec le détail des imputations.
        foreach ($this->lignes as $ligne) {
            $sheets[] = new RapportAnalytiqueCentreSheet($ligne);
        }

        return $sheets;
    }
}