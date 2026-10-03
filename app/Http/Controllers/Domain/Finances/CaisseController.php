<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Administration\Models\User;
use App\Domain\Finances\DTO\ClotureCaisseData;
use App\Domain\Finances\DTO\MouvementCaisseData;
use App\Domain\Finances\DTO\OuvertureCaisseData;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Services\CaisseService;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\MouvementType;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\CloturerCaisseRequest;
use App\Http\Requests\Domain\Finances\OuvrirCaisseRequest;
use App\Http\Requests\Domain\Finances\StoreMouvementCaisseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
   
use Barryvdh\DomPDF\Facade\Pdf;

class CaisseController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private CaisseRepository $caisses,
        private CaisseService    $service,
    ) {
        $this->middleware('permission:caisses.voir')->only(['index', 'show', 'export', 'exportListe']);
        $this->middleware('permission:caisses.ouvrir')->only(['store']);
        $this->middleware('permission:caisses.cloturer')->only(['cloturer']);
        $this->middleware('permission:caisses.mouvements')->only(['storeMouvement']);
        $this->middleware('permission:caisses.voir')->only(['exportPdf']);
    }

    /** Écran index : liste paginée + filtres + stats + modale d'ouverture. */
    public function index(Request $request)
    {
        $filters = $request->only(['statut', 'responsable_id', 'search']);
        // Cloisonnement : les caisses de l'année scolaire choisie en haut de page
        $annee = Annee::find(app(\App\Support\AnneeScolaireContext::class)->id());
        $filters['annee_id'] = $annee?->id;

        $user = $request->user();

        return view('domain.finances.caisses.index', [
            'caisses'      => $this->caisses->listePaginee($filters),
            'filters'      => $filters,
            'annee'        => $annee,
            'anneeCloturee' => (bool) $annee?->isCloturee(),
            'statuts'      => CaisseStatut::options(),
            'caissiers'    => User::query()->where('etat', 1)->orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            // Compteurs réservés à l'Administrateur (et au superadmin)
            'compteurs'    => $user->isAdmin() ? $this->caisses->compterParStatutAnnee($annee?->id) : null,
            // Responsables possibles quand l'Administrateur confie une caisse : lui-même et ceux qui encaissent
            'responsables' => $user->isAdmin()
                ? User::query()->where('etat', 1)->orderBy('nom')->orderBy('prenom')->get()
                    ->filter(fn (User $u) => $u->id === $user->id || $u->can('encaissements.creer'))
                    ->values()
                : collect(),
        ]);
    }

    /** Export de la liste des caisses (sessions), avec les filtres de l'écran. */
    public function exportListe(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'statut', 'responsable_id', 'search']);
        $caisses = $this->caisses->listePaginee($filters, perPage: 10000);

        return response()->streamDownload(function () use ($caisses) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Caisse', 'Caissier', 'Statut', 'Solde initial', 'Solde théorique',
                'Solde compté', 'Écart', 'Ouverture', 'Clôture',
            ], ';');

            foreach ($caisses as $caisse) {
                fputcsv($out, [
                    $caisse->libelle,
                    trim(($caisse->caissier?->nom ?? '') . ' ' . ($caisse->caissier?->prenom ?? '')),
                    $caisse->isOuverte() ? 'Ouverte' : 'Clôturée',
                    number_format((float) $caisse->solde_initial, 2, ',', ' '),
                    number_format((float) $caisse->solde_theorique, 2, ',', ' '),
                    $caisse->solde_compte !== null ? number_format((float) $caisse->solde_compte, 2, ',', ' ') : '',
                    $caisse->ecart !== null ? number_format((float) $caisse->ecart, 2, ',', ' ') : '',
                    optional($caisse->date_ouverture)->format('d/m/Y H:i'),
                    optional($caisse->date_cloture)->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($out);
        }, 'caisses_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Journal d'une session (ouverte ou clôturée), en trois onglets : la
     * chronologie de tous les mouvements, les encaissements (avec leurs
     * totaux par nature de paiement) et les décaissements.
     */
    public function show(int $caisse)
    {
        $journal = $this->service->preparerJournal($caisse);
        $mouvements = $journal['mouvements'];

        // Encaissements des paiements dans cette caisse, regroupés par paiement
        $encaissements = app(\App\Domain\Finances\Repositories\PaiementRepository::class)
            ->encaissementsQuery(['caisse_id' => $caisse])
            ->get();

        // Totaux encaissés par nature de paiement (scolarité, cantine…)
        $totauxParType = $this->service->totauxEncaissesParType($caisse);

        $entreesManuelles = $mouvements->filter(fn ($m) => $m->type_mouvement === MouvementType::ENTREE_MANUELLE)->values();
        $decaissements    = $mouvements->filter(fn ($m) => $m->type_mouvement && ! $m->type_mouvement->isEntree())->values();

        return view('domain.finances.caisses.show', [
            'journal'          => $journal,
            'typesManuels'     => MouvementType::manuels(),
            'encaissements'    => $encaissements,
            'totauxParType'    => $totauxParType,
            'totalEncaisse'    => round((float) $totauxParType->sum('montant'), 2),
            'entreesManuelles' => $entreesManuelles,
            'decaissements'    => $decaissements,
            'onglet'           => in_array(request('onglet'), ['mouvements', 'encaissements', 'decaissements'], true) ? request('onglet') : 'mouvements',
        ]);
    }

    /** Ouvre une nouvelle session. */
    public function store(OuvrirCaisseRequest $request)
    {
        $data = OuvertureCaisseData::fromRequest($request->validated(), Auth::id());

        // Fenêtre d'ouverture (AJAX) : réponse JSON, avec le motif en cas de refus
        if ($request->expectsJson()) {
            return $this->runAjax(fn () => ['caisse' => $this->service->ouvrir($data)], 'Caisse ouverte avec succès.');
        }

        return $this->runWeb(
            fn () => $this->service->ouvrir($data),
            'finances.caisses.show',
            'Caisse ouverte avec succès.',
            fn ($caisse) => ['caisse' => $caisse->id]  // reçoit la caisse créée
        );
    }

    /** Clôture une session (fige le journal). */
    public function cloturer(CloturerCaisseRequest $request, int $caisse)
    {
        $data = ClotureCaisseData::fromRequest($caisse, $request->validated(), Auth::id());

        return $this->runWeb(
            fn () => $this->service->cloturer($data),
            'finances.caisses.show',
            'Caisse clôturée. Le journal est désormais figé.',
            ['caisse' => $caisse]
        );
    }

    /** Ajoute un mouvement manuel sur une caisse ouverte. */
    public function storeMouvement(StoreMouvementCaisseRequest $request, int $caisse)
    {
        $data = MouvementCaisseData::fromRequest($caisse, $request->validated(), Auth::id());

        return $this->runWeb(
            fn () => $this->service->enregistrerMouvement($data),
            'finances.caisses.show',
            'Mouvement enregistré.',
            ['caisse' => $caisse]
        );
    }

    /** Export CSV (ouvrable dans Excel) du journal d'une session. */
    public function export(int $caisse): StreamedResponse
    {
        $journal = $this->service->preparerJournal($caisse);

        $filename = sprintf(
            'journal_caisse_%d_%s.csv',
            $journal['caisse']->id,
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function () use ($journal) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

            fputcsv($out, [
                'Date', 'Libellé', 'Bénéficiaire', 'Type',
                'Entrée', 'Sortie', 'User',
            ], ';');

            foreach ($journal['mouvements'] as $m) {
                $type = $m->type_mouvement;

                fputcsv($out, [
                    optional($m->date_mouvement)->format('d/m/Y'),
                    $m->libelle,
                    $m->beneficiaire,
                    $type?->label(),
                    $type?->isEntree() ? number_format($m->montant, 2, ',', ' ') : '',
                    $type && ! $type->isEntree() ? number_format($m->montant, 2, ',', ' ') : '',
                    trim(($m->User?->nom ?? '') . ' ' . ($m->User?->prenom ?? '')),
                ], ';');
            }

            fputcsv($out, []);
            fputcsv($out, ['Totaux', '', '', '',
                number_format($journal['totalEntrees'], 2, ',', ' '),
                number_format($journal['totalSorties'], 2, ',', ' '),
            ], ';');
            fputcsv($out, ['Solde théorique', '', '', '',
                number_format($journal['soldeTheorique'], 2, ',', ' '),
            ], ';');

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }




public function exportPdf(int $caisse)
{
    $journal = $this->service->preparerJournal($caisse);
    $caisseModel = $journal['caisse'];

    $totauxParType = $this->service->totauxEncaissesParType($caisse);
    $entreesManuelles = $journal['mouvements']->filter(fn ($m) => $m->type_mouvement === MouvementType::ENTREE_MANUELLE);

    $pdf = Pdf::loadView('domain.finances.caisses.print', [
        'journal'    => $journal,
        'caisse'     => $caisseModel,
        'totauxParType'  => $totauxParType,
        'totalEncaisse'  => round((float) $totauxParType->sum('montant'), 2),
        'totalManuel'    => round((float) $entreesManuelles->sum('montant'), 2),
        'nbManuelles'    => $entreesManuelles->count(),
        'genereLe'   => now(),
        'generePar'  => auth()->user(),
    ])
    ->setPaper('A4', 'portrait')
    ->setOptions([
        'defaultFont'          => 'DejaVu Sans',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => true,
        'dpi'                  => 130,
        'margin_top'           => 12,
        'margin_bottom'        => 12,
        'margin_left'          => 12,
        'margin_right'         => 12,
    ]);

    $filename = sprintf(
        'journal_caisse_%d_%s.pdf',
        $caisseModel->id,
        now()->format('Ymd_His')
    );

    return $pdf->download($filename);
}
}