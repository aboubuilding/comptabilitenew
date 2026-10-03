<?php

namespace App\Domain\Administration\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MessagesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $messages) {}

    public function collection(): Collection { return $this->messages; }

    public function headings(): array
    {
        return [
            'Sujet', 'Type', 'Modèle', 'Expéditeur',
            'Statut', 'Nb destinataires', 'Nb envoyés', 'Nb échecs',
            'Taux livraison (%)', 'Date envoi',
        ];
    }

    public function map($m): array
    {
        return [
            $m->sujet,
            $m->type,
            $m->modele->libelle ?? '—',
            $m->expediteur_nom ?? $m->expediteur->nom_complet ?? '—',
            $m->statut?->libelle() ?? '',
            $m->nb_destinataires,
            $m->nb_envoyes,
            $m->nb_echecs,
            $m->taux_livraison,
            $m->date_envoi?->format('d/m/Y H:i') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B0000']]],
        ];
    }

    public function title(): string { return 'Communications'; }
}