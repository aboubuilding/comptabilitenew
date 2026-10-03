<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\DTO\VenteData;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\VenteRepository;
use App\Domain\Logistique\Services\VenteService;
use App\Domain\Logistique\Types\VenteStatutPaiement;
use App\Domain\Finances\Services\CaisseService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReversementVenteController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private MagasinRepository $magasins,
        private VenteService      $venteService,
        private VenteRepository   $ventes,
        private CaisseService     $caisses,
    ) {
        // Écran de saisie du reversement : même droit que la vente (voir config/menu.php).
        $this->middleware('permission:ventes.creer');
    }

    /** Formulaire de saisie du carnet papier. */
    public function index(Request $request)
    {
        return view('domain.logistique.ventes.reversement', [
            'magasins' => $this->magasins->listePourSelect(),
            'produits' => app(\App\Domain\Logistique\Repositories\ProduitRepository::class)->listePourSelect(),
            // Le reversement entre dans une caisse ouverte (journal non figé)
            'caisses'  => app(CaisseRepository::class)
                ->listePourSelect(app(AnneeScolaireContext::class)->id())
                ->filter(fn ($caisse) => $caisse->isOuverte())
                ->values(),
            'dateJour' => now()->toDateString(),
        ]);
    }

    /**
     * Enregistre le reversement : une vente "globale" avec N lignes
     * correspondant au carnet papier. Une seule référence pour l'ensemble.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date_reversement' => ['required', 'date'],
            'magasin_id'       => ['required', 'exists:magasins,id'],
            'caisse_id'        => ['required', 'exists:caisses,id'],
            'reference_carnet' => ['nullable', 'string', 'max:50'],
            'commentaire'      => ['nullable', 'string', 'max:500'],
            'montant_declare'  => ['required', 'numeric', 'min:0'],

            'lignes'                 => ['required', 'array', 'min:1'],
            'lignes.*.produit_id'    => ['required', 'exists:produits,id'],
            'lignes.*.quantite'      => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
            'lignes.*.remise'        => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return $this->runAjax(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $anneeId = app(AnneeScolaireContext::class)->id();

                // Journal de caisse figé à la clôture : aucune écriture sur une caisse clôturée
                $caisse = app(CaisseRepository::class)->findOrFail((int) $data['caisse_id']);
                if (! $caisse->isOuverte()) {
                    throw new \RuntimeException('La caisse sélectionnée est clôturée : le reversement doit entrer dans une caisse ouverte.');
                }

                // 1) Créer la vente globale (type "comptoir")
                $venteData = new VenteData(
                    dateVente:     $data['date_reversement'],
                    magasinId:     (int) $data['magasin_id'],
                    typeVente:     1,
                    clientId:      null,
                    inscriptionId: null,
                    lignes:        array_map(fn ($l) => \App\Domain\Logistique\DTO\VenteLigneData::fromArray($l), $data['lignes']),
                    utilisateurId: Auth::id(),
                    marquerPayee:  true,
                );

                $vente = $this->venteService->enregistrer($venteData);

                // 2) Marquer la vente comme reverseée (traçabilité)
                $this->ventes->update($vente->id, [
                    'date_reversement' => $data['date_reversement'],
                    'caissier_id'      => Auth::id(),
                ]);

                // 3) Mouvement de caisse (encaissement du montant global)
                $montantCompte = $data['montant_declare'];
                $ecart = round($montantCompte - (float) $vente->total_ttc, 2);

                // Enregistrer directement sur la caisse
                \App\Domain\Finances\Models\Mouvement::create([
                    'libelle'          => "Reversement boutique — {$vente->reference}",
                    'beneficiaire'     => $vente->magasin?->libelle,
                    'motif'            => $data['reference_carnet']
                        ? "Carnet papier : {$data['reference_carnet']}"
                        : 'Reversement vente au comptoir',
                    'date_mouvement'   => $data['date_reversement'],
                    'montant'          => (float) $vente->total_ttc,
                    'type_mouvement'   => \App\Domain\Finances\Types\MouvementType::ENCAISSEMENT->value,
                    'caisse_id'        => (int) $data['caisse_id'],
                    'utilisateur_id'   => Auth::id(),
                    'annee_id'         => $anneeId,
                    'statut_mouvement' => 1,
                    'etat'             => 1,
                ]);

                return [
                    'vente'  => $vente,
                    'ecart'  => $ecart,
                    'message' => abs($ecart) > 0.01
                        ? "Reversement enregistré. ⚠️ Écart de {$ecart} F à justifier."
                        : 'Reversement enregistré sans écart.',
                ];
            });
        }, 'Reversement enregistré.');
    }
}