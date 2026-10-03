<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\Logistique\Repositories\StockMouvementRepository;
use App\Domain\Logistique\Services\StockService;
use App\Domain\Logistique\Types\TypeMouvementStock;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\AjusterStockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMouvementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private StockMouvementRepository $mouvements,
        private ProduitRepository        $produits,
        private MagasinRepository        $magasins,
        private StockService             $service,
    ) {
        $this->middleware('permission:stock.voir')->only(['index', 'export']);
        $this->middleware('permission:stock.ajuster')->only(['ajuster']);
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'annee_id', 'produit_id', 'magasin_id', 'type_mouvement',
            'date_debut', 'date_fin',
        ]);

        return view('domain.logistique.mouvements.index', [
            'mouvements' => $this->mouvements->listePaginee($filters),
            'filters'    => $filters,
            'produits'   => $this->produits->listePourSelect(),
            'magasins'   => $this->magasins->listePourSelect(),
            'types'      => TypeMouvementStock::options(),
        ]);
    }

    /** Ajustement d'inventaire. */
    public function ajuster(AjusterStockRequest $request)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($data) {
            $this->service->ajuster(
                produitId:       (int) $data['produit_id'],
                magasinId:       (int) $data['magasin_id'],
                nouvelleQuantite: (float) $data['nouvelle_quantite'],
                motif:           $data['motif'],
                utilisateurId:   Auth::id(),
            );
            return null;
        }, 'Ajustement enregistré.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'annee_id', 'produit_id', 'magasin_id', 'type_mouvement',
            'date_debut', 'date_fin',
        ]);
        $mouvements = $this->mouvements->listePaginee($filters, perPage: 10000);

        $filename = 'mouvements_stock_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($mouvements) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Date', 'Produit', 'Magasin', 'Type', 'Quantité',
                'Prix unitaire', 'Valeur', 'Utilisateur', 'Libellé',
            ], ';');

            foreach ($mouvements as $m) {
                fputcsv($out, [
                    optional($m->date_stock)->format('d/m/Y'),
                    $m->produit?->libelle,
                    $m->magasin?->libelle,
                    $m->type_mouvement?->label(),
                    number_format((float) $m->quantite, 2, ',', ' '),
                    number_format((float) $m->prix_unitaire, 2, ',', ' '),
                    number_format((float) $m->valeur_mouvement, 2, ',', ' '),
                    $m->utilisateur?->name,
                    $m->libelle,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}