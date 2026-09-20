<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Administration\Models\Annee;
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

class CaisseController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private CaisseRepository $caisses,
        private CaisseService    $service,
    ) {
        $this->middleware('permission:caisses.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:caisses.ouvrir')->only(['store']);
        $this->middleware('permission:caisses.cloturer')->only(['cloturer']);
        $this->middleware('permission:caisses.mouvements')->only(['storeMouvement']);
    }

    /** Écran index : liste paginée + filtres + stats + modale d'ouverture. */
    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'statut', 'responsable_id', 'search']);

        return view('domain.finances.caisses.index', [
            'caisses'         => $this->caisses->listePaginee($filters),
            'filters'         => $filters,
            'statuts'         => CaisseStatut::options(),
            'caissiers'       => User::query()
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom']),
            'annees'          => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'totalCaisses'    => $this->caisses->count(),
            'caissesOuvertes' => $this->caisses->compterOuvertes(),
        ]);
    }

    /** Écran show : journal complet d'une session (ouverte ou clôturée). */
    public function show(int $caisse)
    {
        $journal = $this->service->preparerJournal($caisse);

        return view('domain.finances.caisses.show', [
            'journal'      => $journal,
            'typesManuels' => MouvementType::manuels(),
        ]);
    }

    /** Ouvre une nouvelle session. */
    public function store(OuvrirCaisseRequest $request)
    {
        $data   = OuvertureCaisseData::fromRequest($request->validated(), Auth::id());
        $caisse = null;

        return $this->runWeb(
            function () use ($data, &$caisse) {
                $caisse = $this->service->ouvrir($data);
            },
            'finances.caisses.show',
            'Caisse ouverte avec succès.',
            fn () => ['caisse' => $caisse->id]  // évalué APRÈS l'action
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
}