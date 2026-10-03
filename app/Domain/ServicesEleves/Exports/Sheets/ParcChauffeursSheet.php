<?php

namespace App\Domain\ServicesEleves\Exports\Sheets;

use App\Domain\ServicesEleves\Models\Chauffeur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParcChauffeursSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private ?int $anneeId = null) {}

    public function title(): string
    {
        return 'Chauffeurs';
    }

    public function headings(): array
    {
        return [
            'Nom', 'Prénom', 'Permis', 'Validité permis', 'Téléphone',
            'Email', 'Adresse', 'Statut',
        ];
    }

    public function collection()
    {
        return Chauffeur::query()
            ->when($this->anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->where('etat', 1)
            ->orderBy('nom')
            ->get()
            ->map(fn (Chauffeur $c) => [
                $c->nom,
                $c->prenom,
                $c->permis_conduire,
                optional($c->date_validite_permis)->format('d/m/Y'),
                $c->telephone,
                $c->email,
                $c->adresse,
                $c->isActif() ? 'Actif' : 'Inactif',
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