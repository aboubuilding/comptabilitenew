<?php

namespace App\Domain\ServicesEleves\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class ParcSyntheseSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private ?int $anneeId = null) {}

    public function title(): string
    {
        return 'Synthèse';
    }

    public function array(): array
    {
        $anneeId = $this->anneeId;

        $nbVoitures     = DB::table('voitures')->where('etat', 1)->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))->count();
        $nbChauffeurs   = DB::table('chauffeurs')->where('etat', 1)->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))->count();
        $nbZones        = DB::table('zones')->where('etat', 1)->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))->count();
        $nbAbonnements  = DB::table('abonnements_bus')->where('statut', 1)->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))->count();

        $revenusMensuels = DB::table('abonnements_bus')
            ->where('statut', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->sum('montant_mensuel');

        $revenusAnnuels = DB::table('abonnements_bus')
            ->where('statut', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->sum('montant_total_du');

        $coutsCarburant = DB::table('carburant_vehicules')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->sum('montant_total');

        $coutsEntretien = DB::table('entretiens_vehicules')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->sum('cout');

        $coutsAssurance = DB::table('assurances_vehicules')->sum('prime');

        return [
            ['ÉCOLE INTERNATIONALE MARIAM — PARC DE TRANSPORT SCOLAIRE'],
            [''],
            ['Généré le', now()->format('d/m/Y à H:i')],
            [''],
            ['PARC', ''],
            ['Véhicules', $nbVoitures],
            ['Chauffeurs', $nbChauffeurs],
            ['Zones de transport', $nbZones],
            [''],
            ['ABONNEMENTS', ''],
            ['Abonnements actifs', $nbAbonnements],
            ['Revenus mensuels', round((float) $revenusMensuels, 2)],
            ['Revenus annuels attendus', round((float) $revenusAnnuels, 2)],
            [''],
            ['COÛTS EXPLOITATION', ''],
            ['Carburant', round((float) $coutsCarburant, 2)],
            ['Entretiens', round((float) $coutsEntretien, 2)],
            ['Assurances', round((float) $coutsAssurance, 2)],
            ['TOTAL COÛTS', round((float) $coutsCarburant + (float) $coutsEntretien + (float) $coutsAssurance, 2)],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'C81E3A']]],
            5 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']]],
            10 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                   'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']]],
            15 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                   'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2D3A']]],
            19 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '7A0F22']]],
        ];
    }
}