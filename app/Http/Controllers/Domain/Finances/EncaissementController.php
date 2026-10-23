<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\PaiementRepository;
use App\Domain\Finances\Services\EncaissementService;
use App\Domain\Finances\Services\PaiementService;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\PaiementStatut;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 2.3 Encaissements — le caissier constate l'entrée en caisse des paiements
 * saisis par le comptable. Quatre onglets : tous les encaissements (filtrables
 * par caissier), les paiements en attente, mes encaissements (tout caissier),
 * et les encaissements de ma caisse ouverte (seulement si j'en ai une), d'où
 * l'on ouvre le journal de la caisse.
 */
class EncaissementController extends Controller
{
    use HandlesControllerErrors;

    private const ONGLETS = ['tous', 'attente', 'mes', 'caisse'];

    public function __construct(
        private PaiementRepository  $paiements,
        private CaisseRepository    $caisses,
        private EncaissementService $service,
        private PaiementService     $paiementService,
    ) {
        $this->middleware('permission:encaissements.voir')->only(['index', 'resume', 'recuPdf', 'recuPaiement']);
        $this->middleware('permission:encaissements.creer')->only(['nouveau', 'paiementsEnAttente', 'encaisserPaiement']);
        $this->middleware('permission:encaissements.annuler')->only(['annulerEncaissement']);
        $this->middleware('permission:paiements-en-attente.annuler')->only(['annulerPaiement']);
    }

    /**
     * Les encaissements portent sur l'année scolaire choisie dans l'en-tête
     * (cloisonnement par année) ; à défaut, l'année active.
     */
    private function anneeActive(): \App\Domain\Scolarite\Models\Annee
    {
        $id = app(AnneeScolaireContext::class)->id();

        return ($id ? \App\Domain\Scolarite\Models\Annee::find($id) : null)
            ?? app(\App\Domain\Scolarite\Services\AnneeService::class)->resolveActive();
    }

    public function index(Request $request)
    {
        $annee   = $this->anneeActive();
        $anneeId = (int) $annee->id;
        $user    = $request->user();

        $filtres = $request->only(['search', 'date_debut', 'date_fin', 'caisse_id', 'caissier_id']) + ['annee_id' => $anneeId];

        // Les paiements en attente couvrent toute la base : l'année choisie dans
        // l'en-tête n'est que le filtre de départ, « toutes » le lève.
        $parAnnee     = $this->paiements->paiementsEnAttenteParAnnee();
        $anneeAttente = $request->input('annee_attente', $anneeId);
        $anneeAttente = $anneeAttente === 'toutes' ? null : ((int) $anneeAttente ?: $anneeId);
        $recherche    = ['search' => $request->input('recherche_attente'), 'annee_id' => $anneeAttente];
        $retenues     = $anneeAttente ? $parAnnee->where('annee_id', $anneeAttente) : $parAnnee;

        $peutEncaisser = $user->can('encaissements.creer');
        $caisseOuverte = $this->caisses->findOuverteParCaissier((int) $user->id);

        // « Mes encaissements » : tout caissier ; « Caisse ouverte » : seulement s'il en a une
        $disponibles = array_values(array_filter(self::ONGLETS, fn ($o) => match ($o) {
            'mes'    => $peutEncaisser,
            'caisse' => $peutEncaisser && $caisseOuverte,
            default  => true,
        }));
        $demande = $request->input('onglet') === 'jour' ? 'caisse' : $request->input('onglet');   // ancien nom
        $onglet = in_array($demande, $disponibles, true)
            ? $demande
            : ($peutEncaisser ? ($caisseOuverte ? 'caisse' : 'mes') : 'tous');

        $duJour = $this->paiements->encaissementsDuJour((int) $user->id);

        // Mes encaissements : ceux du caissier connecté sur l'année, filtrables par dates
        $filtresMes = ['annee_id' => $anneeId, 'caissier_id' => (int) $user->id]
            + array_filter(['search' => $request->input('recherche_mes'), 'date_debut' => $request->input('mes_debut'), 'date_fin' => $request->input('mes_fin')]);
        // Caisse ouverte : tout ce qui y est entré, quelle que soit l'année du paiement
        $filtresCaisse = $caisseOuverte ? ['caisse_id' => (int) $caisseOuverte->id] : null;

        return view('domain.finances.encaissements.index', [
            'onglet'        => $onglet,
            'annee'         => $annee,
            'filtres'       => $filtres,
            'recherche'     => $recherche,
            'encaissements' => $this->paiements->encaissementsPagines($filtres),
            'caissiers'     => $this->paiements->caissiers($anneeId),
            'mesEncaissements' => $peutEncaisser ? $this->paiements->encaissementsPagines($filtresMes, 20, 'page_mes') : null,
            'totalMes'      => $peutEncaisser ? $this->paiements->totalEncaissements($filtresMes) : null,
            'filtresMes'    => $filtresMes,
            'encaissementsCaisse' => $filtresCaisse ? $this->paiements->encaissementsPagines($filtresCaisse, 50, 'page_caisse') : null,
            'totalCaisse'   => $filtresCaisse ? $this->paiements->totalEncaissements($filtresCaisse) : null,
            'enAttente'     => $this->paiements->paiementsEnAttentePagines($recherche),
            'duJour'        => $duJour,
            'totalJour'     => round((float) $duJour->sum('montant'), 2),
            'compteurAttente' => ['nb' => (int) $retenues->sum('nb'), 'montant' => round((float) $retenues->sum('montant'), 2)],
            'attenteParAnnee' => $parAnnee,
            'anneeAttente'  => $anneeAttente,
            'caisseOuverte' => $caisseOuverte,
            'caisses'       => $this->caisses->listePourSelect($anneeId),
        ]);
    }

    /** Écran « Nouvel encaissement » : choix du paiement en attente, détail, montant reçu. */
    public function nouveau(Request $request)
    {
        return view('domain.finances.encaissements.nouveau', [
            'annee'         => $this->anneeActive(),
            'caisseOuverte' => $this->caisses->findOuverteParCaissier((int) $request->user()->id),
        ]);
    }

    /** AJAX (liste avec recherche) : paiements en attente, par nom d'élève, matricule, payeur ou référence. */
    public function paiementsEnAttente(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $paiements = $this->paiements
            ->paiementsEnAttenteQuery(['search' => $q, 'annee_id' => (int) $this->anneeActive()->id])
            ->limit(20)
            ->get();

        return response()->json(['results' => $paiements->map(function ($p) {
            $eleve = $p->inscription?->eleve;
            // Niveau et classe (la classe seulement si l'élève en a une)
            $scolarite = collect([$p->inscription?->niveau?->libelle, $p->inscription?->classe?->libelle])->filter()->implode(' · ');

            return [
                'id'   => $p->id,
                'text' => trim(($eleve ? "{$eleve->nom} {$eleve->prenom}" : $p->payeur) . ($scolarite ? " ({$scolarite})" : '') . ' — ' . $p->reference . ' — '
                    . number_format((float) $p->details->sum('montant'), 0, ',', ' ') . ' F'),
            ];
        })->values()]);
    }

    /** Fragment HTML : détail d'un paiement (fenêtres d'encaissement et de consultation). */
    public function resume(int $paiement)
    {
        $paiement = $this->paiements->findPourEncaissement($paiement);

        return view('domain.finances.encaissements.partials.resume', [
            'paiement'   => $paiement,
            'aEncaisser' => round((float) $paiement->details
                ->filter(fn ($d) => $d->statut_paiement === PaiementStatut::EN_ATTENTE)->sum('montant'), 2),
        ]);
    }

    /** Encaisse un paiement dans la caisse ouverte du caissier, avec le montant réellement reçu. */
    public function encaisserPaiement(Request $request, int $paiement)
    {
        $valide = $request->validate([
            'montant_recu' => ['required', 'numeric', 'min:0.01'],
        ], [
            'montant_recu.required' => 'Saisissez le montant réellement encaissé.',
        ]);

        return $this->runAjax(function () use ($paiement, $valide) {
            $annee = $this->anneeActive();
            if ((int) \App\Domain\Finances\Models\Paiement::whereKey($paiement)->value('annee_id') !== (int) $annee->id) {
                throw new \RuntimeException("Ce paiement n'appartient pas à l'année scolaire sélectionnée ({$annee->libelle}).");
            }

            $resultat = $this->service->encaisserParCaissier($paiement, (float) $valide['montant_recu'], (int) Auth::id());

            return [
                'montant'  => $resultat['montant'],
                'monnaie'  => $resultat['monnaie'],
                'recu_url' => route('finances.encaissements.paiement.recu', $paiement),
            ];
        }, 'Encaissement effectué.');
    }

    /** Annule l'encaissement d'un paiement (caisse encore ouverte), avec un motif. */
    public function annulerEncaissement(Request $request, int $paiement)
    {
        $valide = $request->validate(['motif' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->runAjax(
            fn () => ['details' => $this->service->annulerEncaissementPaiement($paiement, $valide['motif'], (int) Auth::id())],
            'Encaissement annulé : le paiement est de nouveau en attente.'
        );
    }

    /** Annule un paiement en attente (Administrateur). */
    public function annulerPaiement(Request $request, int $paiement)
    {
        $valide = $request->validate(['motif' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->runAjax(
            fn () => ['paiement' => $this->paiementService->annuler($paiement, $valide['motif'], (int) Auth::id())],
            'Paiement annulé.'
        );
    }

    /** Reçu d'un paiement encaissé (toutes ses lignes encaissées). */
    public function recuPaiement(int $paiement)
    {
        $paiement = $this->paiements->findPourEncaissement($paiement);
        $detail = $paiement->details->first(fn ($d) => $d->statut_paiement === PaiementStatut::ENCAISSE);

        abort_if(! $detail, 404, "Aucun reçu disponible : ce paiement n'est pas encore encaissé.");

        return $this->recuPdf($detail->id);
    }

    /** Génère le PDF du reçu d'un encaissement. */
    public function recuPdf(int $detail)
    {
        $detailModel = $this->paiements->findDetailAvecRelations($detail);

        if ($detailModel->statut_paiement !== PaiementStatut::ENCAISSE) {
            abort(404, "Aucun reçu disponible : ce détail n'est pas encore encaissé.");
        }

        $paiement = $this->paiements->findPourEncaissement((int) $detailModel->paiement_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('domain.finances.encaissements.recu', [
            'detail'    => $detailModel,
            'paiement'  => $paiement,
            'eleve'     => $paiement->inscription?->eleve,
            'caisse'    => $detailModel->caisse,
            'genereLe'  => now(),
            'generePar' => auth()->user(),
        ])
        ->setPaper('A4', 'portrait') // les deux exemplaires (original, duplicata) sur une feuille
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'dpi'                  => 150,
            'margin_top'           => 8,
            'margin_bottom'        => 8,
            'margin_left'          => 10,
            'margin_right'         => 10,
        ]);

        $filename = sprintf('recu_%s_%s.pdf', $paiement->reference ?? 'paiement', $detailModel->id);

        return $pdf->stream($filename);
    }
}
