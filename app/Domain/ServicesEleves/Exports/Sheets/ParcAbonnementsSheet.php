<?php

namespace App\Domain\ServicesEleves\Exports\Sheets;

use App\Domain\ServicesEleves\Models\AbonnementBus;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParcAbonnementsSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private ?int $anneeId = null) {}

    public function title(): string
    {
        return 'Abonnements bus';
    }

    public function headings(): array
    {
        return [
            'Élève', 'Matricule', 'Classe', 'Zone', 'Véhicule', 'Ligne',
            'Sens', 'Position GPS', 'Mensuel', 'Nb mois', 'Total dû',
            'Statut', 'Début', 'Fin',
        ];
    }

    public function collection()
    {
        return AbonnementBus::query()
            ->with([
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'zone:id,libelle',
                'voiture:id,plaque',
                'ligne:id,libelle',
            ])
            ->when($this->anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->where('etat', 1)
            ->orderByDesc('id')
            ->get()
            ->map(fn (AbonnementBus $a) => [
                trim(($a->inscription?->eleve?->nom ?? '') . ' ' . ($a->inscription?->eleve?->prenom ?? '')),
                $a->inscription?->eleve?->matricule,
                $a->inscription?->classe?->libelle,
                $a->zone?->libelle,
                $a->voiture?->plaque,
                $a->ligne?->libelle,
                $a->sens?->label(),
                $a->aUnePosition() ? "{$a->latitude}, {$a->longitude}" : '',
                (float) $a->montant_mensuel,
                $a->nombre_mois,
                (float) $a->montant_total_du,
                $a->statut == 1 ? 'Actif' : 'Abandonné',
                optional($a->date_debut)->format('d/m/Y'),
                optional($a->date_fin)->format('d/m/Y'),
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