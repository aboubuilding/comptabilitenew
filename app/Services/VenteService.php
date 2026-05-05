<?php
namespace App\Services;

use App\Models\Vente;
use App\Models\VenteDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VenteService
{
    protected StockManagerService $stockService;

    public function __construct(StockManagerService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Créer une vente avec plusieurs produits
     */
    public function createVente(array $data): Vente
    {
        $anneeId = session('LoginUser.annee_id') ?? 1;

        return DB::transaction(function () use ($data, $anneeId) {
            // 1. Vérifier les stocks pour chaque produit
            foreach ($data['details'] as $item) {
                $stock = $this->stockService->getStockActuel($item['produit_id'], $data['magasin_id']);
                if ($stock < $item['quantite']) {
                    throw new \Exception("Stock insuffisant pour le produit ID {$item['produit_id']}");
                }
            }

            // 2. Créer l'entête
            $vente = Vente::create([
                'reference'      => $this->genererReference(),
                'date_vente'     => $data['date_vente'],
                'magasin_id'     => $data['magasin_id'],
                'type_vente'     => $data['type_vente'] ?? 1,
                'statut_paiement'=> $data['statut_paiement'] ?? 0,
                'client_id'      => $data['client_id'] ?? null,
                'annee_id'       => $anneeId,
                'utilisateur_id' => Auth::id(),
                'etat'           => 1,
            ]);

            $total = 0;
            // 3. Créer les lignes et déduire le stock
            foreach ($data['details'] as $item) {
                $detail = VenteDetail::create([
                    'vente_id'       => $vente->id,
                    'produit_id'     => $item['produit_id'],
                    'quantite'       => $item['quantite'],
                    'prix_unitaire'  => $item['prix_unitaire'],
                    'remise'         => $item['remise'] ?? 0,
                    'inscription_id' => $item['inscription_id'] ?? null,
                ]);
                $total += $detail->montant_ttc;
                $this->stockService->sortieStock(
                    $item['produit_id'],
                    $data['magasin_id'],
                    $item['quantite'],
                    'Vente N°' . $vente->reference
                );
            }

            $vente->total_ht = $total;
            $vente->total_ttc = $total;
            $vente->save();

            return $vente;
        });
    }

    /**
     * Annuler une vente (annulation logique + restitution stock)
     */
    public function annulerVente(int $id): void
    {
        $vente = Vente::with('details')->findOrFail($id);
        if ($vente->etat == 0) {
            throw new \Exception("Vente déjà annulée");
        }

        DB::transaction(function () use ($vente) {
            $vente->etat = 0;
            $vente->statut_paiement = 2; // annulé
            $vente->save();

            foreach ($vente->details as $detail) {
                $this->stockService->entreeStock(
                    $detail->produit_id,
                    $vente->magasin_id,
                    $detail->quantite,
                    'Annulation vente N°' . $vente->reference
                );
            }
        });
    }

    /**
     * Liste des ventes avec filtres (magasin, dates, référence)
     */
    public function listVentes(array $filters = []): array
    {
        $query = Vente::with(['magasin', 'utilisateur', 'details.produit'])->where('etat', 1);

        if (!empty($filters['magasin_id'])) $query->where('magasin_id', $filters['magasin_id']);
        if (!empty($filters['date_debut'])) $query->whereDate('date_vente', '>=', $filters['date_debut']);
        if (!empty($filters['date_fin'])) $query->whereDate('date_vente', '<=', $filters['date_fin']);
        if (!empty($filters['reference'])) $query->where('reference', 'like', '%'.$filters['reference'].'%');

        $perPage = $filters['per_page'] ?? 15;
        $ventes = $query->orderBy('date_vente', 'desc')->paginate($perPage);

        $data = $ventes->map(fn($v) => [
            'id'          => $v->id,
            'reference'   => $v->reference,
            'date_vente'  => $v->date_vente->format('Y-m-d'),
            'magasin'     => $v->magasin?->libelle,
            'total_ttc'   => $v->total_ttc,
            'statut_paiement' => $v->statut_paiement,
            'nb_articles' => $v->details->count(),
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $ventes->currentPage(),
                'last_page'    => $ventes->lastPage(),
                'per_page'     => $ventes->perPage(),
                'total'        => $ventes->total(),
            ]
        ];
    }

    private function genererReference(): string
    {
        $prefix = 'VTE' . date('Ymd');
        $last = Vente::where('reference', 'like', $prefix.'%')->orderBy('id', 'desc')->first();
        $num = $last ? intval(substr($last->reference, -4)) + 1 : 1;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}