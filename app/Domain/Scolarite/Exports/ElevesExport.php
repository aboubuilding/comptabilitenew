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

class ElevesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $eleves) {}

    public function collection(): Collection { return $this->eleves; }

    public function headings(): array
    {
        return [
            'Matricule', 'Nom', 'Prénom', 'Sexe', 'Date de naissance', 'Âge',
            'Nationalité', 'Classe', 'Niveau', 'Cycle', 'Statut',
            'Montant dû (FCFA)', 'Montant encaissé (FCFA)',
        ];
    }

    public function map($e): array
    {
        $insc = $e->inscriptionActive;

        return [
            $e->matricule,
            $e->nom,
            $e->prenom,
            $e->sexe?->libelle() ?? '',
            $e->date_naissance?->format('d/m/Y') ?? '',
            $e->age,
            $e->nationalite->libelle ?? '',
            $insc->classe->libelle ?? 'Non affecté',
            $insc->niveau->libelle ?? '',
            $insc->cycle->libelle ?? '',
            $e->etat ? 'Actif' : 'Inactif',
            number_format($e->montant_du ?? 0, 0, ',', ' '),
            number_format($e->montant_encaisse ?? 0, 0, ',', ' '),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B0000']]],
        ];
    }

    public function title(): string { return 'Élèves'; }
}