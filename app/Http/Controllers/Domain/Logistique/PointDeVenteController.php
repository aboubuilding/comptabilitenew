<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\DTO\VenteData;
use App\Domain\Logistique\Repositories\ClientRepository;
use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\Logistique\Repositories\VenteRepository;
use App\Domain\Logistique\Services\VenteService;
use App\Domain\Logistique\Types\TypeVente;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreVenteRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointDeVenteController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private MagasinRepository $magasins,
        private ProduitRepository $produits,
        private ClientRepository  $clients,
        private VenteService      $service,
        private VenteRepository   $ventes,
    ) {
        // Le point de vente est un écran de saisie : il est réservé à qui peut vendre
        // (la consultation des ventes passe par l'écran Ventes, permission ventes.voir).
        $this->middleware('permission:ventes.creer');
    }

    /**
     * Écran de caisse : un seul magasin à la fois (le magasin du poste).
     * Le magasin actif est passé en query string (?magasin_id=X), sinon
     * c'est le premier magasin de type boutique.
     */
    public function index(Request $request)
    {
        $magasinId = $request->input('magasin_id');
        $magasins  = $this->magasins->listePourSelect();

        $magasinActif = $magasinId
            ? $magasins->firstWhere('id', (int) $magasinId)
            : $magasins->firstWhere('type', \App\Domain\Logistique\Types\TypeMagasin::BOUTIQUE)
                ?? $magasins->first();

        if (! $magasinActif) {
            return redirect()->route('logistique.magasins.index')
                ->with('error', 'Aucun magasin disponible. Créez un magasin de type boutique.');
        }

        return view('domain.logistique.ventes.pos', [
            'magasinActif' => $magasinActif,
            'magasins'     => $magasins,
            'clients'      => $this->clients->listePourSelect(),
            'inscriptions' => app(\App\Domain\Scolarite\Repositories\InscriptionRepository::class)
                ->listePourSelect(app(AnneeScolaireContext::class)->id()),
            'ventesDuJour' => $this->ventes->ventesDuJour(
                $magasinActif->id,
                app(AnneeScolaireContext::class)->id()
            ),
        ]);
    }

    /**
     * AJAX : recherche produit pour le scan / la saisie rapide.
     * Retourne aussi le stock disponible dans le magasin actif.
     */
    public function rechercherProduit(Request $request)
    {
        $request->validate([
            'q'          => ['required', 'string', 'min:1'],
            'magasin_id' => ['required', 'exists:magasins,id'],
        ]);

        $magasinId = (int) $request->input('magasin_id');
        $q = $request->input('q');

        $produits = DB::table('produits as p')
            ->leftJoin('stock_actuel as sa', function ($join) use ($magasinId) {
                $join->on('sa.produit_id', '=', 'p.id')
                     ->where('sa.magasin_id', '=', $magasinId);
            })
            ->where('p.etat', 1)
            ->where('p.type_produit', 2) // vendable uniquement
            ->where(function ($sub) use ($q) {
                $sub->where('p.libelle', 'like', "%{$q}%")
                    ->orWhere('p.id', '=', is_numeric($q) ? (int) $q : 0);
            })
            ->select([
                'p.id', 'p.libelle', 'p.prix_unitaire', 'p.unite_stock',
                'p.photo',
                DB::raw('COALESCE(sa.quantite, 0) as stock'),
            ])
            ->orderBy('p.libelle')
            ->limit(20)
            ->get();

        return response()->json(['produits' => $produits]);
    }

    /** AJAX : ventes du jour pour le magasin actif. */
    public function ventesDuJour(Request $request)
    {
        $request->validate(['magasin_id' => ['required', 'exists:magasins,id']]);

        $ventes = $this->ventes->ventesDuJour(
            (int) $request->input('magasin_id'),
            app(AnneeScolaireContext::class)->id()
        );

        return response()->json(['ventes' => $ventes]);
    }

    /** Enregistre la vente + renvoie l'URL du ticket pour impression. */
    public function store(StoreVenteRequest $request)
    {
        $data = VenteData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(function () use ($data) {
            $vente = $this->service->enregistrer($data);
            return [
                'vente'      => $vente,
                'ticket_url' => route('logistique.ventes.ticket', $vente),
            ];
        }, 'Vente enregistrée.');
    }
}