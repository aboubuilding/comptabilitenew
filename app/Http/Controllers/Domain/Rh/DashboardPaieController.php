<?php
// app/Http/Controllers/Domain/Rh/DashboardPaieController.php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\Repositories\PaieRepository;
use App\Domain\Rh\Repositories\PeriodeRepository;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;

class DashboardPaieController extends Controller
{
    public function __construct(
        private PaieRepository    $paies,
        private PeriodeRepository $periodes,
    ) {
        $this->middleware('permission:paie.voir');
    }

    public function index(Request $request)
    {
        $periodeId = $request->input('periode_id')
            ?? $this->periodes->listePourSelect()->first()?->id;

        $recap = $periodeId
            ? $this->paies->recapPeriode((int) $periodeId)
            : null;

        $enAttente = $this->paies->enAttenteRetrait(
            $periodeId ? (int) $periodeId : null
        );

        $evolution = $this->paies->evolutionMensuelle();

        $chartData = [
            'evolution' => [
                'labels' => array_column($evolution, 'mois'),
                'brut'   => array_column($evolution, 'brut'),
                'net'    => array_column($evolution, 'net'),
            ],
            'repartition' => [
                'labels' => ['Billetterie', 'Banque'],
                'values' => [
                    $recap['billette']['prevu'] ?? 0,
                    $recap['banque']['prevu']   ?? 0,
                ],
            ],
            'statuts' => [
                'labels' => ['Brouillons', 'Validés', 'Payés'],
                'values' => [
                    $recap['brouillons'] ?? 0,
                    $recap['valides']    ?? 0,
                    $recap['payes']      ?? 0,
                ],
            ],
        ];

        return view('domain.rh.paies.dashboard', [
            'recap'       => $recap,
            'enAttente'   => $enAttente,
            'periodes'    => $this->periodes->listePourSelect(),
            'periodeId'   => $periodeId,
            'chartData'   => $chartData,
            'evolution'   => $evolution,
        ]);
    }
}