<?php

namespace App\Domain\Finances\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Élèves non à jour à une date donnée, tous échéanciers en cours confondus. */
class ElevesNonAJourExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    /** @param array $situation  résultat de EcheancierService::elevesNonAJour */
    public function __construct(private readonly array $situation) {}

    public function collection(): Collection
    {
        return collect($this->situation['eleves'])->map(fn (array $e) => [
            $e['matricule'],
            $e['nom'],
            $e['niveau'],
            $e['classe'],
            $e['frais'],
            implode(', ', array_map(fn ($x) => $x['libelle'] . ($x['date'] ? ' (' . $x['date']->format('d/m/Y') . ')' : ''), $e['echeances'])),
            $e['du'],
            $e['paye'],
            $e['retard'],
        ]);
    }

    public function headings(): array
    {
        $jour = $this->situation['jour']->format('d/m/Y');

        return [
            'Matricule', 'Élève', 'Niveau', 'Classe', 'Frais', 'Échéances non réglées',
            "Dû au {$jour}", "Encaissé au {$jour}", 'Retard',
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
        return 'Non à jour au ' . $this->situation['jour']->format('d-m-Y');
    }
}
