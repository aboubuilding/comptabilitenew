<?php

namespace App\Domain\Scolarite\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EspacesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $espaces) {}

    public function collection(): Collection { return $this->espaces; }

    public function headings(): array
    {
        return [
            'Nom famille',
            'Parent principal',
            'Téléphone',
            'WhatsApp',
            'Email',
            'Quartier',
            'Adresse',
            'Nombre d\'élèves',
            'Compte portail',
        ];
    }

    public function map($e): array
    {
        $principal = $e->parents->firstWhere('is_principal', 1) ?? $e->parents->first();
        $compte    = $e->comptes->firstWhere('etat', 1);

        return [
            $e->nom_famille,
            $principal?->nom_complet ?? '',
            $principal?->telephone ?? '',
            $principal?->whatsapp ?? '',
            $principal?->email ?? '',
            $principal?->quartier ?? '',
            $principal?->adresse ?? '',
            $e->eleves_count ?? 0,
            $compte ? ($compte->statut_compte?->libelle() ?? 'Actif') : 'Aucun',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B0000']]],
        ];
    }

    public function title(): string { return 'Espaces familiaux'; }
}