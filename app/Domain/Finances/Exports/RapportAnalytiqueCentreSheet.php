<?php

namespace App\Domain\Finances\Exports;

use App\Domain\Finances\Repositories\ImputationAnalytiqueRepository;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RapportAnalytiqueCentreSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private array $ligne) {}

    public function title(): string
    {
        // Titre Excel max 31 caractères, sans caractères interdits
        $title = sprintf('%s - %s', $this->ligne['code'], $this->ligne['libelle']);
        return mb_substr(str_replace(['/', '\\', '?', '*', ':', '[', ']'], ' ', $title), 0, 31);
    }

    public function array(): array
    {
        $l = $this->ligne;

        // 1) Bandeau d'identification + récap du centre
        $rows = [
            ["CENTRE ANALYTIQUE : {$l['code']} — {$l['libelle']}"],
            [''],
            ['Indicateur', 'Valeur'],
            ['Recettes', $l['recettes']],
            ['Dépenses', $l['depenses']],
            ['Résultat', $l['resultat']],
            ['Budget', $l['budget']],
            ['Écart budget', $l['ecart_budget']],
            ['Taux de réalisation (%)', $l['taux_realisation']],
            [''],
            ['DÉTAIL DES IMPUTATIONS'],
            [''],
        ];

        // 2) Détail des imputations de ce centre
        $imputations = app(ImputationAnalytiqueRepository::class)
            ->activeQuery()
            ->with(['imputable', 'auteur:id,name'])
            ->where('centre_analytique_id', $l['centre_id'])
            ->orderByDesc('created_at')
            ->get();

        $rows[] = ['Date', 'Sens', 'Montant', 'Pourcentage', 'Source', 'Auteur'];

        if ($imputations->isEmpty()) {
            $rows[] = ['—', '—', '—', '—', 'Aucune imputation', '—'];
        } else {
            foreach ($imputations as $imp) {
                $rows[] = [
                    optional($imp->created_at)->format('d/m/Y H:i'),
                    $imp->sens?->label(),
                    $imp->montant,
                    $imp->pourcentage,
                    class_basename($imp->imputable_type) . ' #' . $imp->imputable_id,
                    $imp->auteur?->name,
                ];
            }
        }

        // 3) Totaux de la section détail
        $rows[] = [''];
        $rows[] = ['TOTAL', '', $imputations->sum('montant'), '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'C81E3A']],
            ],
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']],
            ],
            11 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'B8860B']],
            ],
            13 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}