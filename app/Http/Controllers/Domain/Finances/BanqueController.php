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
        $this->middleware('permission:banques.voir')->only(['show', 'exportPdf', 'paiement']);
    }

    public function index(Request $request)
    {
        // Les chèques comptés sont ceux de l'année de l'en-tête
        $filters = $request->only(['search']) + ['annee_id' => app(\App\Support\AnneeScolaireContext::class)->id()];

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



    /**
     * Fragment HTML (fenêtre de la fiche banque) : contenu d'un paiement réglé
     * par un chèque de cette banque — ses lignes, ses totaux, son chèque.
     * Seuls les paiements adossés à un chèque de la banque sont consultables ici.
     */
    public function paiement(int $banque, int $paiement)
    {
        $cheque = \App\Domain\Finances\Models\Cheque::query()
            ->where('banque_id', $banque)->where('etat', 1)
            ->where(fn ($q) => $q->where('paiement_id', $paiement)
                ->orWhereIn('id', \App\Domain\Finances\Models\Paiement::whereKey($paiement)->select('cheque_id')))
            ->first();
        abort_unless($cheque, 404, "Ce paiement n'est pas réglé par un chèque de cette banque.");

        return view('domain.finances.banques.partials.paiement',
            app(\App\Domain\Finances\Repositories\ChequeRepository::class)->contenuPaiement($cheque, $paiement));
    }

    /** Onglets de la fiche banque : statut de chèque correspondant. */
    private const ONGLETS = [
        'attente'  => \App\Domain\Finances\Types\ChequeStatut::EMIS,
        'encaisse' => \App\Domain\Finances\Types\ChequeStatut::ENCAISSE,
        'rejete'   => \App\Domain\Finances\Types\ChequeStatut::REJETE,
        'tous'     => null,
    ];

    /**
     * Fiche d'une banque : les paiements réglés par chèque sur cette banque,
     * chacun avec son chèque adossé, par onglet (en attente par défaut), et ce
     * qui a été réellement perçu / ce qui reste, sur l'année de l'en-tête.
     */
    public function show(Request $request, int $banque)
    {
        $banque = $this->banques->findOrFail($banque);
        $anneeId = app(\App\Support\AnneeScolaireContext::class)->id();
        $onglet = array_key_exists((string) $request->input('onglet'), self::ONGLETS) ? $request->input('onglet') : 'attente';

        return view('domain.finances.banques.show', [
            'banque'    => $banque,
            'onglet'    => $onglet,
            'annee'     => \App\Domain\Scolarite\Models\Annee::find($anneeId),
            'situation' => $this->banques->situationCheques($banque->id, $anneeId),
            'filters'   => $request->only(['search']),
            'lignes'    => $this->banques->chequesEtPaiements($banque->id, $anneeId, [
                'statut' => self::ONGLETS[$onglet]?->value,
                'search' => $request->input('search'),
            ]),
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