<?php

namespace App\Domain\ServicesEleves\Exports\Sheets;

use App\Domain\ServicesEleves\Models\Voiture;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParcVoituresSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private ?int $anneeId = null) {}

    public function title(): string
    {
        return 'Véhicules';
    }

    public function headings(): array
    {
        return [
            'Plaque', 'Marque', 'Modèle', 'Année fab.', 'Places',
            'N° châssis', 'Date achat', 'Prix achat', 'Fournisseur',
            'Kilométrage', 'Statut', 'Chauffeur actuel',
        ];
    }

    public function collection()
    {
        return Voiture::query()
            ->when($this->anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->where('etat', 1)
            ->orderBy('plaque')
            ->get()
            ->map(fn (Voiture $v) => [
                $v->plaque,
                $v->marque,
                $v->modele,
                $v->annee_fabrication,
                $v->nombre_place,
                $v->numero_chassis,
                optional($v->date_achat)->format('d/m/Y'),
                $v->prix_achat,
                $v->fournisseur,
                (float) $v->kilometrage_actuel,
                $v->statut?->label(),
                $v->chauffeurActuel()?->nom_complet,
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}