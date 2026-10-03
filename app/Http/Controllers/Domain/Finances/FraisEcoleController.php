<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\DTO\FraisEcoleData;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use App\Domain\Finances\Services\FraisEcoleService;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use App\Domain\Scolarite\Models\Niveau;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreFraisEcoleRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

// app/Http/Controllers/Domain/Finances/FraisEcoleController.php — ajout

use Barryvdh\DomPDF\Facade\Pdf;

class FraisEcoleController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private FraisEcoleRepository    $frais,
        private PlanEcheancierRepository $plans,
        private FraisEcoleService       $service,
    ) {
        $this->middleware('permission:frais.voir')->only(['index', 'export']);
        $this->middleware('permission:frais.gerer')->only(['store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:frais.voir')->only(['show', 'exportPdf']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'niveau_id', 'modalite_paiement', 'type_forfait', 'search']);

        return view('domain.finances.frais.index', [
            'fraisEcoles'    => $this->frais->listePaginee($filters),
            'filters'        => $filters,
            'niveauxOptions' => Niveau::orderBy('libelle')->get(['id', 'libelle']),
            'plansOptions'   => $this->plans->all(['id', 'nom']),
            'anneesOptions'  => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'anneeId'        => (int) ($filters['annee_id'] ?? app(\App\Support\AnneeScolaireContext::class)->id()),
            'modalitesPaiement'=> TypePaiement::options(),
            'typesForfait' => TypeForfait::options(),
            'compteurs'    => $this->frais->compterParTypePaiement(),
        ]);
    }

    public function store(StoreFraisEcoleRequest $request)
    {
        return $this->runAjax(
            fn () => ['frais' => $this->service->creer(FraisEcoleData::fromRequest($request->validated()))],
            'Frais enregistré.'
        );
    }

    /** Données d'un frais pour pré-remplir la modale de modification. */
    public function edit(int $frais)
    {
        return response()->json([
            'fraisEcole' => $this->frais->findOrFail($frais)->only([
                'id', 'libelle', 'montant', 'type_paiement', 'modalite_paiement',
                'type_forfait', 'niveau_id', 'plan_echeancier_id',
            ]),
        ]);
    }

    public function update(StoreFraisEcoleRequest $request, int $frais)
    {
        return $this->runAjax(
            fn () => ['frais' => $this->service->modifier($frais, FraisEcoleData::fromRequest($request->validated()))],
            'Frais mis à jour.'
        );
    }

    public function destroy(int $frais)
    {
        return $this->runAjax(function () use ($frais) {
            $this->service->supprimer($frais);
            return null;
        }, 'Frais désactivé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'niveau_id', 'modalite_paiement', 'type_forfait', 'search']);
        $frais   = $this->frais->listePaginee($filters, perPage: 10000);

        $filename = 'frais_ecoles_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($frais) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Libellé', 'Niveau', 'Montant', 'Type de paiement',
                'Type forfait', 'Plan d\'échéancier', 'Année',
            ], ';');

            foreach ($frais as $f) {
                fputcsv($out, [
                    $f->libelle,
                    $f->niveau?->libelle,
                    number_format((float) $f->montant, 2, ',', ' '),
                    $f->modalite_paiement?->label(),
                    $f->type_forfait?->label(),
                    $f->planEcheancier?->nom,
                    $f->annee_id,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }


    // app/Http/Controllers/Domain/Finances/FraisEcoleController.php — ajout

public function show(int $frais)
{
    $fraisModel = $this->frais->findOrFail($frais);
    $fraisModel->load([
        'niveau:id,libelle',
        'planEcheancier.lignes',
        'tranches',
    ]);

    // Souscriptions rattachées à ce frais, avec élève et inscription.
    $souscriptions = \App\Domain\Finances\Models\Souscription::query()
        ->with([
            'inscription:id,eleve_id,classe_id',
            'inscription.eleve:id,nom,prenom,matricule',
            'inscription.classe:id,libelle',
            'auteur:id,name',
        ])
        ->where('frais_ecole_id', $fraisModel->id)
        ->where('etat', 1)
        ->orderByDesc('date_souscription')
        ->paginate(15);

    // Agrégats : total brut, remise, net
    $totaux = [
        'brut'   => round((float) $souscriptions->sum('montant_annuel_prevu'), 2),
        'remise' => round((float) $souscriptions->sum(fn ($s) => $s->montant_remise), 2),
        'net'    => round((float) $souscriptions->sum(fn ($s) => $s->montant_net), 2),
    ];

    return view('domain.finances.frais.show', [
        'frais'        => $fraisModel,
        'souscriptions'=> $souscriptions,
        'totaux'       => $totaux,
    ]);
}




public function exportPdf(Request $request)
{
    $filters = $request->only(['annee_id', 'niveau_id', 'modalite_paiement', 'type_forfait', 'search']);
    $frais   = $this->frais->listePaginee($filters, perPage: 5000);

    // Agrégats par type de paiement
    $totaux = [
        'comptant'  => round((float) $frais->where('modalite_paiement', \App\Domain\Finances\Types\TypePaiement::COMPTANT)->sum('montant'), 2),
        'echelonne' => round((float) $frais->where('modalite_paiement', \App\Domain\Finances\Types\TypePaiement::ECHELONNE)->sum('montant'), 2),
        'obligatoire' => $frais->where('type_forfait', \App\Domain\Finances\Types\TypeForfait::OBLIGATOIRE)->count(),
        'optionnel'   => $frais->where('type_forfait', \App\Domain\Finances\Types\TypeForfait::OPTIONNEL)->count(),
    ];

    $filtreLabels = array_filter([
        ! empty($filters['search']) ? 'Recherche : « ' . $filters['search'] . ' »' : null,
        ! empty($filters['modalite_paiement']) ? 'Type : ' . (\App\Domain\Finances\Types\TypePaiement::tryFrom((int) $filters['modalite_paiement'])?->label() ?? '') : null,
        ! empty($filters['type_forfait']) ? 'Forfait : ' . (\App\Domain\Finances\Types\TypeForfait::tryFrom((int) $filters['type_forfait'])?->label() ?? '') : null,
    ]);

    $pdf = Pdf::loadView('domain.finances.frais.print', [
        'frais'        => $frais,
        'totaux'       => $totaux,
        'filtreLabels' => $filtreLabels,
        'nbFrais'      => $frais->total(),
        'genereLe'     => now(),
        'generePar'    => auth()->user(),
    ])
    ->setPaper('A4', 'portrait')
    ->setOptions([
        'defaultFont'          => 'DejaVu Sans',
        'isHtml5ParserEnabled' => true,
        'dpi'                  => 130,
        'margin_top'           => 12,
        'margin_bottom'        => 12,
        'margin_left'          => 12,
        'margin_right'         => 12,
    ]);

    $filename = 'catalogue_frais_' . now()->format('Ymd_His') . '.pdf';

    return $pdf->download($filename);
}
}