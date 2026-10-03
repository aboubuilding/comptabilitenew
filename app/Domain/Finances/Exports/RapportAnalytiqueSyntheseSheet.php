<?php

namespace App\Domain\Finances\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RapportAnalytiqueSyntheseSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private array $totaux,
        private array $filtres = [],
    ) {}

    public function title(): string
    {
        return 'Synthèse';
    }

    public function array(): array
    {
        $periode = '—';
        if (! empty($this->filtres['date_debut']) || ! empty($this->filtres['date_fin'])) {
            $periode = sprintf(
                'Du %s au %s',
                $this->filtres['date_debut'] ?? '—',
                $this->filtres['date_fin'] ?? '—'
            );
        }

        return [
            ['ÉCOLE INTERNATIONALE MARIAM — RAPPORT ANALYTIQUE'],
            [''],
            ['Période analysée', $periode],
            ['Généré le', now()->format('d/m/Y à H:i')],
            [''],
            ['INDICATEUR', 'VALEUR'],
            ['Recettes cumulées', $this->totaux['recettes'] ?? 0],
            ['Dépenses cumulées', $this->totaux['depenses'] ?? 0],
            ['Résultat net', $this->totaux['resultat'] ?? 0],
            ['Budget prévisionnel', $this->totaux['budget'] ?? 0],
            ['Taux de réalisation (%)', $this->totaux['taux_realisation'] ?? 0],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Titre principal
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'C81E3A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            // En-tête tableau
            6 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']],
            ],
        ];
    }
}