<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\Repositories\DossierRecouvrementRepository;
use App\Domain\Finances\Repositories\SituationRecouvrementRepository;
use App\Domain\Finances\Services\RecouvrementService;
use App\Domain\Finances\Services\RelanceService;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeRelance;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\ChangeStatutDossierRequest;
use App\Http\Requests\Domain\Finances\StoreRelanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecouvrementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private DossierRecouvrementRepository   $dossiers,
        private SituationRecouvrementRepository $situations,
        private RecouvrementService             $recouvrement,
        private RelanceService                  $relances,
    ) {
        $this->middleware('permission:recouvrement.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:recouvrement.gerer')->only(['changerStatut', 'recalculer']);
        $this->middleware('permission:recouvrement.relancer')->only(['storeRelance']);
    }

    /** Écran principal : liste des dossiers avec compteurs. */
    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'statut', 'nature', 'search']);

        return view('domain.finances.recouvrement.index', [
            'dossiers'   => $this->dossiers->listePaginee($filters),
            'filters'    => $filters,
            'statuts'    => DossierStatut::options(),
            'natures'    => NatureCreance::options(),
            'annees'     => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'compteurs'  => $this->dossiers->compterParStatut(),
            'montants'   => $this->dossiers->montantsEnCours(),
        ]);
    }

    /** Fiche détaillée d'un dossier + historique des relances. */
    public function show(int $dossier)
    {
        return view('domain.finances.recouvrement.show', [
            'dossier'        => $this->dossiers->findAvecDetails($dossier),
            'typesRelance'   => TypeRelance::options(),
            'resultats'      => ResultatRelance::options(),
            'statutsDossier' => DossierStatut::options(),
        ]);
    }

    /** Enregistre une relance. */
    public function storeRelance(StoreRelanceRequest $request, int $dossier)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($dossier, $data) {
            $relance = $this->relances->enregistrer(
                dossierId:       $dossier,
                typeRelance:     $data['type_relance'],
                dateRelance:     $data['date_relance'],
                contenu:         $data['contenu'] ?? null,
                resultat:        $data['resultat'] ?? null,
                dateProchaine:   $data['date_prochaine_relance'] ?? null,
                userId:          Auth::id(),
            );

            return ['relance' => $relance];
        }, 'Relance enregistrée.');
    }

    /** Change le statut d'un dossier. */
    public function changerStatut(ChangeStatutDossierRequest $request, int $dossier)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($dossier, $data) {
            return [
                'dossier' => $this->relances->changerStatut($dossier, $data['statut'], $data['motif'] ?? null),
            ];
        }, 'Statut du dossier mis à jour.');
    }

    /** Recalcul manuel (bouton d'urgence — la nuit c'est automatique). */
    public function recalculer(Request $request)
    {
        // Toutes les inscriptions de l'année sont recalculées : peut dépasser la durée par défaut
        @set_time_limit(300);

        return $this->runAjax(function () use ($request) {
            return $this->recouvrement->recalculerTout($request->input('annee_id'));
        }, 'Situations recalculées.');
    }

    /** Export CSV de la liste des dossiers. */
    public function export(Request $request): StreamedResponse
    {
        $filters  = $request->only(['annee_id', 'statut', 'nature', 'search']);
        $dossiers = $this->dossiers->listePaginee($filters, perPage: 10000);

        $filename = 'recouvrement_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($dossiers) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Dossier', 'Inscription', 'Nature', 'Montant initial',
                'Recouvré', 'Restant', 'Statut', 'Ouvert le', 'Clos le', 'Responsable',
            ], ';');

            foreach ($dossiers as $d) {
                fputcsv($out, [
                    $d->id,
                    $d->inscription_id,
                    $d->nature?->label(),
                    number_format((float) $d->montant_initial_du, 2, ',', ' '),
                    number_format((float) $d->montant_recouvre, 2, ',', ' '),
                    number_format($d->montant_restant, 2, ',', ' '),
                    $d->statut?->label(),
                    optional($d->date_ouverture)->format('d/m/Y'),
                    optional($d->date_cloture)->format('d/m/Y'),
                    $d->responsable?->name,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}