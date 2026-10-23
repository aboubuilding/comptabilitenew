<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\FraisEcoleData;
use App\Domain\Finances\Models\Souscription;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use App\Domain\Finances\Services\EcheancierService;
use App\Domain\Finances\Services\FraisEcoleService;
use App\Domain\Finances\Services\FraisSynchronisationService;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Models\Niveau;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreFraisEcoleRequest;
use App\Support\AnneeScolaireContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 2.1 Frais scolaires. La liste (cinq onglets), les compteurs, les exports,
 * la création et les synchronisations portent sur l'année scolaire choisie
 * dans l'en-tête (AnneeScolaireContext) : pas d'autre sélecteur d'année.
 */
class FraisEcoleController extends Controller
{
    use HandlesControllerErrors;

    private const FILTRES = ['niveau_id', 'modalite_paiement', 'type_forfait', 'type_paiement', 'search', 'tri', 'sens'];

    /** Onglets dont les paiements citent un frais : on y signale l'encaissé resté sans frais précis. */
    private const ONGLETS_DE_SERVICE = ['cantine', 'transport'];

    public function __construct(
        private FraisEcoleRepository        $frais,
        private PlanEcheancierRepository    $plans,
        private FraisEcoleService           $service,
        private EcheancierService           $echeanciers,
        private FraisSynchronisationService $synchronisation,
        private AnneeScolaireContext        $contexte,
    ) {
        $this->middleware('permission:frais.voir')->only(['index', 'export', 'show', 'exportPdf']);
        $this->middleware('permission:frais.gerer')->only(['store', 'edit', 'update', 'destroy', 'synchroniser']);
    }

    private function anneeId(): int
    {
        return (int) $this->contexte->id();
    }

    private function onglet(Request $request): string
    {
        return array_key_exists((string) $request->input('onglet'), FraisEcoleRepository::ONGLETS)
            ? $request->input('onglet')
            : 'inscription';
    }

    /** Filtres de l'écran ; les natures de l'onglet s'y ajoutent. */
    private function filtres(Request $request, string $onglet): array
    {
        $filters = array_filter($request->only(self::FILTRES), fn ($v) => $v !== null && $v !== '');
        $types = FraisEcoleRepository::typesDe($onglet);
        if ($types !== null) {
            $filters['types'] = $types;
            unset($filters['type_paiement']);   // le filtre « type de frais » n'existe que dans « Tous les frais »
        }

        return $filters;
    }

    public function index(Request $request)
    {
        $anneeId = $this->anneeId();
        $onglet = $this->onglet($request);
        $filters = $this->filtres($request, $onglet);
        $fraisEcoles = $this->frais->listePaginee($anneeId, $filters);
        $voitMontants = $request->user()->can('totaux-encaisses.consulter');

        return view('domain.finances.frais.index', [
            'onglet'            => $onglet,
            'onglets'           => FraisEcoleRepository::ONGLETS,
            'annee'             => Annee::find($anneeId),
            'fraisEcoles'       => $fraisEcoles,
            // Montant récolté par frais : Administrateur et Direction seulement
            'recoltes'          => $voitMontants
                ? $this->frais->montantsRecoltes($fraisEcoles->getCollection(), $anneeId)
                : null,
            // Cantine et transport : encaissé de l'année d'après le type de paiement, dont la part sans frais cité
            'encaisseType'      => $voitMontants && in_array($onglet, self::ONGLETS_DE_SERVICE, true)
                ? $this->frais->encaisseParType($anneeId, FraisEcoleRepository::typesDe($onglet))
                : null,
            'filters'           => $filters,
            'compteursOnglets'  => $this->frais->compterParOnglet($anneeId),
            'compteurs'         => $this->frais->compterParTypePaiement($anneeId, $filters),
            'niveauxOptions'    => Niveau::orderBy('libelle')->get(['id', 'libelle']),
            // Plans de l'année de l'en-tête uniquement
            'plansOptions'      => $this->plans->pourAnnee($anneeId),
            'naturesCreables'   => collect(FraisEcoleRepository::CREABLES)->mapWithKeys(fn (TypeDetail $t) => [$t->value => $t->label()])->all(),
            'toutesNatures'     => TypeDetail::options(),
            'modalitesPaiement' => TypePaiement::options(),
            'typesForfait'      => TypeForfait::options(),
            'cibles'            => FraisSynchronisationService::CIBLES,
        ]);
    }

    public function store(StoreFraisEcoleRequest $request)
    {
        return $this->runAjax(
            fn () => ['frais' => $this->service->creer(FraisEcoleData::fromRequest($request->validated()))],
            'Frais enregistré.'
        );
    }

    public function edit(int $frais)
    {
        $modele = $this->frais->findOrFail($frais);
        $modele->load('planEcheancier.lignes');

        return response()->json([
            'fraisEcole' => $modele->only([
                'id', 'libelle', 'montant', 'type_paiement', 'modalite_paiement',
                'type_forfait', 'niveau_id', 'plan_echeancier_id',
            ]) + [
                'echeances' => ($modele->planEcheancier?->lignes ?? collect())->map(fn ($l) => [
                    'libelle'       => $l->libelle,
                    'date_echeance' => optional($l->date_echeance)->toDateString(),
                    // Un plan en pourcentages s'exprime ici en montants du frais
                    'montant'       => $l->montant ?: round((float) $modele->montant * (float) $l->pourcentage / 100),
                ])->values(),
            ],
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

    /** Alimente les produits, les activités ou les événements à partir des frais de l'année de l'en-tête. */
    public function synchroniser(string $cible)
    {
        abort_unless(array_key_exists($cible, FraisSynchronisationService::CIBLES), 404);

        return $this->runAjax(
            fn () => ['bilan' => $this->synchronisation->synchroniser($cible, $this->anneeId())],
            'Synchronisation terminée.'
        );
    }

    /** Export CSV de l'onglet affiché : année de l'en-tête et filtres en cours. */
    public function export(Request $request): StreamedResponse
    {
        $onglet = $this->onglet($request);
        $frais = $this->frais->listePaginee($this->anneeId(), $this->filtres($request, $onglet), perPage: 10000);
        $annee = Annee::find($this->anneeId())?->libelle;

        $filename = 'frais_ecoles_' . $onglet . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($frais, $annee) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Libellé', 'Type de frais', 'Niveau', 'Montant', 'Type de paiement',
                'Type forfait', 'Plan d\'échéancier', 'Année',
            ], ';');

            foreach ($frais as $f) {
                fputcsv($out, [
                    $f->libelle,
                    $f->type_paiement?->label(),
                    $f->niveau?->libelle,
                    number_format((float) $f->montant, 2, ',', ' '),
                    $f->modalite_paiement?->label(),
                    $f->type_forfait?->label(),
                    $f->planEcheancier?->nom,
                    $annee,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(int $frais)
    {
        $fraisModel = $this->frais->findOrFail($frais);
        $fraisModel->load([
            'niveau:id,libelle',
            'planEcheancier.lignes',
        ]);

        // Souscriptions rattachées à ce frais, avec élève et inscription.
        $souscriptions = Souscription::query()
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
            'frais'         => $fraisModel,
            'souscriptions' => $souscriptions,
            'totaux'        => $totaux,
            // Prévu / payé / reste par échéance (frais de scolarité échelonnés)
            'suivi'         => $this->echeanciers->suivi($fraisModel),
        ]);
    }

    /** Export PDF de l'onglet affiché : année de l'en-tête et filtres en cours. */
    public function exportPdf(Request $request)
    {
        $onglet = $this->onglet($request);
        $filters = $this->filtres($request, $onglet);
        $frais = $this->frais->listePaginee($this->anneeId(), $filters, perPage: 5000);

        $totaux = [
            'comptant'    => round((float) $frais->where('modalite_paiement', TypePaiement::COMPTANT)->sum('montant'), 2),
            'echelonne'   => round((float) $frais->where('modalite_paiement', TypePaiement::ECHELONNE)->sum('montant'), 2),
            'obligatoire' => $frais->where('type_forfait', TypeForfait::OBLIGATOIRE)->count(),
            'optionnel'   => $frais->where('type_forfait', TypeForfait::OPTIONNEL)->count(),
        ];

        $filtreLabels = array_filter([
            'Année scolaire : ' . (Annee::find($this->anneeId())?->libelle ?? '—'),
            'Onglet : ' . FraisEcoleRepository::ONGLETS[$onglet][0],
            ! empty($filters['search']) ? 'Recherche : « ' . $filters['search'] . ' »' : null,
            ! empty($filters['modalite_paiement']) ? 'Type : ' . (TypePaiement::tryFrom((int) $filters['modalite_paiement'])?->label() ?? '') : null,
            ! empty($filters['type_forfait']) ? 'Forfait : ' . (TypeForfait::tryFrom((int) $filters['type_forfait'])?->label() ?? '') : null,
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

        return $pdf->download('catalogue_frais_' . $onglet . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
