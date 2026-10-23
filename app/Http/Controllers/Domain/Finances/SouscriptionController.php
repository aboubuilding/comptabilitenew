<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Exports\SouscriptionsExport;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Repositories\SouscriptionRepository;
use App\Domain\Finances\Services\SouscriptionChargementService;
use App\Domain\Finances\Services\SouscriptionService;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Scolarite\Repositories\InscriptionRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreSouscriptionRequest;
use App\Support\AnneeScolaireContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Souscriptions aux services : cantine, transport, activités extrascolaires
 * et événements.
 *
 * Tout l'écran — les deux onglets, les indicateurs, les exports et la
 * synchronisation — porte sur l'année scolaire choisie dans l'en-tête
 * (AnneeScolaireContext) ; il n'y a pas d'autre sélecteur d'année.
 * Les indicateurs financiers sont réservés à la Direction et à l'Administrateur.
 */
class SouscriptionController extends Controller
{
    use HandlesControllerErrors;

    public const ONGLETS = ['non-a-jour' => 'Élèves non à jour', 'toutes' => 'Toutes les souscriptions'];

    private const FILTRES = ['search', 'niveau_id', 'classe_id', 'type', 'paiement', 'frais_ecole_id', 'inscription_id'];

    public function __construct(
        private SouscriptionRepository        $souscriptions,
        private FraisEcoleRepository          $frais,
        private SouscriptionService           $service,
        private InscriptionRepository         $inscriptions,
        private SouscriptionChargementService $chargement,
        private AnneeScolaireContext          $contexte,
    ) {
        $this->middleware('permission:souscriptions.voir')->only(['index', 'export', 'exportPdf', 'paiements']);
        $this->middleware('permission:souscriptions.gerer')->only(['store', 'destroy', 'synchroniser']);
        // Cahier des charges § 5.2 : la Direction arbitre aussi les taux de remise.
        $this->middleware('permission:souscriptions.remise')->only(['update']);
    }

    private function anneeId(): int
    {
        return (int) $this->contexte->id();
    }

    private function onglet(Request $request): string
    {
        return array_key_exists((string) $request->input('onglet'), self::ONGLETS) ? $request->input('onglet') : 'non-a-jour';
    }

    /** Filtres de l'écran, toujours bornés à l'année de l'en-tête. */
    private function filtres(Request $request, string $onglet): array
    {
        $filters = array_filter($request->only(self::FILTRES), fn ($v) => $v !== null && $v !== '');
        $filters['annee_id'] = $this->anneeId();

        if ($onglet === 'non-a-jour') {
            $filters['non_a_jour'] = true;
            // Dans cet onglet le statut ne peut être que « partiel » ou « impayé »
            if (($filters['paiement'] ?? null) === 'solde') {
                unset($filters['paiement']);
            }
        }

        return $filters;
    }

    public function index(Request $request)
    {
        $anneeId = $this->anneeId();
        // Les abonnements pris dans les modules de services apparaissent ici sans ressaisie
        $this->chargement->synchroniserModules($anneeId);

        $onglet = $this->onglet($request);
        $filters = $this->filtres($request, $onglet);
        $voitIndicateurs = $request->user()->can('tableau-souscriptions.consulter');

        return view('domain.finances.souscriptions.index', [
            'onglet'         => $onglet,
            'onglets'        => self::ONGLETS,
            'annee'          => Annee::find($anneeId),
            'souscriptions'  => $this->souscriptions->listePaginee($filters),
            'filters'        => $filters,
            'types'          => SouscriptionRepository::SERVICES,
            'niveaux'        => Niveau::orderBy('libelle')->get(['id', 'libelle']),
            'classes'        => Classe::where('annee_id', $anneeId)->orderBy('libelle')->get(['id', 'libelle']),
            // Seuls les frais de service de l'année se souscrivent (la scolarité relève de l'inscription)
            'frais'          => $this->frais->listePourSelect($anneeId)
                ->filter(fn ($f) => in_array($f->type_paiement, SouscriptionRepository::SERVICES, true))->values(),
            'inscriptions'   => $this->inscriptions->listePourSelect($anneeId),
            'compteNonAJour' => $this->souscriptions->compter(['annee_id' => $anneeId, 'non_a_jour' => true]),
            'compteToutes'   => $this->souscriptions->compter(['annee_id' => $anneeId]),
            'statistiques'   => $voitIndicateurs ? $this->souscriptions->statistiques($filters) : null,
            'tableauDeBord'  => $voitIndicateurs ? $this->souscriptions->tableauDeBord($anneeId) : null,
        ]);
    }

    /** Fragment HTML : paiements liés à une souscription et état du règlement. */
    public function paiements(int $souscription)
    {
        $souscription = $this->souscriptions->avecPaiement($souscription);

        return view('domain.finances.souscriptions.partials.paiements', [
            'souscription' => $souscription,
            'paiements'    => $this->souscriptions->paiementsDe($souscription),
        ]);
    }

    /** Crée les souscriptions manquantes de l'année de l'en-tête (rien n'est supprimé). */
    public function synchroniser()
    {
        return $this->runAjax(
            fn () => ['bilan' => $this->chargement->synchroniser($this->anneeId())],
            'Synchronisation terminée.'
        );
    }

    public function store(StoreSouscriptionRequest $request)
    {
        return $this->runAjax(
            fn () => ['souscription' => $this->service->creer(
                SouscriptionData::fromRequest($request->validated(), Auth::id())
            )],
            'Souscription enregistrée.'
        );
    }

    public function update(Request $request, int $souscription)
    {
        $taux = (float) $request->validate([
            'taux_remise' => ['required', 'numeric', 'min:0', 'max:100'],
        ])['taux_remise'];

        return $this->runAjax(
            fn () => ['souscription' => $this->service->modifierRemise($souscription, $taux)],
            'Remise mise à jour.'
        );
    }

    public function destroy(int $souscription)
    {
        return $this->runAjax(function () use ($souscription) {
            $this->service->supprimer($souscription);
            return null;
        }, 'Souscription désactivée.');
    }

    /** Export Excel de l'onglet affiché : année de l'en-tête et filtres en cours. */
    public function export(Request $request)
    {
        $onglet = $this->onglet($request);
        $lignes = $this->souscriptions->listePourExport($this->filtres($request, $onglet));

        return Excel::download(
            new SouscriptionsExport($lignes, self::ONGLETS[$onglet]),
            'souscriptions_' . $onglet . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /** Export PDF de l'onglet affiché : année de l'en-tête et filtres en cours. */
    public function exportPdf(Request $request)
    {
        $onglet = $this->onglet($request);
        $filters = $this->filtres($request, $onglet);

        return Pdf::loadView('domain.finances.souscriptions.print', [
            'titre'         => self::ONGLETS[$onglet],
            'annee'         => Annee::find($this->anneeId()),
            'souscriptions' => $this->souscriptions->listePourExport($filters),
            // Indicateurs financiers : Direction et Administrateur seulement
            'statistiques'  => $request->user()->can('tableau-souscriptions.consulter') ? $this->souscriptions->statistiques($filters) : null,
            'genereLe'      => now(),
            'generePar'     => $request->user(),
        ])->setPaper('A4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'dpi' => 110])
            ->download('souscriptions_' . $onglet . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
