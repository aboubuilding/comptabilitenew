<?php

namespace App\Domain\Finances\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RapportAnalytiqueGlobalSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $lignes) {}

    public function title(): string
    {
        return 'Rapport global';
    }

    public function headings(): array
    {
        return [
            'Code', 'Centre analytique', 'Recettes', 'Dépenses',
            'Résultat', 'Budget', 'Écart', 'Taux de réalisation (%)',
        ];
    }

    public function collection(): Collection
    {
        return $this->lignes->map(fn ($l) => [
            $l['code'],
            $l['libelle'],
            $l['recettes'],
            $l['depenses'],
            $l['resultat'],
            $l['budget'],
            $l['ecart_budget'],
            $l['taux_realisation'],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C81E3A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}