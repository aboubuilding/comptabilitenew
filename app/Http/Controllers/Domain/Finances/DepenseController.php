<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\DTO\DepenseData;
use App\Domain\Finances\Types\CaisseStatut; // ⚠️ adapte si tu utilises Types\CaisseStatut
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\DepenseRepository;
use App\Domain\Finances\Services\DepenseService;
use App\Domain\Finances\Types\DepenseStatut;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\ReglerDepenseRequest;
use App\Http\Requests\Domain\Finances\RejeterDepenseRequest;
use App\Http\Requests\Domain\Finances\StoreDepenseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Domain\Finances\Repositories\CentreAnalytiqueRepository;
use App\Domain\Finances\Repositories\ImputationAnalytiqueRepository;

class DepenseController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private DepenseRepository $depenses,
        private CaisseRepository  $caisses,
        private DepenseService    $service,
    ) {
        $this->middleware('permission:depenses.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:depenses.creer')->only(['store', 'update']);
        $this->middleware('permission:depenses.valider')->only(['valider', 'rejeter']);
        $this->middleware('permission:depenses.regler')->only(['regler']);
        $this->middleware('permission:depenses.voir')->only(['exportPdf']);
        $this->middleware('permission:depenses.creer')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'statut', 'caisse_id', 'search', 'date_debut', 'date_fin']);

        return view('domain.finances.depenses.index', [
            'depenses'       => $this->depenses->listePaginee($filters),
            'filters'        => $filters,
            'statuts'        => DepenseStatut::options(),
            'caisses'        => $this->caisses->listePourSelect(),
            'annees'         => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'compteurs'      => $this->depenses->compterParStatut(),
            'montants'       => $this->depenses->montantParStatut(),
        ]);
    }

    /** Saisie d'une nouvelle dépense (avec justificatif). */
   public function store(StoreDepenseRequest $request)
{
    $data = DepenseData::fromRequest($request->validated(), Auth::id());

    return $this->runAjax(
        fn () => ['depense' => $this->service->saisir(
            $data,
            $request->file('justificatif'),
            $request->validated()['nature_analytique'] ?? null,
        )],
        'Dépense enregistrée en attente de validation.'
    );
}

    /** Modification d'une dépense (en attente uniquement). */
    public function update(StoreDepenseRequest $request, int $depense)
    {
        $data = DepenseData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(
            fn () => ['depense' => $this->service->modifier($depense, $data, $request->file('justificatif'))],
            'Dépense mise à jour.'
        );
    }

    /** Validation par la Direction. */
    public function valider(int $depense)
    {
        return $this->runAjax(
            fn () => ['depense' => $this->service->valider($depense, Auth::id())],
            'Dépense validée.'
        );
    }

    /** Rejet motivé. */
    public function rejeter(RejeterDepenseRequest $request, int $depense)
    {
        return $this->runAjax(
            fn () => ['depense' => $this->service->rejeter($depense, Auth::id(), $request->validated()['motif_rejet'])],
            'Dépense rejetée.'
        );
    }

    /** Règlement via une caisse ouverte. */
    public function regler(ReglerDepenseRequest $request, int $depense)
    {
        return $this->runAjax(
            fn () => ['depense' => $this->service->reglerViaCaisse($depense, (int) $request->validated()['caisse_id'], Auth::id())],
            'Dépense réglée et mouvement de caisse créé.'
        );
    }

    /** Export CSV ouvrable dans Excel. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'statut', 'caisse_id', 'search', 'date_debut', 'date_fin']);
        $depenses = $this->depenses->listePaginee($filters, perPage: 10000);

        $filename = 'depenses_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($depenses) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Libellé', 'Bénéficiaire', 'Montant', 'Date',
                'Statut', 'Caisse de règlement', 'Date de règlement',
                'Motif', 'Motif de rejet',
            ], ';');

            foreach ($depenses as $d) {
                fputcsv($out, [
                    $d->libelle,
                    $d->beneficiaire,
                    number_format((float) $d->montant, 2, ',', ' '),
                    optional($d->date_depense)->format('d/m/Y'),
                    $d->statut_depense?->label(),
                    $d->caisse?->libelle,
                    optional($d->date_reglement)->format('d/m/Y'),
                    $d->motif_depense,
                    $d->motif_rejet,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Journal des dépenses au format PDF, en respectant les filtres de l'écran. */
    public function exportPdf(Request $request)
    {
        $filters  = $request->only(['annee_id', 'statut', 'caisse_id', 'search', 'date_debut', 'date_fin']);
        $depenses = $this->depenses->listePaginee($filters, perPage: 5000);

        $somme = fn (callable $filtre) => round((float) $depenses->getCollection()->filter($filtre)->sum('montant'), 2);

        $totaux = [
            'en_attente' => $somme(fn ($d) => $d->isEnAttente()),
            'validees'   => $somme(fn ($d) => $d->isValidee()),
            'reglees'    => $somme(fn ($d) => $d->isValidee() && $d->isReglee()),
            'rejetees'   => $somme(fn ($d) => $d->isRejetee()),
        ];

        $filtreLabels = array_filter([
            ! empty($filters['search']) ? 'Recherche : « ' . $filters['search'] . ' »' : null,
            ! empty($filters['statut']) ? 'Statut : ' . (DepenseStatut::tryFrom((int) $filters['statut'])?->label() ?? '') : null,
            ! empty($filters['date_debut']) ? 'Du ' . $filters['date_debut'] : null,
            ! empty($filters['date_fin']) ? 'Au ' . $filters['date_fin'] : null,
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('domain.finances.depenses.print', [
            'depenses'     => $depenses,
            'totaux'       => $totaux,
            'filtreLabels' => $filtreLabels,
            'nbDepenses'   => $depenses->total(),
            'genereLe'     => now(),
            'generePar'    => auth()->user(),
        ])
        ->setPaper('A4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'dpi'                  => 130,
        ]);

        return $pdf->download('depenses_' . now()->format('Ymd_His') . '.pdf');
    }

    // app/Http/Controllers/Domain/Finances/DepenseController.php — ajout

public function destroy(int $depense)
{
    return $this->runAjax(
        function () use ($depense) {
            $this->depenses->delete($depense); // déclenche MouvementObserver
            return null;
        },
        'Dépense supprimée.'
    );
}


public function show(int $depense)
{
    $depenseModel = $this->depenses->findOrFail($depense);
    $depenseModel->load(['auteur:id,name', 'valideur:id,name', 'caisse:id,libelle']);

    $imputations = app(ImputationAnalytiqueRepository::class)
        ->pourSource($depenseModel::class, $depenseModel->id);

    return view('domain.finances.depenses.show', [
        'depense'     => $depenseModel,
        'imputations' => $imputations,
        'centres'     => app(CentreAnalytiqueRepository::class)->listePourSelect(),
    ]);
}
}