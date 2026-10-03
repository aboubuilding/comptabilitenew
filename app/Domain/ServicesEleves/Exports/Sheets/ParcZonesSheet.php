<?php

namespace App\Domain\ServicesEleves\Exports\Sheets;

use App\Domain\ServicesEleves\Models\Zone;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParcZonesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private ?int $anneeId = null) {}

    public function title(): string
    {
        return 'Zones';
    }

    public function headings(): array
    {
        return [
            'Zone', 'Description', 'Chauffeur assigné',
            'Véhicule assigné', 'Abonnements actifs',
        ];
    }

    public function collection()
    {
        return Zone::query()
            ->with(['chauffeur:id,nom,prenom', 'voiture:id,plaque,marque'])
            ->when($this->anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->where('etat', 1)
            ->orderBy('libelle')
            ->get()
            ->map(fn (Zone $z) => [
                $z->libelle,
                $z->description,
                $z->chauffeur?->nom_complet,
                $z->voiture ? $z->voiture->plaque . ' — ' . $z->voiture->marque : null,
                $z->abonnements()->where('statut', 1)->count(),
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