<?php

namespace App\Domain\Finances\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Souscriptions de l'année scolaire active, telles que filtrées à l'écran. */
class SouscriptionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public const ETATS = ['solde' => 'Réglée', 'partiel' => 'Partiellement réglée', 'impaye' => 'Impayée'];

    public function __construct(private readonly Collection $souscriptions, private readonly string $titre) {}

    public function collection(): Collection
    {
        return $this->souscriptions;
    }

    public function headings(): array
    {
        return [
            'Matricule', 'Élève', 'Niveau', 'Classe', 'Type de souscription', 'Souscription', 'Date',
            'Montant attendu', 'Montant payé', 'Reste à payer', 'Statut de paiement',
        ];
    }

    public function map($s): array
    {
        $eleve = $s->inscription?->eleve;

        return [
            $eleve?->matricule,
            trim(($eleve?->nom ?? '') . ' ' . ($eleve?->prenom ?? '')),
            $s->inscription?->niveau?->libelle,
            $s->inscription?->classe?->libelle,
            $s->type_paiement?->label(),
            $s->intitule,
            optional($s->date_souscription)->format('d/m/Y'),
            $s->montant_net,
            (float) $s->montant_paye,
            $s->reste,
            self::ETATS[$s->etat_paiement],
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
        return mb_substr($this->titre, 0, 31);
    }
}
