<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\BanqueData;
use App\Domain\Finances\Repositories\BanqueRepository;
use App\Domain\Finances\Services\BanqueService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreBanqueRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BanqueController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private BanqueRepository $banques,
        private BanqueService    $service,
    ) {
        $this->middleware('permission:banques.voir')->only(['index']);
        $this->middleware('permission:banques.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        return view('domain.finances.banques.index', [
            'banques' => $this->banques->listePaginee($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreBanqueRequest $request)
    {
        return $this->runAjax(
            fn () => ['banque' => $this->service->creer(BanqueData::fromRequest($request->validated()))],
            'Banque créée avec succès.'
        );
    }

    public function update(StoreBanqueRequest $request, int $banque)
    {
        return $this->runAjax(
            fn () => ['banque' => $this->service->modifier($banque, BanqueData::fromRequest($request->validated()))],
            'Banque mise à jour avec succès.'
        );
    }



public function show(int $banque)
{
    $banque = $this->banques->findAvecCheques($banque);

    // Compteurs + montants pour les cartes statistiques de la vue.
    $compteurs = [
        'emis'     => $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS)->count(),
        'encaisse' => $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::ENCAISSE)->count(),
        'rejete'   => $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::REJETE)->count(),
    ];

    $montants = [
        'emis'     => round((float) $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS)->sum('montant'), 2),
        'encaisse' => round((float) $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::ENCAISSE)->sum('montant'), 2),
        'rejete'   => round((float) $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::REJETE)->sum('montant'), 2),
    ];

    return view('domain.finances.banques.show', [
        'banque'    => $banque,
        'cheques'   => $banque->cheques,
        'compteurs' => $compteurs,
        'montants'  => $montants,
    ]);
}

    public function destroy(int $banque)
    {
        return $this->runAjax(
            function () use ($banque) {
                $this->service->supprimer($banque);
                return null;
            },
            'Banque désactivée.'
        );
    }


    public function exportPdf(int $banque)
{
    $banque = $this->banques->findAvecCheques($banque);

    // Mêmes compteurs que la vue show — factorisable si tu veux,
    // mais on garde simple : ce sont des agrégats sur une collection
    // déjà chargée.
    $compteurs = [
        'emis'     => $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS)->count(),
        'encaisse' => $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::ENCAISSE)->count(),
        'rejete'   => $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::REJETE)->count(),
    ];

    $montants = [
        'emis'     => round((float) $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS)->sum('montant'), 2),
        'encaisse' => round((float) $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::ENCAISSE)->sum('montant'), 2),
        'rejete'   => round((float) $banque->cheques->where('statut', \App\Domain\Finances\Types\ChequeStatut::REJETE)->sum('montant'), 2),
    ];

    $pdf = Pdf::loadView('domain.finances.banques.print', [
        'banque'    => $banque,
        'cheques'   => $banque->cheques,
        'compteurs' => $compteurs,
        'montants'  => $montants,
        'genereLe'  => now(),
        'generePar' => auth()->user(),
    ])
    ->setPaper('A4', 'portrait')
    ->setOptions([
        'defaultFont'          => 'DejaVu Sans', // support UTF-8 + accents
        'isRemoteEnabled'      => true,          // si tu ajoutes un logo via URL
        'isHtml5ParserEnabled' => true,
        'dpi'                  => 130,
        'margin_top'           => 15,
        'margin_bottom'        => 15,
        'margin_left'          => 15,
        'margin_right'         => 15,
    ]);

    $filename = sprintf(
        'journal_banque_%s_%s.pdf',
        \Illuminate\Support\Str::slug($banque->nom),
        now()->format('Ymd_His')
    );

    return $pdf->download($filename);
}
}