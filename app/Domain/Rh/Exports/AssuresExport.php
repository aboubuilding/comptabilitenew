<?php

namespace App\Domain\Rh\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel des assurés du personnel.
 */
class AssuresExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $assures
    ) {}

    public function collection(): Collection
    {
        return $this->assures;
    }

    public function headings(): array
    {
        return [
            'Employé',
            'Assureur',
            'N° de police',
            'Date de souscription',
            'Prélèvement mensuel',
            'Statut',
        ];
    }

    public function map($assure): array
    {
        return [
            (string) ($assure->employe?->nom_prenom ?? ''),
            $assure->assurance->nom_assureur ?? '',
            $assure->assurance->police_assurance ?? '',
            $assure->date_souscription?->format('d/m/Y') ?? '',
            number_format((float) $assure->prelevement_mensuel, 2, ',', ' '),
            ucfirst($assure->statut ?? 'actif'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B0000']]],
        ];
    }

    public function title(): string
    {
        return 'Assurances personnel';
    }
}