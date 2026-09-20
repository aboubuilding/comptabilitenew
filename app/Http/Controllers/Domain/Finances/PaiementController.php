<?php

namespace App\Http\Controllers\Admin\Finances;

use App\Domain\Finances\Repositories\DetailRepository;
use App\Domain\Finances\Repositories\ServiceRepository;
use App\Domain\Finances\Services\PaiementService;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Repositories\EleveRepository;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Domain\ServicesEleves\Repositories\EvenementRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\PaiementRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * "Modifier ou annuler un paiement non encore encaissé" et l'export
 * Excel (cahier des charges) restent hors scope de ce tour.
 */
class PaiementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PaiementService $paiementService,
        private DetailRepository $details,
        private EleveRepository $eleves,
        private ProduitRepository $produits,
        private ServiceRepository $services,
        private EvenementRepository $evenements,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    /**
     * Liste globale des lignes de paiement de l'année active — c'est
     * l'écran que retrouve "Paiements" dans le menu. Le bouton "Nouveau
     * paiement" y renvoie vers rechercheEleve().
     */
    public function index(Request $request): View
    {
        $anneeId = (int) $this->anneeContext->id();

        return view('admin.finances.paiements.index', [
            'details' => $this->details->paginateWithFilters($request->only(['statut', 'search']), $anneeId),
            'anneeId' => $anneeId,
        ]);
    }

    /**
     * Étape de création : recherche d'un élève avant d'ouvrir son écran
     * de paiement (l'écran de saisie lui-même est "par élève").
     */
    public function rechercheEleve(Request $request): View
    {
        $anneeId = (int) $this->anneeContext->id();

        $resultats = collect();
        if ($request->filled('search')) {
            $resultats = $this->eleves->paginateWithFilters(['search' => $request->input('search')], $anneeId, 10);
        }

        return view('admin.finances.paiements.recherche', [
            'resultats' => $resultats,
            'search'    => $request->input('search'),
        ]);
    }

    public function show(Eleve $eleve): View
    {
        $anneeId = (int) $this->anneeContext->id();

        $inscription = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_id', $anneeId)
            ->where('statut_validation', StatutValidationInscription::Validee->value)
            ->first();

        return view('admin.finances.paiements.show', [
            'eleve'       => $eleve,
            'inscription' => $inscription,
            'engagements' => $this->paiementService->engagementsPourEleve($eleve, $anneeId),
            'anneeId'     => $anneeId,
        ]);
    }

    /**
     * Recherche combinée produit/service/événement pour le panier
     * (zone 2). Événements limités à ceux sans capacité limitée — les
     * autres passent par l'écran de pré-inscription dédié.
     */
    public function rechercherPanier(Request $request): JsonResponse
    {
        $terme = (string) $request->input('q', '');
        if (mb_strlen($terme) < 2) {
            return response()->json([]);
        }

        $resultats = collect()
            ->concat($this->produits->rechercherEnStock($terme)->map(fn ($p) => [
                'nature' => 'produit', 'id' => $p->id, 'libelle' => $p->libelle,
                'montant' => $p->prix_unitaire, 'stock' => $p->quantite_stock,
            ]))
            ->concat($this->services->rechercher($terme)->map(fn ($s) => [
                'nature' => 'service', 'id' => $s->id, 'libelle' => $s->libelle, 'montant' => $s->prix_unitaire,
            ]))
            ->concat($this->evenements->rechercherSansLimite($terme)->map(fn ($e) => [
                'nature' => 'evenement', 'id' => $e->id, 'libelle' => $e->nom, 'montant' => $e->participation,
            ]));

        return response()->json($resultats->values());
    }

    public function store(PaiementRequest $request): RedirectResponse
    {
        $eleve = Eleve::findOrFail($request->input('eleve_id'));
        $anneeId = (int) $this->anneeContext->id();

        $inscription = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_id', $anneeId)
            ->where('statut_validation', StatutValidationInscription::Validee->value)
            ->first();

        return $this->runWeb(function () use ($request, $eleve, $inscription, $anneeId) {
            $this->paiementService->enregistrerPaiement([
                'eleve_id'         => $eleve->id,
                'inscription_id'   => $inscription?->id,
                'payeur'           => $request->input('payeur'),
                'telephone_payeur' => $request->input('telephone_payeur'),
                'mode_paiement'    => $request->input('mode_paiement'),
                'annee_id'         => $anneeId,
                'utilisateur_id'   => Auth::id(),
                'engagements'      => $request->input('engagements', []),
                'panier'           => $request->input('panier', []),
            ]);
        }, 'finances.paiements.show', "Paiement enregistré avec succès, en attente d'encaissement.", ['eleve' => $eleve->id]);
    }
}