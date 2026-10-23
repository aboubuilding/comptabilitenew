<?php

namespace App\Domain\Finances\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Élèves à jour et non à jour à chaque échéance d'un plan : une ligne par
 * élève et par échéance, telle que filtrée à l'écran (intervalle de dates, statut).
 */
class SuiviEcheancesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param array $suivi   résultat de EcheancierService::suiviEleves
     * @param ?string $statut  « a_jour », « en_retard » ou null (tous)
     */
    public function __construct(private readonly array $suivi, private readonly ?string $statut = null) {}

    public function collection(): Collection
    {
        $lignes = collect();

        foreach ($this->suivi['echeances'] as $echeance) {
            foreach (['en_retard' => 'Non à jour', 'a_jour' => 'À jour'] as $cle => $libelle) {
                if ($this->statut && $this->statut !== $cle) {
                    continue;
                }
                foreach ($echeance[$cle] as $eleve) {
                    $lignes->push([
                        $echeance['libelle'],
                        optional($echeance['date'])->format('d/m/Y'),
                        $libelle,
                        $eleve['matricule'],
                        $eleve['nom'],
                        $eleve['niveau'],
                        $eleve['classe'],
                        $eleve['frais'],
                        $eleve['prevu'],
                        $eleve['paye'],
                        $eleve['reste'],
                    ]);
                }
            }
        }

        return $lignes;
    }

    public function headings(): array
    {
        return [
            'Échéance', 'Date butoir', 'Situation', 'Matricule', 'Élève', 'Niveau', 'Classe', 'Frais',
            'Dû à cette échéance', 'Encaissé', 'Reste',
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
        return 'Suivi des échéances';
    }
}
